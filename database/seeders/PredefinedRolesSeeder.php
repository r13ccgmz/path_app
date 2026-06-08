<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class PredefinedRolesSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Generate shield permissions programmatically to ensure all are up-to-date
        \Illuminate\Support\Facades\Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--no-interaction' => true,
        ]);

        // Clear Spatie permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Truncate/Clean old roles to start fresh
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Role::whereNotIn('name', ['super_admin', 'admin', 'viewer'])->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Establish Predefined Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        // 3. Fetch all generated permissions
        $allPermissions = Permission::all();

        // 4. Admin Permissions Logic:
        // Admin gets everything EXCEPT Role and User permissions.
        $adminPerms = $allPermissions->filter(function ($permission) {
            $name = $permission->name;
            // Exclude User and Role resource permissions
            if (str_ends_with($name, ':User') || str_ends_with($name, ':Role')) {
                return false;
            }
            // Exclude settings/roles page view permissions
            if ($name === 'View:Role' || $name === 'View:User') {
                return false;
            }
            return true;
        });
        $adminRole->syncPermissions($adminPerms);

        // 5. Viewer Permissions Logic:
        // Viewer gets View/ViewAny for academics, faculty, and student profiles ONLY.
        // Strictly NO log permissions, settings pages, match reports, or graduation eligibility.
        $viewerAllowedResources = [
            'Student', 'Enrollee', 'Faculty',
            'Course', 'Program', 'AcademicYear',
            'Unit', 'CognateField', 'DegreeAbbreviation', 'MilestoneTemplate'
        ];
        $viewerAllowedPagesAndWidgets = [
            'StudentHistory', 'FacultyWorkload', 'UserManual',
            'HelpAndUserGuide', 'CurriculumMap', 'AllAcademicOutputs',
            'Dashboard', 'StatsOverviewWidget', 'DashboardStudentOverviewWidget',
            'DashboardStudentDemographicsChartsWidget', 'MentorshipMonitoring'
        ];

        $viewerPerms = $allPermissions->filter(function ($permission) use ($viewerAllowedResources, $viewerAllowedPagesAndWidgets) {
            $name = $permission->name;

            // ViewAny:Resource or View:Resource for allowed resources
            foreach ($viewerAllowedResources as $res) {
                if ($name === "ViewAny:{$res}" || $name === "View:{$res}") {
                    return true;
                }
            }

            // View:Page or View:Widget for allowed items
            foreach ($viewerAllowedPagesAndWidgets as $item) {
                if ($name === "View:{$item}") {
                    return true;
                }
            }

            return false;
        });
        $viewerRole->syncPermissions($viewerPerms);

        // 6. Ensure default/test users exist with password "12345678"
        // --- Super Admin ---
        $superAdminUser = User::updateOrCreate(
            ['email' => 'admin@cpaf.uplb.edu.ph'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => Hash::make('12345678'),
                'account_status' => 'active',
            ]
        );
        $superAdminUser->syncRoles([$superAdminRole]);

        // --- Admin ---
        $adminUser = User::updateOrCreate(
            ['email' => 'admin_test@cpaf.uplb.edu.ph'],
            [
                'first_name' => 'Standard',
                'last_name' => 'Admin',
                'password' => Hash::make('12345678'),
                'account_status' => 'active',
            ]
        );
        $adminUser->syncRoles([$adminRole]);

        // --- Viewer ---
        $viewerUser = User::updateOrCreate(
            ['email' => 'viewer_test@cpaf.uplb.edu.ph'],
            [
                'first_name' => 'Guest',
                'last_name' => 'Viewer',
                'password' => Hash::make('12345678'),
                'account_status' => 'active',
            ]
        );
        $viewerUser->syncRoles([$viewerRole]);
    }
}
