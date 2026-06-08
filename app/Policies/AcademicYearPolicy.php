<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AcademicYear;
use Illuminate\Auth\Access\HandlesAuthorization;

class AcademicYearPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AcademicYear');
    }

    public function view(AuthUser $authUser, AcademicYear $academicYear): bool
    {
        return $authUser->can('View:AcademicYear');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AcademicYear');
    }

    public function update(AuthUser $authUser, AcademicYear $academicYear): bool
    {
        return $authUser->can('Update:AcademicYear');
    }

    public function delete(AuthUser $authUser, AcademicYear $academicYear): bool
    {
        return $authUser->can('Delete:AcademicYear');
    }

    public function restore(AuthUser $authUser, AcademicYear $academicYear): bool
    {
        return $authUser->can('Restore:AcademicYear');
    }

    public function forceDelete(AuthUser $authUser, AcademicYear $academicYear): bool
    {
        return $authUser->can('ForceDelete:AcademicYear');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AcademicYear');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AcademicYear');
    }

    public function replicate(AuthUser $authUser, AcademicYear $academicYear): bool
    {
        return $authUser->can('Replicate:AcademicYear');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AcademicYear');
    }

}