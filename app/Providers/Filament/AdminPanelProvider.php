<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\KpiOverviewWidget;
use App\Filament\Widgets\RecentAuditEventsWidget;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Library\Filament\Resources\InstrumentResource;
use Modules\Library\Filament\Resources\ObligationResource;
use Modules\Library\Filament\Resources\RegulatorResource;
use Modules\Policy\Filament\Resources\PolicyResource;
use Modules\Sanctkb\Filament\Resources\SanctionResource;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::hex('#1A365D'),
                'warning' => Color::hex('#D4AF37'),
            ])
            ->resources([
                InstrumentResource::class,
                ObligationResource::class,
                RegulatorResource::class,
                SanctionResource::class,
                PolicyResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                KpiOverviewWidget::class,
                RecentAuditEventsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->navigationGroups([
                'Library',
                'Sanctions',
                'Policy',
                'System',
            ]);
    }

}
