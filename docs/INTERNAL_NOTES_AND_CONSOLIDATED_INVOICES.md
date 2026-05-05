# Internal Notes and Consolidated Invoices

This document explains the internal notes system for work orders and how to create consolidated invoices.

## Overview

The system allows you to:
1. Create internal notes for work orders
2. Close work orders with an internal note instead of immediate invoicing
3. Generate consolidated invoices that group multiple internal notes (work orders) into a single invoice

## Database Schema

### Tables Created

#### 1. `ap_internal_notes`
Stores internal notes generated for work orders.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| number | string(50) | Unique sequential number (e.g., IN-00001) |
| work_order_id | bigint | Foreign key to ap_work_orders |
| created_date | date | Internal note creation date |
| closed_date | date (nullable) | Closing date when associated with an invoice |
| status | enum | 'pending' or 'invoiced' |
| timestamps | - | created_at, updated_at |
| deleted_at | timestamp | Soft delete |

**Indexes:**
- `work_order_id`
- `status`
- `created_date`

#### 2. `electronic_document_internal_notes`
Pivot table connecting invoices with internal notes (many-to-many).

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| electronic_document_id | bigint | Foreign key to ap_billing_electronic_documents |
| internal_note_id | bigint | Foreign key to ap_internal_notes |
| timestamps | - | created_at, updated_at |

**Indexes:**
- `electronic_document_id`
- `internal_note_id`
- Unique constraint on `[electronic_document_id, internal_note_id]`

#### 3. `ap_billing_electronic_documents` (Modified)
Added field for consolidation type.

| New Column | Type | Description |
|------------|------|-------------|
| consolidation_type | string(50) nullable | Type of consolidation: 'work_orders', etc. NULL for regular invoices |

**Index:**
- `consolidation_type`

## Models

### ApInternalNote

```php
use App\Models\ap\facturacion\ApInternalNote;

// Create a new internal note
$internalNote = ApInternalNote::create([
    'work_order_id' => $workOrderId,
    'created_date' => now(), // Optional, defaults to now()
    // 'number' is auto-generated
]);

// Relationships
$internalNote->workOrder; // BelongsTo ApWorkOrder
$internalNote->electronicDocuments; // BelongsToMany ElectronicDocument

// Scopes
ApInternalNote::pending()->get();
ApInternalNote::invoiced()->get();

// Business methods
$internalNote->markAsInvoiced('2024-03-16'); // or null for today
$internalNote->isPending(); // boolean
$internalNote->isInvoiced(); // boolean

// Auto-generate next number
$nextNumber = ApInternalNote::generateNextNumber(); // Returns IN-00002, etc.
```

### ElectronicDocument (Updated)

```php
use App\Models\ap\facturacion\ElectronicDocument;

// Relationships
$invoice->internalNotes; // BelongsToMany ApInternalNote

// Scopes
ElectronicDocument::consolidated()->get();
ElectronicDocument::consolidatedWorkOrders()->get();

// Business methods
$invoice->isConsolidated(); // boolean
$invoice->isWorkOrdersConsolidation(); // boolean

// Constants
ElectronicDocument::CONSOLIDATION_WORK_ORDERS; // 'work_orders'
```

### ApWorkOrder (Updated)

```php
use App\Models\ap\postventa\taller\ApWorkOrder;

// Relationships
$workOrder->internalNote; // HasOne ApInternalNote
```

## Usage Examples

### 1. Create Internal Note for a Work Order

```php
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\ApMasters;

// Create internal note
$internalNote = ApInternalNote::create([
    'work_order_id' => $workOrder->id,
    'created_date' => now(),
]);

// Close work order with internal note
$workOrder->update([
    'status_id' => ApMasters::CLOSED_WORK_ORDER_ID,
]);
```

### 2. Get Pending Internal Notes

```php
use App\Models\ap\facturacion\ApInternalNote;

// Get all pending notes
$pendingNotes = ApInternalNote::pending()
    ->with('workOrder')
    ->orderBy('created_date', 'desc')
    ->get();

// Filter by date range
$notesInPeriod = ApInternalNote::pending()
    ->whereBetween('created_date', [$startDate, $endDate])
    ->get();
```

### 3. Create Consolidated Invoice

```php
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\facturacion\ApInternalNote;

// Get internal notes to consolidate
$internalNotesIds = [1, 2, 3, 4];
$internalNotes = ApInternalNote::whereIn('id', $internalNotesIds)
    ->pending()
    ->get();

// Calculate total amount
$totalAmount = $internalNotes->sum(function ($note) {
    return $note->workOrder->final_amount;
});

// Create consolidated invoice
$invoice = ElectronicDocument::create([
    'sunat_concept_document_type_id' => ElectronicDocument::TYPE_FACTURA,
    'consolidation_type' => ElectronicDocument::CONSOLIDATION_WORK_ORDERS,
    'total' => $totalAmount,
    // ... other invoice fields
]);

// Attach internal notes to invoice
$invoice->internalNotes()->attach($internalNotesIds);

// Mark internal notes as invoiced
foreach ($internalNotes as $note) {
    $note->markAsInvoiced();
}
```

### 4. Query Consolidated Invoices

```php
use App\Models\ap\facturacion\ElectronicDocument;

// Get all consolidated invoices
$consolidatedInvoices = ElectronicDocument::consolidated()
    ->with('internalNotes.workOrder')
    ->get();

// Get only work orders consolidations
$workOrderConsolidations = ElectronicDocument::consolidatedWorkOrders()
    ->get();

// Get invoice with its internal notes
$invoice = ElectronicDocument::with('internalNotes.workOrder')
    ->find($invoiceId);

foreach ($invoice->internalNotes as $note) {
    echo "Note: {$note->number}, WO: {$note->workOrder->correlative}\n";
}
```

### 5. Filter by Consolidation Type

```php
use App\Models\ap\facturacion\ElectronicDocument;

// Regular invoices only
$regularInvoices = ElectronicDocument::whereNull('consolidation_type')
    ->get();

// Consolidated invoices only
$consolidatedInvoices = ElectronicDocument::whereNotNull('consolidation_type')
    ->get();
```

## Business Flow

### Regular Flow (Before)
1. Work order is completed (labours + parts)
2. Invoice is created for the work order
3. Work order status changes to CLOSED

### New Flow (With Internal Notes)
1. Work order is completed (labours + parts)
2. Internal note is created with auto-generated number (IN-00001, IN-00002, etc.)
3. Work order status changes to CLOSED immediately
4. Internal note status is 'pending'
5. At month end (or any period):
   - Select multiple pending internal notes
   - Create one consolidated invoice
   - Attach internal notes to the invoice
   - Mark internal notes as 'invoiced' with closed_date
   - Invoice total = sum of all work orders

## Filters

### ApInternalNote Filters
```php
const filters = [
    'search' => ['number'],
    'number' => '=',
    'work_order_id' => '=',
    'status' => '=',
    'created_date' => 'date_between',
    'closed_date' => 'date_between',
];
```

### ElectronicDocument Filters (Added)
```php
const filters = [
    // ... existing filters
    'consolidation_type' => '=',
];
```

## API Example

```php
// Controller example
public function createConsolidatedInvoice(Request $request)
{
    $validated = $request->validate([
        'internal_note_ids' => 'required|array',
        'internal_note_ids.*' => 'exists:ap_internal_notes,id',
        'client_id' => 'required|exists:ap_business_partners,id',
        // ... other invoice fields
    ]);

    DB::beginTransaction();
    try {
        // Get internal notes
        $internalNotes = ApInternalNote::whereIn('id', $validated['internal_note_ids'])
            ->pending()
            ->with('workOrder')
            ->get();

        if ($internalNotes->isEmpty()) {
            return response()->json(['error' => 'No pending internal notes found'], 400);
        }

        // Calculate totals
        $subtotal = $internalNotes->sum(fn($note) => $note->workOrder->subtotal);
        $taxAmount = $internalNotes->sum(fn($note) => $note->workOrder->tax_amount);
        $total = $internalNotes->sum(fn($note) => $note->workOrder->final_amount);

        // Create invoice
        $invoice = ElectronicDocument::create([
            'sunat_concept_document_type_id' => ElectronicDocument::TYPE_FACTURA,
            'consolidation_type' => ElectronicDocument::CONSOLIDATION_WORK_ORDERS,
            'client_id' => $validated['client_id'],
            'total_gravada' => $subtotal,
            'total_igv' => $taxAmount,
            'total' => $total,
            'fecha_de_emision' => now(),
            // ... other fields
        ]);

        // Attach internal notes
        $invoice->internalNotes()->attach($validated['internal_note_ids']);

        // Mark notes as invoiced
        foreach ($internalNotes as $note) {
            $note->markAsInvoiced();
        }

        DB::commit();

        return response()->json([
            'invoice' => $invoice->load('internalNotes.workOrder'),
            'message' => 'Consolidated invoice created successfully',
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

## Migrations

To run the migrations:

```bash
php artisan migrate
```

The migrations will create:
1. `ap_internal_notes` table
2. `electronic_document_internal_notes` pivot table
3. Add `consolidation_type` column to `ap_billing_electronic_documents` table

## Constants Reference

### ApInternalNote
- `STATUS_PENDING = 'pending'`
- `STATUS_INVOICED = 'invoiced'`

### ElectronicDocument
- `CONSOLIDATION_WORK_ORDERS = 'work_orders'`

## Best Practices

1. **Always use transactions** when creating consolidated invoices
2. **Validate that internal notes are pending** before consolidating
3. **Calculate totals from work orders**, not from hardcoded values
4. **Use scopes** for cleaner queries (`pending()`, `consolidated()`, etc.)
5. **Eager load relationships** to avoid N+1 problems
6. **Check work order status** before creating internal notes

## Notes

- Internal note numbers are auto-generated sequentially (IN-00001, IN-00002, ...)
- One work order can only have one internal note (1:1 relationship)
- One invoice can have many internal notes (many-to-many relationship)
- One internal note can only be in one invoice (validated by business logic)
- Consolidation type is nullable to maintain backward compatibility
- All tables use soft deletes for audit trail