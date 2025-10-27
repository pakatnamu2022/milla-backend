<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICANDO TABLA INTERMEDIA (dbtp) ===\n\n";

// Obtener el proveedor de la OC
$po = DB::table('ap_purchase_order')->where('id', 1)->first();
$supplier = DB::table('business_partners')->where('id', $po->supplier_id)->first();

echo "Proveedor en OC: {$supplier->full_name} (RUC: {$supplier->num_doc})\n\n";

// Listar EmpresaIds disponibles
echo "EmpresaIds disponibles en neInTbProveedor:\n";
$empresas = DB::connection('dbtp')
    ->table('neInTbProveedor')
    ->select('EmpresaId')
    ->distinct()
    ->limit(5)
    ->get();

foreach ($empresas as $emp) {
    echo "- {$emp->EmpresaId}\n";
}

echo "\n";

// Verificar proveedor en tabla intermedia
$proveedor = DB::connection('dbtp')
    ->table('neInTbProveedor')
    ->where('NumeroDocumento', $supplier->num_doc)
    ->first();

if ($proveedor) {
    echo "✓ Proveedor en tabla intermedia | EmpresaId: {$proveedor->EmpresaId} | ProcesoEstado: {$proveedor->ProcesoEstado}\n";
} else {
    echo "✗ Proveedor NO encontrado en tabla intermedia\n";
}

// Verificar artículo
$vehicle = DB::table('ap_vehicles')->where('id', 1)->first();
$model = DB::table('ap_models_vn')->where('id', $vehicle->ap_models_vn_id)->first();

echo "Modelo: {$model->code}\n\n";

$articulo = DB::connection('dbtp')
    ->table('neInTbArticulo')
    ->where('Articulo', $model->code)
    ->first();

if ($articulo) {
    echo "✓ Artículo en tabla intermedia: {$articulo->Articulo} | EmpresaId: {$articulo->EmpresaId} | ProcesoEstado: {$articulo->ProcesoEstado}\n";
} else {
    echo "✗ Artículo NO encontrado en tabla intermedia\n";
}

// Verificar OC
$oc = DB::connection('dbtp')
    ->table('neInTbOrdenCompra')
    ->where('OrdenCompraId', $po->number)
    ->first();

if ($oc) {
    echo "✓ OC en tabla intermedia: {$oc->OrdenCompraId} | EmpresaId: {$oc->EmpresaId} | ProcesoEstado: {$oc->ProcesoEstado} | ProcesoError: " . ($oc->ProcesoError ?? 'ninguno') . "\n";
} else {
    echo "✗ OC NO encontrada en tabla intermedia\n";
}

// Verificar OC Detalle
$ocDet = DB::connection('dbtp')
    ->table('neInTbOrdenCompraDet')
    ->where('OrdenCompraId', $po->number)
    ->first();

if ($ocDet) {
    echo "✓ OC Detalle en tabla intermedia | Artículo: {$ocDet->ArticuloId}\n";
} else {
    echo "✗ OC Detalle NO encontrado en tabla intermedia\n";
}

echo "\n";
