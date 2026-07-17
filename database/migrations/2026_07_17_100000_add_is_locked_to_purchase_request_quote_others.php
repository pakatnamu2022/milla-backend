<?php

use App\Models\ap\comercial\PurchaseRequestQuote;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_request_quote_others', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('value');
        });

        DB::table('purchase_request_quote_others')
            ->where('description', 'FLETE E INMATRICULACIÓN')
            ->update(['is_locked' => true]);

        $this->recalculateAllMargins();
    }

    public function down(): void
    {
        Schema::table('purchase_request_quote_others', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }

    private function recalculateAllMargins(): void
    {
        PurchaseRequestQuote::with(['discountCoupons', 'accessories', 'others', 'vehicle'])
            ->chunk(100, function ($quotes) {
                foreach ($quotes as $quote) {
                    $margin = $this->calculateMargin($quote);
                    $quote->timestamps = false;
                    $quote->update($margin);
                }
            });
    }

    private function calculateMargin(PurchaseRequestQuote $quote): array
    {
        $vehicle   = $quote->vehicle;
        $salePrice = (float) $quote->base_selling_price;
        $billedCost = $vehicle ? (float) $vehicle->purchase_price : 0;

        if (!$vehicle || !$vehicle->vin || $billedCost <= 0 || $salePrice <= 0) {
            return ['margin_amount' => 0, 'margin_pct' => 0];
        }

        $bonusTotal    = 0.0;
        $discountTotal = 0.0;
        foreach ($quote->discountCoupons as $d) {
            $d->is_negative
                ? $discountTotal += (float) $d->precio_unitario
                : $bonusTotal    += (float) $d->precio_unitario;
        }

        $paidAccTotal = 0.0;
        $giftTotal    = 0.0;
        foreach ($quote->accessories as $acc) {
            $acc->type === 'OBSEQUIO'
                ? $giftTotal    += (float) $acc->total
                : $paidAccTotal += (float) $acc->total;
        }

        $extraCostsTotal = 0.0;
        $fleteRows       = [];
        foreach ($quote->others as $other) {
            $other->is_locked
                ? $fleteRows[]         = $other
                : $extraCostsTotal    += (float) $other->amount;
        }

        $clientRevenue = $salePrice - $discountTotal + $paidAccTotal;
        $totalIncome   = $clientRevenue + $bonusTotal;
        $vehicleCosts  = $billedCost + $giftTotal + $extraCostsTotal;

        $grossDiff    = $totalIncome - $vehicleCosts;
        $netDiff      = $grossDiff / 1.18;
        $netSalePrice = $salePrice / 1.18;

        $othersNetTotal = 0.0;
        foreach ($fleteRows as $flete) {
            $othersNetTotal += $flete->type === 'PORCENTAJE'
                ? ((float) $flete->value / 100) * $netSalePrice
                : (float) $flete->value;
        }

        $realMarginAmount = $netDiff - $othersNetTotal;
        $realMarginPct    = $netSalePrice > 0 ? ($realMarginAmount / $netSalePrice) * 100 : 0;

        return [
            'margin_amount' => round($realMarginAmount, 4),
            'margin_pct'    => round($realMarginPct, 4),
        ];
    }
};
