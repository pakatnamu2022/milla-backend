<?php

namespace App\Http\Utils;

/**
 * Única fuente de verdad para el redondeo en cadena de montos facturables
 * (repuestos, mano de obra y detalles de cotización): el precio unitario y la
 * cantidad se mantienen en 2 decimales, y total_cost/net_amount/tax_amount
 * se redondean a 2 decimales en cadena, cada paso a partir del
 * anterior ya redondeado.
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
    $totalCost = round($unitPrice * $quantity, 2);

    if ($discountPercentage > 0) {
      $discountAmount = $totalCost * ($discountPercentage / 100);
      $netAmount = round($totalCost - $discountAmount, 2);
    } else {
      $netAmount = $totalCost;
    }

    // Redondear primero a 3 decimales y luego a 2 para un redondeo más preciso
    // Ejemplo: 960.47 * 0.18 = 172.8846 → 172.885 → 172.89
    $taxAmount = round(round($netAmount * (Constants::VAT_TAX / 100), 3), 2);

    return [
      'total_cost' => $totalCost,
      'net_amount' => $netAmount,
      'tax_amount' => $taxAmount,
    ];
  }

  /**
   * Punto único para el par "redondear precio unitario ya convertido de moneda" +
   * "calcular totales de línea", usado por repuestos, mano de obra y detalles de
   * cotización de OT/cotización, tanto al crear/editar un ítem como al recalcular
   * en lote (cambio de moneda, reparación de totales). $factor por defecto 1.0
   * cuando no hay conversión de moneda de por medio.
   *
   * @return array{unit_price: float, total_cost: float, net_amount: float, tax_amount: float}
   */
  public static function calculateLine(float $basePrice, float $quantity, float $discountPercentage = 0, float $factor = 1.0): array
  {
    $unitPrice = self::roundUnitPrice($basePrice * $factor);

    return array_merge(
      ['unit_price' => $unitPrice],
      self::calculateLineTotals($unitPrice, $quantity, $discountPercentage)
    );
  }
}
