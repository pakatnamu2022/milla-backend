<?php

namespace App\Models\ap\comercial;

use App\Http\Traits\Reportable;
use App\Models\ap\ApMasters;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\gp\maestroGeneral\ExchangeRate;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PurchaseRequestQuote extends Model
{
  use SoftDeletes, Reportable;

  protected $table = 'purchase_request_quote';

  protected $fillable = [
    'correlative',
    'type_document',
    'type_vehicle',
    'quote_deadline',
    'exchange_rate_id',
    'base_selling_price',
    'sale_price',
    'doc_sale_price',
    'down_payment',
    'comment',
    'is_invoiced',
    'is_approved',
    'warranty_years',
    'warranty_km',
    'opportunity_id',
    'holder_id',
    'vehicle_color_id',
    'ap_models_vn_id',
    'ap_vehicle_id',
    'type_currency_id',
    'doc_type_currency_id',
    'sede_id',
    'status'
  ];

  const filters = [
    'search' => ['correlative', 'apModelsVn.code', 'holder.full_name', 'holder.num_doc', 'opportunity.worker.nombre_completo'],
    'type_document' => '=',
    'type_vehicle' => '=',
    'quote_deadline' => '=',
    'exchange_rate_id' => '=',
    'base_selling_price' => '=',
    'sale_price' => '=',
    'opportunity_id' => '=',
    'holder_id' => '=',
    'vehicle_color_id' => '=',
    'ap_models_vn_id' => '=',
    'apModelsVn.family.brand_id' => '=',
    'ap_vehicle_id' => '=',
    'doc_type_currency_id' => '=',
    'is_invoiced' => '=',
    'is_approved' => '=',
    'sede_id' => '=',
    'has_vehicle' => 'accessor',
    'status' => '=',
    'is_paid' => 'accessor',
    'created_at' => 'date_between',
  ];

  const sorts = [
    'id',
    'created_at',
    'updated_at',
  ];

  public function getIsInvoicedAttribute(): bool
  {
    return $this->electronicDocuments()
      ->where('aceptada_por_sunat', 1)
      ->where('anulado', 0)
      ->where(function ($query) {
        $query->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_FACTURA)
          ->orWhere('sunat_concept_document_type_id', ElectronicDocument::TYPE_BOLETA);
      })
      ->where('anulado', 0)
      ->whereNull('deleted_at')
      ->where('is_advance_payment', 0)
      ->exists();
  }


  /**
   * Determina si la cotización está pagada comparando el total de los documentos electrónicos asociados con el precio de venta de la cotización.
   * Solo se consideran los documentos electrónicos que han sido aceptados por SUNAT, que no están anulados, que no han sido eliminados.
   * @return bool
   */
  public function getIsPaidAttribute(): bool
  {
    $total = $this->electronicDocuments()
      ->where('aceptada_por_sunat', 1)
      ->where(function ($query) {
        $query->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_FACTURA)
          ->orWhere('sunat_concept_document_type_id', ElectronicDocument::TYPE_BOLETA);
      })
      ->where('anulado', 0)
      ->whereNull('deleted_at')
      ->sum('total');
    return $this->sale_price == $total;
  }

  public function getHasVehicleAttribute(): bool
  {
    return !is_null($this->ap_vehicle_id);
  }

  public function setCommentAttribute($value): void
  {
    if ($value) {
      $this->attributes['comment'] = Str::upper($value);
    }
  }

  public function discountCoupons()
  {
    return $this->hasMany(DiscountCoupons::class, 'purchase_request_quote_id');
  }

  public function accessories(): HasMany
  {
    return $this->hasMany(DetailsApprovedAccessoriesQuote::class, 'purchase_request_quote_id');
  }

  public function vehicleColor(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'vehicle_color_id');
  }

  public function docTypeCurrency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'doc_type_currency_id');
  }

  public function typeCurrency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'type_currency_id');
  }

  public function apModelsVn(): BelongsTo
  {
    return $this->belongsTo(ApModelsVn::class, 'ap_models_vn_id');
  }

  public function opportunity(): BelongsTo
  {
    return $this->belongsTo(Opportunity::class, 'opportunity_id');
  }

  public function holder(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'holder_id');
  }

  public function exchangeRate(): BelongsTo
  {
    return $this->belongsTo(ExchangeRate::class, 'exchange_rate_id');
  }

  public function vehicle(): belongsTo
  {
    return $this->belongsTo(Vehicles::class, 'ap_vehicle_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  /**
   * Relación inversa con órdenes de compra
   */
  public function purchaseOrders(): HasOne
  {
    return $this->hasOne(PurchaseOrder::class, 'quotation_id');
  }

  protected $reportColumns = [
    'created_at' => [
      'label' => 'Fecha de Registro',
      'formatter' => 'date:d/m/Y H:i',
      'width' => 22,
    ],
    'correlative' => [
      'label' => 'Correlativo',
      'formatter' => null,
      'width' => 18,
    ],
    'sede.abreviatura' => [
      'label' => 'Sede',
      'formatter' => null,
      'width' => 15,
    ],
    'type_document' => [
      'label' => 'Tipo Documento',
      'formatter' => null,
      'width' => 18,
    ],
    'type_vehicle' => [
      'label' => 'Tipo Vehículo',
      'formatter' => null,
      'width' => 18,
    ],
    'opportunity.worker.nombre_completo' => [
      'label' => 'Asesor',
      'formatter' => null,
      'width' => 30,
    ],
    'holder.num_doc' => [
      'label' => 'N° Doc. Titular',
      'formatter' => null,
      'width' => 18,
    ],
    'holder.full_name' => [
      'label' => 'Titular',
      'formatter' => null,
      'width' => 30,
    ],
    'apModelsVn.family.brand.name' => [
      'label' => 'Marca',
      'formatter' => null,
      'width' => 20,
    ],
    'apModelsVn.code' => [
      'label' => 'Modelo',
      'formatter' => null,
      'width' => 20,
    ],
    'vehicleColor.description' => [
      'label' => 'Color',
      'formatter' => null,
      'width' => 18,
    ],
    'typeCurrency.code' => [
      'label' => 'Moneda Base',
      'formatter' => null,
      'width' => 15,
    ],
    'sale_price' => [
      'label' => 'Precio Venta',
      'formatter' => null,
      'width' => 18,
    ],
    'docTypeCurrency.code' => [
      'label' => 'Moneda Doc.',
      'formatter' => null,
      'width' => 15,
    ],
    'doc_sale_price' => [
      'label' => 'Precio Doc.',
      'formatter' => null,
      'width' => 18,
    ],
    'quote_deadline' => [
      'label' => 'Vigencia',
      'formatter' => 'date:d/m/Y',
      'width' => 15,
    ],
    'is_approved' => [
      'label' => 'Aprobado',
      'formatter' => null,
      'width' => 12,
    ],
    'status' => [
      'label' => 'Estado',
      'formatter' => null,
      'width' => 12,
    ],
  ];

  protected $reportRelations = [
    'sede',
    'holder',
    'apModelsVn.family.brand',
    'typeCurrency',
    'docTypeCurrency',
    'vehicleColor',
    'opportunity.worker',
  ];

  public function activate(): void
  {
    $this->status = 1;
    $this->save();
  }

  public function desactivate(): void
  {
    $this->status = 0;
    $this->save();
  }

  /**
   * Obtiene todos los documentos electrónicos (facturas, boletas, etc.) a través de la purchase_request_quote_id
   * Un purchase request quote puede tener múltiples documentos electrónicos asociados
   */
  public function electronicDocuments(): HasMany
  {
    return $this->hasMany(ElectronicDocument::class, 'purchase_request_quote_id');
  }
}
