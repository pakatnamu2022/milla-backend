<?php

/**
 * Script de prueba para entender el flujo de sincronización de OC genéricas
 *
 * FLUJO CORREGIDO:
 *
 * 1. verifyAndSyncProducts():
 *    - Recorre TODOS los productos únicos de la OC
 *    - Crea UN LOG por cada producto único (usando dyn_code como external_id)
 *    - Despacha SyncProductArticleJob para cada producto que no existe
 *    - Logging detallado: muestra cuántos jobs se despacharon vs cuántos ya existen
 *
 * 2. verifyAndSyncGenericPurchaseOrder():
 *    - Obtiene productos ÚNICOS requeridos por la OC
 *    - Verifica que TODOS los productos requeridos tengan su log
 *    - Si falta algún log: DETIENE el proceso (return)
 *    - Verifica que TODOS los logs tengan proceso_estado = 1
 *    - Si alguno está pendiente: DETIENE el proceso (return)
 *    - Solo cuando TODOS están listos: crea purchase_order y purchase_order_detail
 *
 * EJEMPLO CON 3 PRODUCTOS:
 *
 * Primera ejecución del job:
 * - verifyAndSyncProducts() crea 3 logs (R067-001673, R067-001675, R067-001680)
 * - Despacha 3 jobs de SyncProductArticleJob
 * - verifyAndSyncGenericPurchaseOrder() verifica: "Faltan 3 productos pendientes" → RETURN
 *
 * Segunda ejecución (después que 2 productos se procesaron):
 * - verifyAndSyncProducts() encuentra 2 productos con proceso_estado=1, 1 con proceso_estado=0
 * - No despacha nuevos jobs
 * - verifyAndSyncGenericPurchaseOrder() verifica: "Esperando procesamiento de 1 producto" → RETURN
 *
 * Tercera ejecución (todos los productos listos):
 * - verifyAndSyncProducts() encuentra 3 productos con proceso_estado=1
 * - verifyAndSyncGenericPurchaseOrder() verifica: "✓ Todos los productos (3) están procesados"
 * - Procede a crear purchase_order y purchase_order_detail
 *
 * VENTAJAS:
 * ✅ Sincroniza productos únicos (evita duplicados)
 * ✅ Logging claro y detallado
 * ✅ No avanza hasta que TODOS estén listos
 * ✅ Reintentos automáticos mediante el job queue
 */

echo "Este es un script de documentación. No ejecutar directamente.\n";
echo "Revisa el contenido para entender el flujo corregido.\n";

