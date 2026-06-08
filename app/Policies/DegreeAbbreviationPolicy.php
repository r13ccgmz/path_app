<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DegreeAbbreviation;
use Illuminate\Auth\Access\HandlesAuthorization;

class DegreeAbbreviationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DegreeAbbreviation');
    }

    public function view(AuthUser $authUser, DegreeAbbreviation $degreeAbbreviation): bool
    {
        return $authUser->can('View:DegreeAbbreviation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DegreeAbbreviation');
    }

    public function update(AuthUser $authUser, DegreeAbbreviation $degreeAbbreviation): bool
    {
        return $authUser->can('Update:DegreeAbbreviation');
    }

    public function delete(AuthUser $authUser, DegreeAbbreviation $degreeAbbreviation): bool
    {
        return $authUser->can('Delete:DegreeAbbreviation');
    }

    public function restore(AuthUser $authUser, DegreeAbbreviation $degreeAbbreviation): bool
    {
        return $authUser->can('Restore:DegreeAbbreviation');
    }

    public function forceDelete(AuthUser $authUser, DegreeAbbreviation $degreeAbbreviation): bool
    {
        return $authUser->can('ForceDelete:DegreeAbbreviation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DegreeAbbreviation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DegreeAbbreviation');
    }

    public function replicate(AuthUser $authUser, DegreeAbbreviation $degreeAbbreviation): bool
    {
        return $authUser->can('Replicate:DegreeAbbreviation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DegreeAbbreviation');
    }

}