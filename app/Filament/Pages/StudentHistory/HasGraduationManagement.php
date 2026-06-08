<?php

namespace App\Filament\Pages\StudentHistory;



/**
 * HasGraduationManagement trait extracted from StudentHistory.
 */
trait HasGraduationManagement
{

    /**
     * Start editing a specific graduation record (called from Blade).
     */
    public function startEditGraduation(int $id): void
    {
        abort_if(auth()->user()?->hasRole('viewer'), 403, 'Unauthorized action.');

        $this->editingGraduateId = $id;
        $this->mountAction('editGraduateInfo');
    }


    /**
     * Start deleting a specific graduation record (called from Blade).
     */
    public function startDeleteGraduation(int $id): void
    {
        abort_if(auth()->user()?->hasRole('viewer'), 403, 'Unauthorized action.');

        $this->editingGraduateId = $id;
        $this->mountAction('deleteGraduateInfo');
    }

}
