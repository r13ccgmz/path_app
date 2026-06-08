<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MilestoneTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;

class MilestoneTemplatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MilestoneTemplate');
    }

    public function view(AuthUser $authUser, MilestoneTemplate $milestoneTemplate): bool
    {
        return $authUser->can('View:MilestoneTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MilestoneTemplate');
    }

    public function update(AuthUser $authUser, MilestoneTemplate $milestoneTemplate): bool
    {
        return $authUser->can('Update:MilestoneTemplate');
    }

    public function delete(AuthUser $authUser, MilestoneTemplate $milestoneTemplate): bool
    {
        return $authUser->can('Delete:MilestoneTemplate');
    }

    public function restore(AuthUser $authUser, MilestoneTemplate $milestoneTemplate): bool
    {
        return $authUser->can('Restore:MilestoneTemplate');
    }

    public function forceDelete(AuthUser $authUser, MilestoneTemplate $milestoneTemplate): bool
    {
        return $authUser->can('ForceDelete:MilestoneTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MilestoneTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MilestoneTemplate');
    }

    public function replicate(AuthUser $authUser, MilestoneTemplate $milestoneTemplate): bool
    {
        return $authUser->can('Replicate:MilestoneTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MilestoneTemplate');
    }

}