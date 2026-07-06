<?php

namespace App\Http\Utils;

/**
 * Única fuente de verdad para el redondeo en cadena de montos facturables
 * (repuestos, mano de obra y detalles de cotización): el precio unitario y la
 * cantidad se mantienen en 2 decimales, pero total_cost/net_amount/tax_amount
 * se redondean a 1 decimal (S/ 0.10) en cadena, cada paso a partir del
 * anterior ya redondeado, para que el monto a facturar no quede con céntimos
 * sueltos al pagar al contado.
 */
class PriceRounding
{
  public static function roundUnitPrice(float $unitPrice): float
  {
    return round($unitPrice, 2);
  }

  /**
   * @return array{total_cost: float, net_amount: float, tax_amount: float}
   */
  public static function calculateLineTotals(float $unitPrice, float $quantity, float $discountPercentage = 0): array
  {
    $totalCost = round($unitPrice * $quantity, 1);

    if ($discountPercentage > 0) {
      $discountAmount = $totalCost * ($discountPercentage / 100);
      $netAmount = round($totalCost - $discountAmount, 1);
    } else {
      $netAmount = $totalCost;
    }

    $taxAmount = round($netAmount * (Constants::VAT_TAX / 100), 1);

    return [
      'total_cost' => $totalCost,
      'net_amount' => $netAmount,
      'tax_amount' => $taxAmount,
    ];
  }
}
