# Importación de Productos

## Formato del Archivo Excel

Coloca tu archivo Excel en esta carpeta con el nombre `products.xlsx`

### Columnas requeridas:

| Columna                | Tipo    | Requerido | Descripción                                      |
|------------------------|---------|-----------|--------------------------------------------------|
| code                   | Texto   | Sí        | Código del producto                              |
| name                   | Texto   | Sí        | Nombre del producto                              |
| description            | Texto   | No        | Descripción del producto                         |
| product_category_id    | Número  | No        | ID de la categoría del producto                  |
| brand_id               | Número  | No        | ID de la marca                                   |
| unit_measurement_id    | Número  | Sí        | ID de la unidad de medida                        |
| ap_class_article_id    | Número  | No        | ID de la clase de artículo                       |

### Valores por defecto (se establecen automáticamente):
- cost_price: 0
- sale_price: 0
- tax_rate: 18
- is_taxable: 1
- sunat_code: ""
- product_type: GOOD
- status: ACTIVE
- current_stock: 0
- minimum_stock: 0

### Valores generados automáticamente:
- dyn_code: Se genera con un correlativo basado en el código
- nubefac_code: Se usa el mismo código del producto

## Ejemplo de Excel:

```
code        | name                  | description              | product_category_id | brand_id | unit_measurement_id | ap_class_article_id
------------|----------------------|--------------------------|---------------------|----------|---------------------|--------------------
PROD001     | Filtro de aceite     | Filtro para motor        | 1                   | 5        | 2                   | 3
PROD002     | Pastillas de freno   | Juego de pastillas       | 2                   | 7        | 1                   | 4
PROD003     | Aceite 5W-30         | Aceite sintético         | 1                   | 5        | 3                   | 3
```

## Comandos de importación:

### Usar el archivo por defecto (storage/app/imports/products.xlsx):
```bash
php artisan import:products
```

### Especificar una ruta diferente:
```bash
php artisan import:products "C:\ruta\al\archivo\productos.xlsx"
```

## Notas importantes:

1. El archivo debe ser un Excel (.xlsx o .xls)
2. La primera fila DEBE contener los nombres de las columnas exactamente como se muestran arriba
3. Los IDs de categoría, marca, unidad de medida y clase de artículo deben existir en la base de datos
4. El comando mostrará un resumen de registros exitosos y errores al finalizar