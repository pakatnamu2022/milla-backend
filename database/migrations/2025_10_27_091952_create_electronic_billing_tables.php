<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    // NOTA: Los catálogos SUNAT ahora están consolidados en la tabla sunat_concepts
    // Ya no se crean tablas separadas para tipos de documento, monedas, IGV, etc.

    // Tabla principal de comprobantes electrónicos
    Schema::create('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->id();

      // Tipo de comprobante (referencia a sunat_concepts con tipo BILLING_DOCUMENT_TYPE)
      $table->foreignId('sunat_concept_document_type_id')
        ->constrained('sunat_concepts', 'id', 'fk_billing_doc_type');
      $table->string('serie', 4)->comment('Serie del comprobante (FFF1, BBB1, etc.)');
      $table->integer('numero')->comment('Número correlativo');

      // Tipo de operación (referencia a sunat_concepts con tipo BILLING_TRANSACTION_TYPE)
      $table->foreignId('sunat_concept_transaction_type_id')
        ->constrained('sunat_concepts', 'id', 'fk_billing_trans_type');

      // Origen del documento (Comercial o Posventa)
      $table->enum('origin_module', ['comercial', 'posventa'])->comment('Módulo de origen');
      $table->string('origin_entity_type', 100)->nullable()->comment('Tipo de entidad de origen');
      $table->unsignedBigInteger('origin_entity_id')->nullable()->comment('ID de la entidad de origen');

      // Relación con movimiento de vehículo (para comercial)
      $table->foreignId('ap_vehicle_movement_id')->nullable()->constrained('ap_vehicle_movement')->nullOnDelete();

      // Datos del cliente (referencia a sunat_concepts con tipo TYPE_DOCUMENT)
      $table->foreignId('sunat_concept_identity_document_type_id')
        ->constrained('sunat_concepts', 'id', 'fk_billing_identity_document_type');

      $table->string('cliente_numero_de_documento', 15);
      $table->string('cliente_denominacion', 100);
      $table->string('cliente_direccion', 250)->nullable();
      $table->string('cliente_email', 250)->nullable();
      $table->string('cliente_email_1', 250)->nullable();
      $table->string('cliente_email_2', 250)->nullable();

      // Fechas
      $table->date('fecha_de_emision');
      $table->date('fecha_de_vencimiento')->nullable();

      // Moneda y tipo de cambio (referencia a sunat_concepts con tipo BILLING_CURRENCY)
      $table->foreignId('sunat_concept_currency_id')->constrained('sunat_concepts');
      $table->decimal('tipo_de_cambio', 10, 3)->nullable();
      $table->decimal('porcentaje_de_igv', 5, 2)->default(18.00);

      // Totales
      $table->decimal('descuento_global', 12, 2)->nullable();
      $table->decimal('total_descuento', 12, 2)->nullable();
      $table->decimal('total_anticipo', 12, 2)->nullable();
      $table->decimal('total_gravada', 12, 2)->nullable();
      $table->decimal('total_inafecta', 12, 2)->nullable();
      $table->decimal('total_exonerada', 12, 2)->nullable();
      $table->decimal('total_igv', 12, 2)->nullable();
      $table->decimal('total_gratuita', 12, 2)->nullable();
      $table->decimal('total_otros_cargos', 12, 2)->nullable();
      $table->decimal('total_isc', 12, 2)->nullable();
      $table->decimal('total', 12, 2);

      // Percepción
      $table->integer('percepcion_tipo')->nullable()->comment('1=2%, 2=1%, 3=0.5%');
      $table->decimal('percepcion_base_imponible', 12, 2)->nullable();
      $table->decimal('total_percepcion', 12, 2)->nullable();
      $table->decimal('total_incluido_percepcion', 12, 2)->nullable();

      // Retención
      $table->integer('retencion_tipo')->nullable()->comment('1=3%, 2=6%');
      $table->decimal('retencion_base_imponible', 12, 2)->nullable();
      $table->decimal('total_retencion', 12, 2)->nullable();

      // Detracción (referencia a sunat_concepts con tipo BILLING_DETRACTION_TYPE)
      $table->boolean('detraccion')->default(false);
      $table->foreignId('sunat_concept_detraction_type_id')->nullable()
        ->constrained('sunat_concepts', 'id', 'fk_billing_detraction_type')->nullOnDelete();
      $table->decimal('detraccion_total', 12, 10)->nullable();
      $table->decimal('detraccion_porcentaje', 8, 5)->nullable();
      $table->integer('medio_de_pago_detraccion')->nullable();

      // Campos para Notas de Crédito/Débito (referencias a sunat_concepts)
      $table->integer('documento_que_se_modifica_tipo')->nullable();
      $table->string('documento_que_se_modifica_serie', 4)->nullable();
      $table->integer('documento_que_se_modifica_numero')->nullable();
      $table->foreignId('sunat_concept_credit_note_type_id')->nullable()->constrained('sunat_concepts', 'id', 'fk_billing_credit_note_type')->nullOnDelete();
      $table->foreignId('sunat_concept_debit_note_type_id')->nullable()->constrained('sunat_concepts', 'id', 'fk_billing_debit_note_type')->nullOnDelete();

      // Observaciones y otros campos
      $table->text('observaciones')->nullable();
      $table->string('condiciones_de_pago', 250)->nullable();
      $table->string('medio_de_pago', 250)->nullable();
      $table->string('placa_vehiculo', 8)->nullable();
      $table->string('orden_compra_servicio', 20)->nullable();
      $table->string('codigo_unico', 20)->nullable()->comment('Código único del sistema');

      // Configuración de envío
      $table->boolean('enviar_automaticamente_a_la_sunat')->default(true);
      $table->boolean('enviar_automaticamente_al_cliente')->default(false);
      $table->boolean('generado_por_contingencia')->default(false);

      // Respuesta de Nubefact
      $table->string('enlace', 500)->nullable();
      $table->string('enlace_del_pdf', 500)->nullable();
      $table->string('enlace_del_xml', 500)->nullable();
      $table->string('enlace_del_cdr', 500)->nullable();
      $table->boolean('aceptada_por_sunat')->nullable();
      $table->text('sunat_description')->nullable();
      $table->text('sunat_note')->nullable();
      $table->string('sunat_responsecode', 10)->nullable();
      $table->text('sunat_soap_error')->nullable();
      $table->boolean('anulado')->default(false);
      $table->string('cadena_para_codigo_qr', 500)->nullable();
      $table->string('codigo_hash', 100)->nullable();

      // Estado interno
      $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'cancelled'])->default('draft');
      $table->text('error_message')->nullable();
      $table->timestamp('sent_at')->nullable();
      $table->timestamp('accepted_at')->nullable();
      $table->timestamp('cancelled_at')->nullable();

      // Auditoría
      $table->integer('created_by')->nullable();
      $table->foreign('created_by')
        ->references('id')
        ->on('usr_users')
        ->nullOnDelete();

      $table->integer('updated_by')->nullable();
      $table->foreign('updated_by')
        ->references('id')
        ->on('usr_users')
        ->nullOnDelete();

      $table->timestamps();
      $table->softDeletes();

      // Índices
      $table->unique(['serie', 'numero', 'sunat_concept_document_type_id'], 'unique_document');
      $table->index(['origin_module', 'origin_entity_type', 'origin_entity_id'], 'idx_origin_ref');
      $table->index('fecha_de_emision');
      $table->index('status');
      $table->index('cliente_numero_de_documento', 'idx_client_document');
    });

    // Tabla de items/líneas del comprobante
    Schema::create('ap_billing_electronic_document_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ap_billing_electronic_document_id')->constrained('ap_billing_electronic_documents', 'id', 'fk_ap_billing_electronic_document_1')->cascadeOnDelete();
      $table->integer('line_number')->comment('Número de línea');

      // Producto/Servicio
      $table->string('unidad_de_medida', 3)->comment('NIU, ZZ, KGM, etc.');
      $table->string('codigo', 30)->nullable()->comment('Código interno del producto');
      $table->string('codigo_producto_sunat', 8)->nullable()->comment('Código SUNAT UNSPSC');
      $table->string('descripcion', 250);

      // Cantidades y precios
      $table->decimal('cantidad', 12, 10);
      $table->decimal('valor_unitario', 12, 10)->comment('Valor sin IGV');
      $table->decimal('precio_unitario', 12, 10)->comment('Precio con IGV');
      $table->decimal('descuento', 12, 2)->nullable();
      $table->decimal('subtotal', 12, 10)->comment('cantidad * valor_unitario');

      // IGV (referencia a sunat_concepts con tipo BILLING_IGV_TYPE)
      $table->foreignId('sunat_concept_igv_type_id')->constrained('sunat_concepts', 'id', 'fk_billing_igv_type');
      $table->decimal('igv', 12, 10);
      $table->decimal('total', 12, 10);

      // Anticipo
      $table->boolean('anticipo_regularizacion')->default(false);
      $table->string('anticipo_documento_serie', 4)->nullable();
      $table->integer('anticipo_documento_numero')->nullable();

      // Auditoría
      $table->timestamps();
      $table->softDeletes();

      // Índices
      $table->index('ap_billing_electronic_document_id', 'idx_document_items');
    });

    // Tabla de guías de remisión asociadas
    Schema::create('ap_billing_electronic_document_guides', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ap_billing_electronic_document_id')->constrained('ap_billing_electronic_documents', 'id', 'fk_ap_billing_electronic_document_2')->cascadeOnDelete();
      $table->integer('guia_tipo')->comment('1=Remitente, 2=Transportista');
      $table->string('guia_serie_numero', 20);
      $table->timestamps();
      $table->softDeletes();
    });

    // Tabla de cuotas para venta al crédito
    Schema::create('ap_billing_electronic_document_installments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ap_billing_electronic_document_id')->constrained('ap_billing_electronic_documents', 'id', 'fk_ap_billing_electronic_document_3')->cascadeOnDelete();
      $table->integer('cuota')->comment('Número de cuota');
      $table->date('fecha_de_pago');
      $table->decimal('importe', 12, 2);
      $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
      $table->timestamp('paid_at')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });

    // Tabla de log de comunicaciones con Nubefact
    Schema::create('ap_billing_nubefact_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ap_billing_electronic_document_id')->nullable()->constrained('ap_billing_electronic_documents', 'id', 'fk_ap_billing_electronic_document_4')->nullOnDelete();
      $table->enum('operation', ['generar_comprobante', 'consultar_comprobante', 'generar_anulacion', 'consultar_anulacion']);
      $table->text('request_payload')->comment('JSON enviado');
      $table->text('response_payload')->nullable()->comment('JSON recibido');
      $table->integer('http_status_code')->nullable();
      $table->boolean('success')->default(false);
      $table->text('error_message')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_billing_nubefact_logs');
    Schema::dropIfExists('ap_billing_electronic_document_installments');
    Schema::dropIfExists('ap_billing_electronic_document_guides');
    Schema::dropIfExists('ap_billing_electronic_document_items');
    Schema::dropIfExists('ap_billing_electronic_documents');
    // NOTA: Las tablas de catálogos ya no se crean aquí, están en sunat_concepts
  }
};
