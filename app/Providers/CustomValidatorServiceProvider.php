<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CustomValidatorServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    Validator::extend('exists_soft', function ($attribute, $value, $parameters) {
      $table = $parameters[0] ?? null;
      $column = $parameters[1] ?? 'id';

      if (!$table || !$column) {
        return false;
      }

      $count = DB::table($table)
        ->where($column, $value)
        ->whereNull('deleted_at')
        ->count();

      return $count > 0;
    }, 'El campo :attribute no existe.');
  }
}
