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
   * DNI del creador de la transacción
   */
  public ?string $creatorVat;

  /**
   * Constructor
   */
  public function __construct($resource, int $asientoNumber, bool $isReversal = false, ?string $creatorVat = null)
  {
    parent::__construct($resource);
    $this->asientoNumber = $asientoNumber;
    $this->isReversal = $isReversal;
    $this->creatorVat = $creatorVat;
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

    // PASO 1: Agrupar por clase de artículo (sin importar la fecha de compra)
    $groupedByClass = [];

    foreach ($traverseItems as $item) {
      $product = $item->product;
      if (!$product || !$product->articleClass) {
        continue;
      }

      $classArticle = $product->articleClass;

      // Obtener precio de venta del item de la factura
      $unitPrice = (float)$item->valor_unitario;

      foreach ($item->linkTransactions as $transaction) {
        $quantity = (float)$transaction->cantidad;
        $discount = (float)($item->descuento ?? 0);
        $totalAmount = ($unitPrice * $quantity) - $discount;

        $classId = $classArticle->id;
        if (!isset($groupedByClass[$classId])) {
          $groupedByClass[$classId] = [
            'class_article' => $classArticle,
            'total_amount' => 0,
          ];
        }

        $groupedByClass[$classId]['total_amount'] += $totalAmount;
      }
    }

    // PASO 2: Generar las líneas del asiento
    $allLines = [];
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

      // Formato de descripción: {serie}-{numero} | Texto (máx 30 caracteres)
      $docNumber = "{$this->serie}-{$this->numero}";

      $descripcionCosto = $this->isReversal
        ? "{$docNumber} | Rev. costo trav."
        : "{$docNumber} | Costo trav.";

      $descripcionIngreso = $this->isReversal
        ? "{$docNumber} | Rev. ingr. trav."
        : "{$docNumber} | Ingreso trav.";

      // Truncar si excede 30 caracteres
      if (strlen($descripcionCosto) > 30) {
        $descripcionCosto = substr($descripcionCosto, 0, 30);
      }
      if (strlen($descripcionIngreso) > 30) {
        $descripcionIngreso = substr($descripcionIngreso, 0, 30);
      }

      // Línea 1: DEBE - Cuenta en tránsito (4961500)
      // Si es reversión, invertimos los montos
      $allLines[] = [
        'Asiento' => $this->asientoNumber,
        'Linea' => $lineNumber++,
        'CuentaNumero' => $cuentaTransito,
        'Debito' => $this->isReversal ? 0.00 : $totalAmount,
        'Credito' => $this->isReversal ? $totalAmount : 0.00,
        'Descripcion' => $descripcionCosto,
        'LoteId' => $this->creatorVat ?? '',
      ];

      // Línea 2: HABER - Cuenta de ventas
      $allLines[] = [
        'Asiento' => $this->asientoNumber,
        'Linea' => $lineNumber++,
        'CuentaNumero' => $cuentaVentas,
        'Debito' => $this->isReversal ? $totalAmount : 0.00,
        'Credito' => $this->isReversal ? 0.00 : $totalAmount,
        'Descripcion' => $descripcionIngreso,
        'LoteId' => $this->creatorVat ?? '',
      ];
    }

    if (empty($allLines)) {
      throw new Exception("No se generaron líneas de asiento contable.");
    }

    // Validar balance total
    $this->validateBalance($allLines);

    return $allLines;
  }

  /**
   * Valida que la suma de débitos sea igual a la suma de créditos
   */
  protected function validateBalance(array $lines): void
  {
    $totalDebitos = 0;
    $totalCreditos = 0;

    foreach ($lines as $line) {
      $totalDebitos += $line['Debito'];
      $totalCreditos += $line['Credito'];
    }

    $diff = abs($totalDebitos - $totalCreditos);
    if ($diff > 0.01) {
      throw new Exception(sprintf(
        'Balance contable inválido: Débitos=%.2f, Créditos=%.2f, Diferencia=%.2f',
        $totalDebitos,
        $totalCreditos,
        $diff
      ));
    }
  }
}