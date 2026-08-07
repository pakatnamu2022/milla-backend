<?php

namespace App\Http\Services\ap\comercial;

use App\Models\ap\comercial\PurchaseRequestQuote;
use Illuminate\Support\Collection;

class ApPurchaseRequestQuoteReportService
{
  public function generate(
    string  $fechaInicio,
    string  $fechaFin,
    ?array  $sedeIds = null,
    ?array  $brandIds = null,
    ?array  $familyIds = null,
    ?array  $modelIds = null
  ): Collection {
    $model = new PurchaseRequestQuote();

    $filters = [
      ['column' => 'created_at', 'operator' => 'date_between', 'value' => [$fechaInicio, $fechaFin]],
    ];

    if (!empty($sedeIds)) {
      $filters[] = ['column' => 'sede_id', 'operator' => 'in', 'value' => $sedeIds];
    }

    if (!empty($brandIds)) {
      $filters[] = ['column' => 'apModelsVn.family.brand_id', 'operator' => 'in', 'value' => $brandIds];
    }

    if (!empty($familyIds)) {
      $filters[] = ['column' => 'apModelsVn.family_id', 'operator' => 'in', 'value' => $familyIds];
    }

    if (!empty($modelIds)) {
      $filters[] = ['column' => 'ap_models_vn_id', 'operator' => 'in', 'value' => $modelIds];
    }

    return $model->getReportData($filters);
  }
}
