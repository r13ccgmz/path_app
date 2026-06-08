<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HelpAndUserGuide extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Help & User Guide';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.help-and-user-guide';

    public function mount(): void
    {
        $this->redirect(UserManual::getUrl());
    }
}
