<x-filament-panels::page>
    {{-- Search --}}
    <div class="mb-6" x-data="{ search: '' }">
        <div class="relative">
            <x-heroicon-o-magnifying-glass class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input type="text" x-model="search" placeholder="Search the manual..."
                class="fi-input block w-full rounded-lg border-gray-300 shadow-sm pl-10 transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
        </div>
    </div>

    <div class="space-y-4" x-data="{ openSection: null }">

        {{-- 1. Getting Started --}}
        <x-filament::section
            :collapsible="true"
            :collapsed="true"
            icon="heroicon-o-rocket-launch"
            heading="Getting Started"
            description="Login, navigation, and profile setup"
        >
            <div class="prose dark:prose-invert max-w-none text-sm">
                <h4>Logging In</h4>
                <p>Navigate to <code>/admin</code> and enter your email and password. Contact your system administrator if you don't have credentials.</p>

                <h4>Navigation</h4>
                <p>PATH uses <strong>top navigation</strong> with dropdown menus organized into groups:</p>
                <ul>
                    <li><strong>Academics</strong> — Programs, Courses, Academic Years, Curriculum Map</li>
                    <li><strong>Faculty Management</strong> — Faculty Directory, Mentorship Monitoring, Faculty Workload</li>
                    <li><strong>Student Management</strong> — Academic Outputs, Enrollees, Graduates, Student History, Student Progress</li>
                    <li><strong>System</strong> — Import Logs, Milestone Templates, Normalizations, Settings, Activity Log, User Manual</li>
                    <li><strong>Users</strong> — User Accounts, Roles & Permissions</li>
                </ul>

                <h4>Global Search</h4>
                <p>Press <kbd>Ctrl+K</kbd> (or <kbd>⌘+K</kbd> on Mac) to open the global search. You can search for students, faculty, and other records.</p>

                <h4>Your Profile</h4>
                <p>Click your avatar in the top-right corner to access your profile. You can update your name, email, password, and upload a profile photo.</p>
            </div>
        </x-filament::section>

        {{-- 2. Dashboard --}}
        <x-filament::section
            :collapsible="true"
            :collapsed="true"
            icon="heroicon-o-chart-bar-square"
            heading="Dashboard"
            description="Understanding your dashboard stats and charts"
        >
            <div class="prose dark:prose-invert max-w-none text-sm">
                <h4>Overview Stats</h4>
                <p>The top row shows key metrics: Total Enrollments, Unique Students, Graduate Records, Faculty Count, and Academic Outputs.</p>

                <h4>Student Overview</h4>
                <p>Shows the breakdown of students by status (Active, Graduated, On Leave, Inactive) and a doughnut chart of program distribution.</p>

                <h4>Term Filters</h4>
                <p>Use the <strong>From</strong> and <strong>To</strong> dropdowns at the top of the dashboard to filter all widgets by semester range. This affects enrollment trends, admissions, demographics, and all student statistics.</p>

                <h4>Charts</h4>
                <ul>
                    <li><strong>Enrollments per Term</strong> — Bar chart showing enrollment trends over time</li>
                    <li><strong>Admissions Trend</strong> — Line chart of new admissions per semester</li>
                    <li><strong>Academic Progress</strong> — Overview of student completion rates</li>
                    <li><strong>Demographics</strong> — Gender distribution, nationality, and age charts</li>
                </ul>
            </div>
        </x-filament::section>

        {{-- 3. Student Management --}}
        <x-filament::section
            :collapsible="true"
            :collapsed="true"
            icon="heroicon-o-users"
            heading="Student Management"
            description="Student History, enrollment records, milestones, and academic outputs"
        >
            <div class="prose dark:prose-invert max-w-none text-sm">
                <h4>Student History (List of Students)</h4>
                <p>The main student management page. View all students in a searchable, filterable table.</p>
                <ul>
                    <li><strong>Search</strong> — Type a student number or name in the search bar to find a specific student</li>
                    <li><strong>Filters</strong> — Filter by Student Status, Adviser, Program, or Admission Semester</li>
                    <li><strong>Click a student</strong> to view their full enrollment history, courses, and academic details</li>
                </ul>

                <h4>Student Detail View</h4>
                <p>When viewing a specific student, you'll see:</p>
                <ul>
                    <li>Personal information panel with full details</li>
                    <li>Enrollment history across all terms</li>
                    <li>Academic progress (units earned vs. required)</li>
                    <li>Milestones tracking (coursework, exams, research, defense)</li>
                    <li>Academic outputs (thesis/dissertation)</li>
                    <li>Committee members (adviser, chair, panel)</li>
                </ul>

                <h4>Adding a Student</h4>
                <p>Click <strong>"Add Student"</strong> in the table header to manually create a student record. Fill in the required fields (Student Number, Last Name, First Name) and optionally set program and contact details.</p>

                <h4>Enrollees</h4>
                <p>The Enrollees page shows raw imported enrollment data. Use the <strong>Import</strong> button to upload enrollment spreadsheets (CSV/XLSX). Imported data is automatically linked to existing student records.</p>
            </div>
        </x-filament::section>

        {{-- 4. Student Progress --}}
        <x-filament::section
            :collapsible="true"
            :collapsed="true"
            icon="heroicon-o-academic-cap"
            heading="Student Progress & Graduation"
            description="Tracking completion and identifying graduation candidates"
        >
            <div class="prose dark:prose-invert max-w-none text-sm">
                <h4>Student Progress Page</h4>
                <p>Shows each student's progress toward completing their degree program, organized by StudentProgram records.</p>
                <ul>
                    <li><strong>Completion %</strong> — Units earned ÷ Total units required × 100</li>
                    <li><strong>GWA</strong> — General Weighted Average</li>
                    <li><strong>Residency</strong> — Number of semesters enrolled vs. maximum residency</li>
                </ul>

                <h4>Graduation Candidates</h4>
                <p>Use the <strong>"Candidates for Graduation"</strong> filter to show only students who have met the completion threshold. This threshold is configurable in <strong>System → Settings → Graduation Settings</strong>.</p>

                <h4>Sync Student Data</h4>
                <p>Click <strong>"Sync Student Data"</strong> to process all enrollee records and update student program information, enrollment records, and unit calculations.</p>
            </div>
        </x-filament::section>

        {{-- 5. Graduate Management --}}
        <x-filament::section
            :collapsible="true"
            :collapsed="true"
            icon="heroicon-o-trophy"
            heading="Graduate Management"
            description="Importing and managing graduate records"
        >
            <div class="prose dark:prose-invert max-w-none text-sm">
                <h4>List of Graduates</h4>
                <p>View all graduate records. Import graduate data via <strong>Excel upload</strong>.</p>

                <h4>Auto-Matching</h4>
                <p>The system uses fuzzy name matching to link graduate records to existing student records. Click <strong>"Auto-Match"</strong> to run the matching algorithm, then review and confirm matches.</p>

                <h4>Manual Matching</h4>
                <p>Use <strong>"Quick Match"</strong> to manually review and link unmatched graduates to students.</p>
            </div>
        </x-filament::section>

        {{-- 6. Faculty Management --}}
        <x-filament::section
            :collapsible="true"
            :collapsed="true"
            icon="heroicon-o-user-group"
            heading="Faculty Management"
            description="Faculty directory, mentorship, and workload tracking"
        >
            <div class="prose dark:prose-invert max-w-none text-sm">
                <h4>Faculty Directory</h4>
                <p>View and manage faculty records including personal info, employment status, academic rank, and specializations.</p>

                <h4>Creating User Accounts</h4>
                <p>From the faculty table, use the <strong>"Create Account"</strong> action to generate a user account linked to a faculty member. This allows them to log into the system.</p>

                <h4>Mentorship Monitoring</h4>
                <p>Track faculty advisory loads across three committee types:</p>
                <ul>
                    <li><strong>Student Committees</strong> — Adviser, Chair, and Member assignments</li>
                    <li><strong>Graduate Committees</strong> — Imported from graduate records</li>
                    <li><strong>Academic Output Committees</strong> — Thesis/dissertation committees</li>
                </ul>
                <p>Use the semester filter or "Current Term" toggle to view assignments for specific periods.</p>

                <h4>Faculty Workload</h4>
                <p>The Faculty Workload page shows teaching assignments per semester. Filter by semester to see how many courses each faculty member teaches and identify those without any course assignments.</p>
            </div>
        </x-filament::section>

        {{-- 7. Academics --}}
        <x-filament::section
            :collapsible="true"
            :collapsed="true"
            icon="heroicon-o-building-library"
            heading="Academics"
            description="Programs, courses, and curriculum management"
        >
            <div class="prose dark:prose-invert max-w-none text-sm">
                <h4>Programs</h4>
                <p>Manage degree programs (Master's, Doctorate). Each program defines total units required, max residency years, and minimum units per course type.</p>

                <h4>Courses</h4>
                <p>The course catalog with codes and names. Courses can be assigned to programs via the Curriculum Map.</p>

                <h4>Curriculum Map</h4>
                <p>A visual interface for mapping courses to programs. View courses by type (Core, Major, Elective, etc.) in a timeline or table layout. Add, edit, or remove course mappings. Export to PDF.</p>

                <h4>Academic Years</h4>
                <p>Manage academic years and their semesters (1st Semester, 2nd Semester, Midyear). Set the current semester.</p>
            </div>
        </x-filament::section>

        {{-- 8. System --}}
        <x-filament::section
            :collapsible="true"
            :collapsed="true"
            icon="heroicon-o-cog-6-tooth"
            heading="System Administration"
            description="Settings, imports, normalizations, and activity logs"
        >
            <div class="prose dark:prose-invert max-w-none text-sm">
                <h4>Settings</h4>
                <p>Configure system-wide settings:</p>
                <ul>
                    <li><strong>Enrollment Settings</strong> — Full-time/Part-time units threshold</li>
                    <li><strong>Graduation Settings</strong> — Candidate completion threshold percentage</li>
                    <li><strong>Student Status Sync</strong> — Automatic inactivity detection rules</li>
                </ul>

                <h4>Import Logs</h4>
                <p>View history of all data imports with row counts, errors, and results. Click an import to see detailed information and affected records.</p>

                <h4>Normalizations</h4>
                <p>Data cleanup rules for standardizing imported data. Types include: Enrollee Programs, Course Codes, Graduate Degrees, and more.</p>

                <h4>Activity Log</h4>
                <p>Browse all changes made to records in the system. See who changed what, when, and what the old/new values were. Use the <strong>Revert</strong> action to undo changes.</p>

                <h4>Milestone Templates</h4>
                <p>Define milestone templates (coursework, examination, research, publication, defense) that can be assigned to students.</p>
            </div>
        </x-filament::section>

        {{-- 9. User Administration --}}
        <x-filament::section
            :collapsible="true"
            :collapsed="true"
            icon="heroicon-o-shield-check"
            heading="User Administration"
            description="Managing user accounts, roles, and permissions"
        >
            <div class="prose dark:prose-invert max-w-none text-sm">
                <h4>User Accounts</h4>
                <p>Create, edit, and manage user accounts. Assign roles to control access. Set account status to active or inactive.</p>

                <h4>Roles & Permissions</h4>
                <p>PATH uses role-based access control (RBAC). Roles define what each user can see and do:</p>
                <ul>
                    <li><strong>Super Admin</strong> — Full access to all features, settings, and system administration.</li>
                    <li><strong>Admin</strong> — Access to most features, excluding role and user management.</li>
                    <li><strong>Viewer</strong> — Limited viewing access to academic profiles, faculty directory, and student progress without editing privileges.</li>
                </ul>
                <p>Permissions are automatically generated for each resource and page. Use the Roles page to customize which permissions each role has.</p>
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
