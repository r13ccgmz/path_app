<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\MenuItem;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(\App\Filament\Pages\EditProfile::class, isSimple: false)
            ->brandName('PATH')
            ->brandLogo(fn () => view('filament.partials.logo'))
            ->brandLogoHeight('4.5rem')
            // Sidebar branding hooks removed because we are using topNavigation
            ->renderHook(
                \Filament\View\PanelsRenderHook::BODY_START,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@include(\'filament.auth.login-background\') @include(\'filament.auth.login-top-bar\')')
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@include(\'filament.partials.theme-switch-override\')')
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@include(\'filament.auth.login-footer\')')
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => '<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>'
            )
            ->colors([
                'primary' => Color::hex('#1A5C38'),
                'success' => Color::hex('#28A745'),
                'warning' => Color::hex('#F0AD4E'),
                'danger'  => Color::hex('#DC3545'),
                'info'    => Color::hex('#1A2B6B'),
                'gray'    => Color::Slate,
            ])
            ->font('Avenir, "Helvetica Neue", Optima, sans-serif')
            ->topNavigation()
            ->navigationGroups([
                NavigationGroup::make('Main'),
                NavigationGroup::make('Academics'),
                NavigationGroup::make('Student Management'),
                NavigationGroup::make('Academic Outputs'),
                NavigationGroup::make('System'),
                NavigationGroup::make('Users'),
            ])
            // Sidebar footer removed because we are using topNavigation
            ->userMenuItems([
                MenuItem::make()
                    ->label('Help & User Guide')
                    ->url(fn (): string => \App\Filament\Pages\HelpAndUserGuide::getUrl())
                    ->icon('heroicon-o-question-mark-circle'),
                MenuItem::make()
                    ->label('Official Website')
                    ->url('https://cpaf.uplb.edu.ph')
                    ->icon('heroicon-o-globe-alt'),
            ])
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->databaseNotifications()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->plugins([
                FilamentShieldPlugin::make()
                    ->globallySearchable(false),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // Default AccountWidget removed in favor of custom dashboard title
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
