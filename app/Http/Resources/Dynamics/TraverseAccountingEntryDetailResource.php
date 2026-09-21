<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\Warehouse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TraverseAccountingEntryDetailResource extends JsonResource
{
  /**
   * Número de asiento
   */
  public int $asientoNumber;

  /**
   * Indica si es una reversión
   */
  public bool $isReversal;

  /**
   * Constructor
   */
  public function __construct($resource, int $asientoNumber, bool $isReversal = false)
  {
    parent::__construct($resource);
    $this->asientoNumber = $asientoNumber;
    $this->isReversal = $isReversal;
  }

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    /** @var ElectronicDocument $this */

    // Obtener todos los items en travesía con sus transacciones activas
    $traverseItems = $this->items()
      ->where('is_traverse', true)
      ->whereNotNull('product_id')
      ->with([
        'linkTransactions' => function ($query) {
          $query->where('status', 'active')
            ->with([
              'purchaseOrderItem.product.articleClass',
              'purchaseOrderItem.purchaseOrder'
            ]);
        }
      ])
      ->get();

    if ($traverseItems->isEmpty()) {
      throw new Exception("No se encontraron items en travesía con product_id válido.");
    }

    // Obtener la sede del documento electrónico
    if (!$this->seriesModel || !$this->seriesModel->sede_id) {
      throw new Exception("El documento electrónico no tiene una sede asociada.");
    }

    $sedeId = $this->seriesModel->sede_id;

    // Obtener el almacén físico de postventa para esta sede
    $warehouse = Warehouse::getPhysicalWarehouseForPostsale($sedeId);
    if (!$warehouse) {
      throw new Exception("No se pudo obtener el almacén físico de postventa para la sede ID {$sedeId}.");
    }

    if (!$warehouse->sede) {
      throw new Exception("El almacén físico de postventa no tiene sede asociada.");
    }

    $sede = $warehouse->sede;

    // PASO 1: Agrupar por fecha de compra
    $groupedByPurchaseDate = [];

    foreach ($traverseItems as $item) {
      $product = $item->product;
      if (!$product || !$product->articleClass) {
        continue;
      }

      $classArticle = $product->articleClass;

      // Obtener precio de venta del item de la factura
      $unitPrice = (float)$item->valor_unitario;

      foreach ($item->linkTransactions as $transaction) {
        $purchaseOrder = $transaction->purchaseOrderItem->purchaseOrder;

        if (!$purchaseOrder || !$purchaseOrder->emission_date) {
          throw new Exception("La orden de compra no tiene fecha de emisión.");
        }

        $purchaseDate = $purchaseOrder->emission_date->format('Y-m-d');
        $quantity = (float)$transaction->cantidad;
        $totalAmount = $unitPrice * $quantity;

        // Agrupar por fecha de compra -> clase de artículo
        if (!isset($groupedByPurchaseDate[$purchaseDate])) {
          $groupedByPurchaseDate[$purchaseDate] = [];
        }

        $classId = $classArticle->id;
        if (!isset($groupedByPurchaseDate[$purchaseDate][$classId])) {
          $groupedByPurchaseDate[$purchaseDate][$classId] = [
            'class_article' => $classArticle,
            'total_amount' => 0,
          ];
        }

        $groupedByPurchaseDate[$purchaseDate][$classId]['total_amount'] += $totalAmount;
      }
    }

    // PASO 2: Generar las líneas del asiento para cada fecha de compra
    $allLines = [];

    foreach ($groupedByPurchaseDate as $purchaseDate => $groupedByClass) {
      $lineNumber = 1;

      foreach ($groupedByClass as $classId => $data) {
        $classArticle = $data['class_article'];
        $totalAmount = round($data['total_amount'], 2);

        if ($totalAmount <= 0) {
          continue;
        }

        // Obtener cuenta de ventas desde ApClassArticle
        $accountSales = $classArticle->account_sales;

        if (!$accountSales) {
          throw new Exception("La clase de artículo '{$classArticle->description}' no tiene cuenta de ventas configurada.");
        }

        // Cuenta en tránsito (hardcodeada)
        $cuentaTransito = '4961500-' . $sede->dyn_code;

        // Cuenta de ventas con código de sede
        $cuentaVentas = $accountSales . '-' . $sede->dyn_code;

        // Línea 1: DEBE - Cuenta en tránsito (4961500)
        // Si es reversión, invertimos los montos
        $allLines[] = [
          'purchase_date' => $purchaseDate, // Metadata para agrupación posterior
          'Asiento' => $this->asientoNumber,
          'Linea' => $lineNumber++,
          'CuentaNumero' => $cuentaTransito,
          'Debito' => $this->isReversal ? 0.00 : $totalAmount,
          'Credito' => $this->isReversal ? $totalAmount : 0.00,
          'Descripcion' => $this->isReversal
            ? 'Reversión costo travesía - ' . $classArticle->description
            : 'Costo travesía - ' . $classArticle->description,
        ];

        // Línea 2: HABER - Cuenta de ventas
        $allLines[] = [
          'purchase_date' => $purchaseDate, // Metadata para agrupación posterior
          'Asiento' => $this->asientoNumber,
          'Linea' => $lineNumber++,
          'CuentaNumero' => $cuentaVentas,
          'Debito' => $this->isReversal ? $totalAmount : 0.00,
          'Credito' => $this->isReversal ? 0.00 : $totalAmount,
          'Descripcion' => $this->isReversal
            ? 'Reversión ingreso travesía - ' . $classArticle->description
            : 'Ingreso travesía - ' . $classArticle->description,
        ];
      }
    }

    if (empty($allLines)) {
      throw new Exception("No se generaron líneas de asiento contable.");
    }

    // Validar balance para cada fecha de compra
    $this->validateBalanceByPurchaseDate($allLines);

    return $allLines;
  }

  /**
   * Valida que la suma de débitos sea igual a la suma de créditos para cada fecha de compra
   */
  protected function validateBalanceByPurchaseDate(array $lines): void
  {
    // Agrupar por purchase_date
    $groupedByDate = [];
    foreach ($lines as $line) {
      $date = $line['purchase_date'];
      if (!isset($groupedByDate[$date])) {
        $groupedByDate[$date] = [
          'debitos' => 0,
          'creditos' => 0,
        ];
      }
      $groupedByDate[$date]['debitos'] += $line['Debito'];
      $groupedByDate[$date]['creditos'] += $line['Credito'];
    }

    // Validar balance para cada fecha
    foreach ($groupedByDate as $date => $totals) {
      $diff = abs($totals['debitos'] - $totals['creditos']);
      if ($diff > 0.01) {
        throw new Exception(sprintf(
          'Balance contable inválido para fecha %s: Débitos=%.2f, Créditos=%.2f, Diferencia=%.2f',
          $date,
          $totals['debitos'],
          $totals['creditos'],
          $diff
        ));
      }
    }
  }
}