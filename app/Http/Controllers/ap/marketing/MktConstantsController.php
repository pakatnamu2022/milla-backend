<?php

namespace App\Http\Controllers\ap\marketing;

use App\Http\Controllers\Controller;
use App\Models\ap\marketing\MktActivity;
use App\Models\ap\marketing\MktBudget;
use App\Models\ap\marketing\MktPlan;
use App\Models\ap\marketing\MktProposal;
use App\Models\ap\marketing\MktPurchaseOrder;
use App\Models\ap\marketing\MktSupport;
use Illuminate\Http\JsonResponse;

class MktConstantsController extends Controller
{
  public function index(): JsonResponse
  {
    return response()->json([
      'plan_statuses' => $this->toOptions(MktPlan::STATUS_LABELS),

      'budget_types'    => $this->toOptions(MktBudget::TYPE_LABELS),
      'budget_statuses' => $this->toOptions(MktBudget::STATUS_LABELS),

      'activity_statuses' => $this->toOptions(MktActivity::STATUS_LABELS),

      'proposal_statuses' => $this->toOptions(MktProposal::STATUS_LABELS),

      'purchase_order_statuses' => $this->toOptions(MktPurchaseOrder::STATUS_LABELS),

      'support_types' => $this->toOptions(MktSupport::TYPE_LABELS),
    ]);
  }

  private function toOptions(array $labels): array
  {
    return array_map(
      fn($value, $label) => ['value' => $value, 'label' => $label],
      array_keys($labels),
      $labels
    );
  }
}
