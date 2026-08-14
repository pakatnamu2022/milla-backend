<?php

namespace App\Exports\ap\postventa\taller;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use App\Models\gp\gestionsistema\UserSede;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConsolidadoOTsSheet implements
  FromCollection,
  WithHeadings,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected array $filters;

  public function __construct(array $filters)
  {
    $this->filters = $filters;
  }

  public function collection()
  {
    // Extract date range and sede_id from filters
    $startDate = null;
    $endDate = null;
    $sedeId = null;

    foreach ($this->filters as $filter) {
      if (($filter['column'] ?? null) === 'actual_end_datetime' && ($filter['operator'] ?? null) === 'date_between') {
        $startDate = $filter['value'][0] ?? null;
        $endDate = $filter['value'][1] ?? null;
      }
      if (($filter['column'] ?? null) === 'sede_id' && ($filter['operator'] ?? null) === '=') {
        $sedeId = $filter['value'] ?? null;
      }
    }

    // Get user sede IDs
    $userSedeIds = $this->getUserSedeIds();

    // Collect all work orders
    $workOrders = collect();

    // 1. Get work orders from electronic documents (SIMPLE and MASSIVE invoicing)
    $queryDocuments = ElectronicDocument::query()
      ->with([
        'workOrder.sede',
        'workOrder.plannings' => function ($query) {
          $query->where('status', 'completed')
            ->whereNotNull('worker_id')
            ->with('worker');
        },
        'internalNotes.workOrder.sede',
        'internalNotes.workOrder.plannings' => function ($query) {
          $query->where('status', 'completed')
            ->whereNotNull('worker_id')
            ->with('worker');
        }
      ])
      ->where('anulado', false)
      ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED])
      ->where('is_advance_payment', false) // Only final invoices
      ->where(function ($q) {
        $q->whereNotNull('work_order_id')
          ->orWhereHas('internalNotes', function ($subQ) {
            $subQ->where('status', 'invoiced');
          });
      });

    // Filter by user sedes
    if (!empty($userSedeIds)) {
      $queryDocuments->where(function ($q) use ($userSedeIds) {
        $q->whereHas('workOrder', function ($subQ) use ($userSedeIds) {
          $subQ->whereIn('sede_id', $userSedeIds);
        })->orWhereHas('internalNotes.workOrder', function ($subQ) use ($userSedeIds) {
          $subQ->whereIn('sede_id', $userSedeIds);
        });
      });
    }

    // Filter by fecha_de_emision (invoice date)
    if ($startDate && $endDate) {
      $queryDocuments->whereBetween('fecha_de_emision', [$startDate, $endDate]);
    }

    // Filter by sede if specified
    if ($sedeId) {
      $queryDocuments->where(function ($q) use ($sedeId) {
        $q->whereHas('workOrder', function ($subQ) use ($sedeId) {
          $subQ->where('sede_id', $sedeId);
        })->orWhereHas('internalNotes.workOrder', function ($subQ) use ($sedeId) {
          $subQ->where('sede_id', $sedeId);
        });
      });
    }

    $documents = $queryDocuments->get();

    // Extract work orders from documents
    foreach ($documents as $document) {
      // SIMPLE invoicing
      if ($document->workOrder) {
        $workOrders->push($document->workOrder);
      }

      // MASSIVE invoicing
      if ($document->internalNotes && $document->internalNotes->count() > 0) {
        foreach ($document->internalNotes as $internalNote) {
          if ($internalNote->workOrder) {
            $workOrders->push($internalNote->workOrder);
          }
        }
      }
    }

    // 2. Get work orders with internal note WITHOUT invoice
    $queryInternalNoteWorkOrders = ApWorkOrder::query()
      ->with([
        'sede',
        'plannings' => function ($query) {
          $query->where('status', 'completed')
            ->whereNotNull('worker_id')
            ->with('worker');
        },
        'internalNotes'
      ])
      ->where('status_id', ApMasters::CLOSED_WORK_ORDER_ID)
      ->whereHas('internalNotes', function ($q) {
        $q->whereNotNull('number');
      })
      ->whereHas('items', function ($q) {
        $q->whereHas('typePlanning', function ($subQ) {
          $subQ->whereIn('type_document', [
            TypePlanningWorkOrder::INTERNA_SC,
            TypePlanningWorkOrder::INTERNA_CC,
          ])
            ->whereNotIn('id', [
              TypePlanningWorkOrder::TYPE_PLANNING_DERCO_WARRANTY_ID,
              TypePlanningWorkOrder::TYPE_PLANNING_ODEBRECHT_MAINTENANCE,
            ]);
        });
      })
      ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
          ->from('ap_billing_electronic_documents')
          ->whereColumn('ap_billing_electronic_documents.work_order_id', 'ap_work_orders.id')
          ->where('ap_billing_electronic_documents.anulado', false);
      })
      ->whereDoesntHave('internalNotes', function ($q) {
        $q->whereHas('electronicDocuments');
      });

    // Filter by user sedes
    if (!empty($userSedeIds)) {
      $queryInternalNoteWorkOrders->whereIn('sede_id', $userSedeIds);
    }

    // Filter by sede if specified
    if ($sedeId) {
      $queryInternalNoteWorkOrders->where('sede_id', $sedeId);
    }

    // Filter by internal note created_date
    if ($startDate && $endDate) {
      $queryInternalNoteWorkOrders->whereHas('internalNotes', function ($q) use ($startDate, $endDate) {
        $q->whereBetween('created_date', [$startDate, $endDate]);
      });
    }

    $internalNoteWorkOrders = $queryInternalNoteWorkOrders->get();
    $workOrders = $workOrders->merge($internalNoteWorkOrders);

    // Remove duplicates by work order ID
    $workOrders = $workOrders->unique('id');

    // Build the collection
    $rows = collect();

    foreach ($workOrders as $wo) {
      // Count technicians with completed plannings
      $completedPlannings = $wo->plannings->filter(function ($planning) {
        return $planning->status === 'completed' && $planning->worker_id !== null;
      });

      $technicianCount = $completedPlannings->count();

      // Get technician names
      $technicians = $completedPlannings->map(function ($planning) {
        return $planning->worker ? $planning->worker->nombre_completo : 'Sin nombre';
      })->unique()->implode(', ');

      $considerada = $technicianCount > 0 ? 'SÍ' : 'NO';

      $rows->push([
        'ot_number' => $wo->correlative ?? '',
        'sede' => $wo->sede ? $wo->sede->abreviatura : 'SIN SEDE',
        'client_name' => $wo->client_name ?? '',
        'plate' => $wo->plate ?? '',
        'considerada' => $considerada,
        'technician_count' => $technicianCount,
        'technicians' => $technicians ?: 'Sin técnicos',
        'reason' => $technicianCount === 0 ? 'Sin técnicos asignados' : '',
      ]);
    }

    return $rows->sortBy([
      ['sede', 'asc'],
      ['ot_number', 'asc']
    ])->values();
  }

  public function headings(): array
  {
    return [
      'N° OT',
      'Sede',
      'Cliente',
      'Placa',
      'Considerada',
      'N° Técnicos',
      'Técnicos Asignados',
      'Motivo (si no se considera)',
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => [
        'font' => [
          'bold' => true,
          'color' => ['rgb' => 'FFFFFF'],
          'size' => 11,
        ],
        'fill' => [
          'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
          'startColor' => ['rgb' => '4472C4'],
        ],
        'alignment' => [
          'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
      ],
    ];
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Center align specific columns
        $sheet->getStyle('A:B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D:F')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Apply conditional formatting to "Considerada" column (E)
        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
          $consideradaValue = $sheet->getCell('E' . $row)->getValue();
          if ($consideradaValue === 'SÍ') {
            $sheet->getStyle('E' . $row)->applyFromArray([
              'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D4EDDA'], // Verde claro
              ],
              'font' => [
                'color' => ['rgb' => '155724'],
                'bold' => true,
              ],
            ]);
          } else {
            $sheet->getStyle('E' . $row)->applyFromArray([
              'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8D7DA'], // Rojo claro
              ],
              'font' => [
                'color' => ['rgb' => '721C24'],
                'bold' => true,
              ],
            ]);
          }
        }

        $sheet->setSelectedCells('A1');
      },
    ];
  }

  public function title(): string
  {
    return 'Consolidado de OTs';
  }

  private function getUserSedeIds(): array
  {
    $user = Auth::user();

    if (!$user) {
      return [];
    }

    return UserSede::where('user_id', $user->id)
      ->where('status', true)
      ->pluck('sede_id')
      ->toArray();
  }
}
