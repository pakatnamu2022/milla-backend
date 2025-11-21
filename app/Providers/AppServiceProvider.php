<?php

namespace App\Providers;


use App\Http\Services\common\EmailService;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    $this->app->singleton(EmailService::class, function ($app) {
      return new EmailService();
    });
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    // Configurar Scramble con autenticación Sanctum
    Scramble::extendOpenApi(function (OpenApi $openApi) {
      $openApi->secure(
        SecurityScheme::http('bearer', 'sanctum')
      );
    });

    // Registrar Blade Directives personalizados para permisos
    $this->registerPermissionBladeDirectives();
  }

  /**
   * Registrar Blade Directives para verificación de permisos
   */
  protected function registerPermissionBladeDirectives(): void
  {
    // @permission('vehicle_purchase_order.export')
    Blade::if('permission', function (string $permission) {
      return auth()->check() && auth()->user()->hasPermission($permission);
    });

    // @anyPermission(['permission1', 'permission2'])
    Blade::if('anyPermission', function (array $permissions) {
      return auth()->check() && auth()->user()->hasAnyPermission($permissions);
    });

    // @allPermissions(['permission1', 'permission2'])
    Blade::if('allPermissions', function (array $permissions) {
      return auth()->check() && auth()->user()->hasAllPermissions($permissions);
    });

    // @canView('vehicle_purchase_order')
    Blade::if('canView', function (string $vistaSlug) {
      return auth()->check() && auth()->user()->hasAccessToView($vistaSlug, 'ver');
    });

    // @canCreate('vehicle_purchase_order')
    Blade::if('canCreate', function (string $vistaSlug) {
      return auth()->check() && auth()->user()->hasAccessToView($vistaSlug, 'crear');
    });

    // @canEdit('vehicle_purchase_order')
    Blade::if('canEdit', function (string $vistaSlug) {
      return auth()->check() && auth()->user()->hasAccessToView($vistaSlug, 'editar');
    });

    // @canDelete('vehicle_purchase_order')
    Blade::if('canDelete', function (string $vistaSlug) {
      return auth()->check() && auth()->user()->hasAccessToView($vistaSlug, 'anular');
    });
  }
}
