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
        $this->editingGraduateId = $id;
        $this->mountAction('editGraduateInfo');
    }


    /**
     * Start deleting a specific graduation record (called from Blade).
     */
    public function startDeleteGraduation(int $id): void
    {
        $this->editingGraduateId = $id;
        $this->mountAction('deleteGraduateInfo');
    }

}
