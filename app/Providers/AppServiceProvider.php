<?php

namespace App\Providers;


use App\Http\Services\common\EmailService;
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
    // Registrar observers para actualización automática de dashboards
    \App\Models\gp\gestionhumana\evaluacion\Evaluation::observe(\App\Observers\EvaluationObserver::class);
    \App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult::observe(\App\Observers\EvaluationPersonResultObserver::class);
    \App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail::observe(\App\Observers\EvaluationPersonCompetenceDetailObserver::class);
    \App\Models\gp\gestionhumana\evaluacion\EvaluationPerson::observe(\App\Observers\EvaluationPersonObserver::class);
  }
}
