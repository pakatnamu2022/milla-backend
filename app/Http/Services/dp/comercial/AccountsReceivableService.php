<?php

namespace App\Http\Services\dp\comercial;

use App\Http\Resources\dp\comercial\AccountReceivableCommentResource;
use App\Http\Resources\dp\comercial\AccountReceivableResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\EmailService;
use App\Models\dp\comercial\AccountReceivable;
use App\Models\dp\comercial\AccountReceivableComment;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategoryDetail;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use App\Exports\GeneralExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AccountsReceivableService extends BaseService
{
  // TODO: change to $worker->email2 ?? $worker->email in production
  private const GENERAL_EMAIL = 'dp-rep-cxc@depositopakatnamu.com';
//  private const GENERAL_EMAIL = 'hvaldiviezos@automotorespakatnamu.com';

  private const COMPANY_CONNECTION_MAP = [
    'deposito' => 'dbdp2',
  ];

  private const COMPANY_EMPRESA_ID_MAP = [
    'deposito' => 2,
  ];

  private const SEDE_EMAIL_MAP = [
    'deposito' => [
      'CAJAMARCA'  => 'dp-caj-cotweb@depositopakatnamu.com',
      'CHIMBOTE'   => 'dp-chb-cotweb@depositopakatnamu.com',
      'TRUJILLO'   => 'dp-tru-cotweb@depositopakatnamu.com',
      'PACASMAYO'  => 'dp-pac-cotweb@depositopakatnamu.com',
      'LAMBAYEQUE' => 'dp-cix-cotweb@depositopakatnamu.com',
      'LEGUIA'     => 'dp-cix-cotweb@depositopakatnamu.com',
      'CHICLAYO'   => 'dp-cix-cotweb@depositopakatnamu.com',
      'SULLANA'    => 'dp-sul-cotweb@depositopakatnamu.com',
      'PIURA'      => 'dp-sul-cotweb@depositopakatnamu.com',
    ],
  ];

  public function __construct(private EmailService $emailService)
  {
  }

  public function sync(string $company = 'deposito'): array
  {
    $connection = self::COMPANY_CONNECTION_MAP[$company]
      ?? throw new \Exception("Company '{$company}' has no configured connection.");

    $pdo = DB::connection($connection)->getPdo();
    $stmt = $pdo->prepare("EXEC GP_Reporte_Indicador_Comercial_CuentasPorCobrar '%'");
    $stmt->execute();

    $rows = [];
    do {
      if ($stmt->columnCount() > 0) {
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        break;
      }
    } while ($stmt->nextRowset());

    $sedeMap = $this->buildSedeMap();
    $batchAt = now()->toDateTimeString();

    $spDocNumbers = collect($rows)
      ->map(fn($r) => trim((string)($r->DocumentoNumero ?? '')))
      ->filter()
      ->values()
      ->all();

    // Detect reversals: documents locally marked PAGADO that reappear in SP
    AccountReceivable::where('company', $company)
      ->where('overdue_status', 'PAGADO')
      ->whereIn('document_number', $spDocNumbers)
      ->get()
      ->each(function (AccountReceivable $record) {
        AccountReceivableComment::create([
          'accounts_receivable_id' => $record->id,
          'sede_id'                => $record->sede_id,
          'user_id'                => null,
          'comment'                => 'Documento reingresado al SP: se detectó anulación de cobro. Verificar movimiento.',
        ]);
      });

    collect($rows)
      ->chunk(100)
      ->each(function ($chunk) use ($company, $sedeMap, $batchAt) {
        $records = $chunk->map(fn($row) => $this->mapRow((array)$row, $company, $sedeMap, $batchAt))->toArray();

        AccountReceivable::upsert(
          $records,
          ['company', 'document_number'],
          [
            'sede_id', 'seller', 'cashier',
            'client_id', 'client_name', 'client_id_real', 'client_name_real',
            'document_date', 'document_due_date', 'due_year', 'due_month',
            'overdue_days', 'overdue_status',
            'currency', 'exchange_rate', 'amount', 'balance', 'amount_pen', 'balance_pen',
            'branch', 'observations', 'collection_date',
            'synced_at', 'updated_at',
          ]
        );
      });

    // Mark records absent from SP as PAGADO
    AccountReceivable::where('company', $company)
      ->where('synced_at', '<', $batchAt)
      ->where('overdue_status', '!=', 'PAGADO')
      ->update(['overdue_status' => 'PAGADO', 'updated_at' => $batchAt]);

    Cache::forget(self::filterTreeCacheKey($company));
    Cache::forget(self::dashboardCacheKey($company));

    $total = count($rows);

    return [
      'message' => "Sincronización completa. Total registros procesados: {$total}.",
      'synced'  => $total,
    ];
  }

  public function list(Request $request)
  {
    $userSedeIds = $this->getUserSedeIds();

    $query = AccountReceivable::query()
      ->whereNot('overdue_status', 'PAGADO')
      ->with('sede')
      ->withCount('comments')
      ->when($userSedeIds !== null, fn($q) => $q->whereIn('sede_id', $userSedeIds));

    $summaryBase = $this->applyFilters(
      AccountReceivable::query()
        ->whereNot('overdue_status', 'PAGADO')
        ->when($userSedeIds !== null, fn($q) => $q->whereIn('sede_id', $userSedeIds)),
      $request,
      AccountReceivable::filters,
    );

    $agg = (clone $summaryBase)->selectRaw('
      COUNT(*) as total_documents,
      SUM(balance_pen) as total_balance_pen,
      SUM(CASE WHEN overdue_days > 0 THEN balance_pen ELSE 0 END) as overdue_balance_pen,
      SUM(CASE WHEN overdue_days <= 0 THEN balance_pen ELSE 0 END) as current_balance_pen
    ')->first();

    $extra = [
      'summary' => [
        'total_documents'     => (int)($agg->total_documents ?? 0),
        'total_balance_pen'   => $this->pen($agg->total_balance_pen),
        'overdue_balance_pen' => $this->pen($agg->overdue_balance_pen),
        'current_balance_pen' => $this->pen($agg->current_balance_pen),
        'breakdown'           => $this->buildBreakdown(clone $summaryBase, $request),
      ],
    ];

    return $this->getFilteredResults(
      $query,
      $request,
      AccountReceivable::filters,
      AccountReceivable::sorts,
      AccountReceivableResource::class,
      [],
      $extra,
    );
  }

  public function show(int $id): AccountReceivableResource
  {
    $record = AccountReceivable::with(['sede', 'comments.user', 'comments.sede'])->findOrFail($id);

    return new AccountReceivableResource($record);
  }

  public static function filterTreeCacheKey(string $company): string
  {
    return "accounts_receivable:filter_tree:{$company}";
  }

  public function filterTree(string $company = 'deposito'): array
  {
    $userSedeIds = $this->getUserSedeIds();

    if ($userSedeIds !== null) {
      return $this->buildFilterTree($company, $userSedeIds);
    }

    return Cache::rememberForever(self::filterTreeCacheKey($company), fn() => $this->buildFilterTree($company, null));
  }

  private function buildFilterTree(string $company, ?array $sedeIds): array
  {
    $rows = AccountReceivable::query()
      ->select('sede_id', 'overdue_status', 'due_year')
      ->whereNot('overdue_status', 'PAGADO')
      ->with('sede:id,suc_abrev,localidad')
      ->where('company', $company)
      ->whereNotNull('sede_id')
      ->whereNotNull('overdue_status')
      ->whereNotNull('due_year')
      ->when($sedeIds !== null, fn($q) => $q->whereIn('sede_id', $sedeIds))
      ->distinct()
      ->orderBy('sede_id')
      ->orderBy('overdue_status')
      ->orderByDesc('due_year')
      ->get();

    $tree = [];

    foreach ($rows as $row) {
      $sedeId = $row->sede_id;

      if (!isset($tree[$sedeId])) {
        $tree[$sedeId] = [
          'sede_id'   => $sedeId,
          'sede_name' => $row->sede?->suc_abrev ?? $row->sede?->localidad ?? "Sede {$sedeId}",
          'statuses'  => [],
        ];
      }

      $status = $row->overdue_status;

      if (!isset($tree[$sedeId]['statuses'][$status])) {
        $tree[$sedeId]['statuses'][$status] = [
          'status' => $status,
          'years'  => [],
        ];
      }

      $tree[$sedeId]['statuses'][$status]['years'][] = $row->due_year;
    }

    return array_values(array_map(function ($sede) {
      $sede['statuses'] = array_values($sede['statuses']);
      return $sede;
    }, $tree));
  }

  public static function dashboardCacheKey(string $company): string
  {
    return "accounts_receivable:dashboard:{$company}";
  }

  public function dashboard(string $company = 'deposito'): array
  {
    return Cache::rememberForever(self::dashboardCacheKey($company), fn() => $this->buildDashboard($company));
  }

  public function storeComment(int $id, array $data): AccountReceivableCommentResource
  {
    $record = AccountReceivable::findOrFail($id);

    $comment = AccountReceivableComment::create([
      'accounts_receivable_id' => $record->id,
      'sede_id'                => $record->sede_id,
      'user_id'                => auth()->id(),
      'comment'                => $data['comment'],
    ]);

    $comment->load(['user', 'sede']);

    return new AccountReceivableCommentResource($comment);
  }

  public function updateComment(int $commentId, array $data): AccountReceivableCommentResource
  {
    $comment = AccountReceivableComment::findOrFail($commentId);

    if (!$comment->created_at->isToday()) {
      throw new \Exception('Solo se pueden editar comentarios del día de hoy.');
    }

    $comment->update(['comment' => $data['comment']]);
    $comment->load(['user', 'sede']);

    return new AccountReceivableCommentResource($comment);
  }

  public function destroyComment(int $commentId): array
  {
    $comment = AccountReceivableComment::findOrFail($commentId);

    if (!$comment->created_at->isToday()) {
      throw new \Exception('Solo se pueden eliminar comentarios del día de hoy.');
    }

    $comment->delete();

    return ['message' => 'Comentario eliminado correctamente.'];
  }

  public function sendGlobalExcel(string $company = 'deposito'): array
  {
    return $this->buildAndSendGlobalExcel($company);
  }

  public function downloadGlobalExcel(string $company = 'deposito'): array
  {
    ['content' => $content, 'filename' => $filename] = $this->buildGlobalExcelContent($company);

    return ['content' => $content, 'filename' => $filename];
  }

  public function sendSedeReports(string $company = 'deposito'): array
  {
    return $this->executeSedeReports(
      $company,
      fn($q) => $q->where('balance_pen', '>', 0)->where('overdue_days', '<=', 0)->whereNot('overdue_status', 'PAGADO')->orderBy('overdue_days'),
      'Cuentas por Cobrar',
      'CxC'
    );
  }

  public function sendDueSedeReports(string $company = 'deposito'): array
  {
    return $this->executeSedeReports(
      $company,
      fn($q) => $q->where('balance_pen', '>', 0)->whereBetween('overdue_days', [-2, 0])->orderBy('overdue_days'),
      'Cuentas por Cobrar — Vencen en los próximos 2 días',
      'CxC_PorVencer'
    );
  }

  private function executeSedeReports(string $company, callable $queryModifier, string $subjectPrefix, string $filePrefix): array
  {
    $this->sync($company);

    $recipients = $this->resolveRecipients($company);

    if (empty($recipients)) {
      return ['sent' => 0, 'skipped' => 0, 'message' => 'No se encontraron destinatarios para la empresa.'];
    }

    $sent = 0;
    $skipped = 0;

    foreach ($recipients as $sedeId => $group) {
      $sede = $group['sede'];
      $sedeAbrev = $sede?->suc_abrev ?? "Sede {$sedeId}";
      $sedeFullName = $sede?->localidad ?: $sedeAbrev;

      $records = $queryModifier(
        AccountReceivable::where('company', $company)->where('sede_id', $sedeId)->whereNot('overdue_status', 'PAGADO')
      )->get();

      if ($records->isEmpty()) {
        $skipped++;
        continue;
      }

      $summary = [
        'total_documents'     => $records->count(),
        'total_balance_pen'   => $this->pen($records->sum('balance_pen')),
        'overdue_balance_pen' => $this->pen($records->sum(fn($r) => ($r->overdue_days ?? 0) > 0 ? $r->balance_pen : 0)),
        'current_balance_pen' => $this->pen($records->sum(fn($r) => ($r->overdue_days ?? 0) <= 0 ? $r->balance_pen : 0)),
      ];

      $recordsData = $records->map(fn($r) => [
        'client_name'       => $r->client_name,
        'client_id'         => $r->client_id,
        'document_number'   => $r->document_number,
        'document_date'     => $r->document_date?->format('d/m/Y'),
        'document_due_date' => $r->document_due_date?->format('d/m/Y'),
        'overdue_days'      => $r->overdue_days,
        'overdue_status'    => $r->overdue_status,
        'currency'          => $r->currency,
        'balance'           => (float)$r->balance,
        'balance_pen'       => $this->pen($r->balance_pen),
        'seller'            => $r->seller,
      ])->toArray();

      $emailData = [
        'sede_name'   => $sedeFullName,
        'sede_abrev'  => $sedeAbrev,
        'company'     => $company,
        'report_date' => now()->format('d/m/Y H:i'),
        'summary'     => $summary,
        'records'     => $recordsData,
      ];

      $pdf = Pdf::loadView('pdf.accounts-receivable-sede-report', $emailData)->setPaper('a4', 'landscape');
      $pdfPath = tempnam(sys_get_temp_dir(), 'ar_report_') . '.pdf';
      file_put_contents($pdfPath, $pdf->output());

      $pdfFileName = "{$filePrefix}_{$sedeAbrev}_{$company}_" . now()->format('Ymd') . '.pdf';

      foreach ($group['entries'] as $entry) {
        $this->emailService->send([
          'to'          => $entry['email'],
          'subject'     => "{$subjectPrefix} — {$sedeFullName} | {$summary['total_documents']} docs · S/ " . number_format($summary['total_balance_pen'], 2) . ' | ' . now()->format('d/m/Y'),
          'template'    => 'emails.accounts-receivable-sede-report',
          'data'        => array_merge($emailData, ['worker_name' => $entry['name'] ?? "Equipo {$sedeFullName}"]),
          'attachments' => [['path' => $pdfPath, 'name' => $pdfFileName, 'mime' => 'application/pdf']],
        ]);
        $sent++;
      }

      @unlink($pdfPath);
    }

    $this->buildAndSendGlobalExcel($company);

    return [
      'sent'    => $sent,
      'skipped' => $skipped,
      'message' => "Se enviaron {$sent} correo(s). {$skipped} sede(s) sin documentos.",
    ];
  }

  private function buildGlobalExcelContent(string $company): array
  {
    $model = new AccountReceivable();

    $records = AccountReceivable::where('company', $company)
      ->whereIn('overdue_status', ['VENCIDO', 'POR VENCER'])
      ->with($model->getReportRelations())
      ->orderBy('overdue_status')
      ->orderByDesc('overdue_days')
      ->get();

    $rows = $records->map(function ($r) {
      $latest = $r->comments->first();
      $lastComment = $latest
        ? ($latest->created_at?->format('d/m/Y') . ': ' . $latest->comment)
        : '';

      return [
        'sede'              => $r->sede?->suc_abrev ?? $r->sede?->localidad ?? '',
        'seller'            => $r->seller,
        'cashier'           => $r->cashier,
        'document_number'   => $r->document_number,
        'client_id'         => $r->client_id,
        'client_name'       => $r->client_name,
        'client_id_real'    => $r->client_id_real,
        'client_name_real'  => $r->client_name_real,
        'document_date'     => $r->document_date?->format('d/m/Y'),
        'document_due_date' => $r->document_due_date?->format('d/m/Y'),
        'due_year'          => $r->due_year,
        'due_month'         => $r->due_month,
        'overdue_days'      => (int)($r->overdue_days ?? 0),
        'overdue_status'    => $r->overdue_status,
        'currency'          => $r->currency,
        'exchange_rate'     => (float)$r->exchange_rate,
        'amount'            => (float)$r->amount,
        'balance'           => (float)$r->balance,
        'amount_pen'        => (float)$r->amount_pen,
        'balance_pen'       => (float)$r->balance_pen,
        'collection_date'   => $r->collection_date?->format('d/m/Y'),
        'last_comment'      => $lastComment,
      ];
    })->toArray();

    $export = new GeneralExport(
      $rows,
      $model->getReportableColumns(),
      'CxC Vencidas y Por Vencer',
      $model->getReportStyles(),
      $model->getReportColorRules(),
    );

    return [
      'content'  => Excel::raw($export, 'Xlsx'),
      'filename' => 'CxC_VENCIDO_POR_VENCER_' . ucfirst($company) . '_' . now()->format('Ymd_His') . '.xlsx',
      'records'  => $records,
    ];
  }

  private function buildAndSendGlobalExcel(string $company): array
  {
    ['content' => $content, 'filename' => $fileName, 'records' => $records] = $this->buildGlobalExcelContent($company);

    if ($records->isEmpty()) {
      return ['sent' => 0, 'records' => 0, 'message' => 'Sin registros VENCIDO/POR VENCER.'];
    }

    $tmpPath = tempnam(sys_get_temp_dir(), 'ar_excel_') . '.xlsx';
    file_put_contents($tmpPath, $content);

    $total = $records->count();
    $totalBalancePen = $this->pen($records->sum('balance_pen'));
    $overdueBalancePen = $this->pen($records->where('overdue_days', '>', 0)->sum('balance_pen'));
    $currentBalancePen = $this->pen($records->where('overdue_days', '<=', 0)->sum('balance_pen'));

    $this->emailService->send([
      'to'          => self::GENERAL_EMAIL,
      'cc'          => ['fbancess@depositopakatnamu.com', 'xlunan@grupopakatnamu.com'],
      'subject'     => 'Reporte Final ' . now()->format('d/m/Y') . ' — Cuentas por Cobrar Vencidas y por Vencer',
      'template'    => 'emails.accounts-receivable-sede-report',
      'data'        => [
        'sede_name'   => 'Consolidado ' . ucfirst($company),
        'sede_abrev'  => strtoupper($company),
        'company'     => $company,
        'report_date' => now()->format('d/m/Y H:i'),
        'summary'     => [
          'total_documents'     => $total,
          'total_balance_pen'   => $totalBalancePen,
          'overdue_balance_pen' => $overdueBalancePen,
          'current_balance_pen' => $currentBalancePen,
        ],
        'records'     => [],
        'worker_name' => 'estimados',
        'description' => 'Se les envía estatus de cobranza según el seguimiento de hoy. Su apoyo con el cumplimiento de los compromisos acordados.'
      ],
      'attachments' => [[
        'path' => $tmpPath,
        'name' => $fileName,
        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      ]],
    ]);

    @unlink($tmpPath);

    return ['sent' => 1, 'records' => $total, 'message' => 'Excel consolidado enviado a ' . self::GENERAL_EMAIL];
  }

  private function resolveRecipients(string $company): array
  {
    if (isset(self::SEDE_EMAIL_MAP[$company])) {
      $emailMap = self::SEDE_EMAIL_MAP[$company];

      $empresaId = self::COMPANY_EMPRESA_ID_MAP[$company] ?? null;

      $result = [];
      Sede::whereIn('suc_abrev', array_keys($emailMap))
        ->when($empresaId, fn($q) => $q->where(fn($q2) => $q2->where('empresa_id', $empresaId)->orWhereNull('empresa_id')))
        ->get()
        ->each(function (Sede $sede) use ($emailMap, &$result) {
          $email = $emailMap[strtoupper($sede->suc_abrev)] ?? null;
          if (!$email) return;

          $result[$sede->id] = [
            'sede'    => $sede,
            'entries' => [['email' => $email, 'name' => null]],
          ];
        });

      return $result;
    }

    $positionIds = HierarchicalCategoryDetail::where('hierarchical_category_id', 13)->pluck('position_id');

    if ($positionIds->isEmpty()) {
      return [];
    }

    $workers = Worker::whereIn('cargo_id', $positionIds)
      ->whereNotNull('sede_id')
      ->with('sede:id,localidad,suc_abrev')
      ->get();

    $result = [];
    foreach ($workers->groupBy('sede_id') as $sedeId => $sedeWorkers) {
      $result[$sedeId] = [
        'sede'    => $sedeWorkers->first()->sede,
        'entries' => $sedeWorkers->map(fn($w) => [
          'email' => self::GENERAL_EMAIL,
          'name'  => $w->nombre_completo,
        ])->toArray(),
      ];
    }

    return $result;
  }

  // ─── Private helpers ────────────────────────────────────────────────────────

  /**
   * Returns null if the user can view all branches, or an array of allowed sede IDs.
   */
  private function getUserSedeIds(): ?array
  {
    $user = auth()->user();

    if (!$user || $user->hasPermission('accounts_receivable.viewBranches')) {
      return null;
    }

    return $user->sedes()->pluck('config_sede.id')->toArray();
  }

  private function buildBreakdown($query, Request $request): array
  {
    if ($request->filled('due_year')) {
      $groupBy = 'due_year';
      $rows = (clone $query)
        ->selectRaw('due_year as label, COUNT(*) as total_documents, SUM(balance_pen) as total_balance_pen, SUM(CASE WHEN overdue_days > 0 THEN balance_pen ELSE 0 END) as overdue_balance_pen, SUM(CASE WHEN overdue_days <= 0 THEN balance_pen ELSE 0 END) as current_balance_pen')
        ->whereNotNull('due_year')
        ->groupBy('due_year')
        ->orderByDesc('due_year')
        ->get();

      return $this->formatBreakdown($rows, $groupBy);
    }

    if ($request->filled('overdue_status')) {
      $groupBy = 'overdue_status';
      $rows = (clone $query)
        ->selectRaw('overdue_status as label, COUNT(*) as total_documents, SUM(balance_pen) as total_balance_pen, SUM(CASE WHEN overdue_days > 0 THEN balance_pen ELSE 0 END) as overdue_balance_pen, SUM(CASE WHEN overdue_days <= 0 THEN balance_pen ELSE 0 END) as current_balance_pen')
        ->whereNotNull('overdue_status')
        ->groupBy('overdue_status')
        ->orderByDesc('total_balance_pen')
        ->get();

      return $this->formatBreakdown($rows, $groupBy);
    }

    // sede_id active or no filters — break down by sede
    return $this->breakdownBySede(clone $query);
  }

  private function breakdownBySede($query): array
  {
    $rows = $query
      ->selectRaw('sede_id, COUNT(*) as total_documents, SUM(balance_pen) as total_balance_pen, SUM(CASE WHEN overdue_days > 0 THEN balance_pen ELSE 0 END) as overdue_balance_pen, SUM(CASE WHEN overdue_days <= 0 THEN balance_pen ELSE 0 END) as current_balance_pen')
      ->whereNotNull('sede_id')
      ->groupBy('sede_id')
      ->orderByDesc('total_balance_pen')
      ->get();

    $sedeIds = $rows->pluck('sede_id')->filter()->unique()->toArray();
    $sedeMap = Sede::select('id', 'suc_abrev', 'localidad')
      ->whereIn('id', $sedeIds)
      ->get()
      ->keyBy('id');

    return $rows->map(function ($row) use ($sedeMap) {
      $sede = $sedeMap[$row->sede_id] ?? null;
      $label = $sede?->suc_abrev ?? $sede?->localidad ?? "Sede {$row->sede_id}";

      return [
        'label'               => $label,
        'sede_id'             => $row->sede_id,
        'total_documents'     => (int)($row->total_documents ?? 0),
        'total_balance_pen'   => $this->pen($row->total_balance_pen),
        'overdue_balance_pen' => $this->pen($row->overdue_balance_pen),
        'current_balance_pen' => $this->pen($row->current_balance_pen),
      ];
    })->values()->toArray();
  }

  private function formatBreakdown($rows, string $groupBy): array
  {
    return $rows->map(fn($row) => [
      'label'               => (string)($row->label ?? ''),
      'total_documents'     => (int)($row->total_documents ?? 0),
      'total_balance_pen'   => $this->pen($row->total_balance_pen),
      'overdue_balance_pen' => $this->pen($row->overdue_balance_pen),
      'current_balance_pen' => $this->pen($row->current_balance_pen),
    ])->values()->toArray();
  }

  private function buildDashboard(string $company): array
  {
    $base = fn() => AccountReceivable::where('company', $company)
      ->whereNot('overdue_status', 'PAGADO');

    // KPIs
    $summary = $base()->selectRaw('
      COUNT(*) as total_documents,
      SUM(amount_pen) as total_amount_pen,
      SUM(balance_pen) as total_balance_pen,
      SUM(CASE WHEN overdue_days > 0 THEN balance_pen ELSE 0 END) as overdue_balance_pen,
      SUM(CASE WHEN overdue_days <= 0 THEN balance_pen ELSE 0 END) as current_balance_pen
    ')->first();

    // By overdue status
    $byStatus = $base()
      ->selectRaw('overdue_status as label, SUM(balance_pen) as value')
      ->whereNotNull('overdue_status')
      ->groupBy('overdue_status')
      ->orderByDesc('value')
      ->get();

    // By sede
    $bySede = $base()
      ->selectRaw('sede_id, SUM(balance_pen) as value')
      ->with('sede:id,suc_abrev,localidad')
      ->whereNotNull('sede_id')
      ->groupBy('sede_id')
      ->orderByDesc('value')
      ->get();

    // By month (ordered chronologically)
    $byMonth = $base()
      ->selectRaw('due_year, due_month, MONTH(document_due_date) as month_num, SUM(balance_pen) as value')
      ->whereNotNull('due_year')
      ->whereNotNull('due_month')
      ->groupByRaw('due_year, due_month, MONTH(document_due_date)')
      ->orderBy('due_year')
      ->orderByRaw('MONTH(document_due_date)')
      ->get();

    // Aging buckets
    $aging = $base()->selectRaw('
      SUM(CASE WHEN overdue_days BETWEEN 1  AND 30  THEN balance_pen ELSE 0 END) as d_1_30,
      SUM(CASE WHEN overdue_days BETWEEN 31 AND 60  THEN balance_pen ELSE 0 END) as d_31_60,
      SUM(CASE WHEN overdue_days BETWEEN 61 AND 90  THEN balance_pen ELSE 0 END) as d_61_90,
      SUM(CASE WHEN overdue_days BETWEEN 91 AND 120 THEN balance_pen ELSE 0 END) as d_91_120,
      SUM(CASE WHEN overdue_days > 120              THEN balance_pen ELSE 0 END) as d_120_plus
    ')->first();

    // Top 10 sellers by balance
    $topSellers = $base()
      ->selectRaw('seller as label, SUM(balance_pen) as value')
      ->whereNotNull('seller')
      ->groupBy('seller')
      ->orderByDesc('value')
      ->limit(10)
      ->get();

    $syncedAt = $base()->max('synced_at');

    return [
      'synced_at' => $syncedAt,
      'summary'   => [
        'total_documents'     => (int)($summary->total_documents ?? 0),
        'total_amount_pen'    => $this->pen($summary->total_amount_pen),
        'total_balance_pen'   => $this->pen($summary->total_balance_pen),
        'overdue_balance_pen' => $this->pen($summary->overdue_balance_pen),
        'current_balance_pen' => $this->pen($summary->current_balance_pen),
      ],
      'charts'    => [
        [
          'id'       => 'balance_by_status',
          'title'    => 'Saldo por Estado de Vencimiento',
          'type'     => 'pie',
          'labels'   => $byStatus->pluck('label')->values()->toArray(),
          'datasets' => [[
            'label' => 'Saldo (S/)',
            'data'  => $byStatus->pluck('value')->map(fn($v) => $this->pen($v))->values()->toArray(),
          ]],
        ],
        [
          'id'       => 'balance_by_sede',
          'title'    => 'Saldo por Sede',
          'type'     => 'bar',
          'labels'   => $bySede->map(fn($r) => $r->sede?->suc_abrev ?? $r->sede?->localidad ?? "Sede {$r->sede_id}")->values()->toArray(),
          'datasets' => [[
            'label' => 'Saldo (S/)',
            'data'  => $bySede->pluck('value')->map(fn($v) => $this->pen($v))->values()->toArray(),
          ]],
        ],
        [
          'id'       => 'balance_by_month',
          'title'    => 'Saldo por Mes de Vencimiento',
          'type'     => 'line',
          'labels'   => $byMonth->map(fn($r) => "{$r->due_month} {$r->due_year}")->values()->toArray(),
          'datasets' => [[
            'label' => 'Saldo (S/)',
            'data'  => $byMonth->pluck('value')->map(fn($v) => $this->pen($v))->values()->toArray(),
          ]],
        ],
        [
          'id'       => 'aging',
          'title'    => 'Antigüedad de Cartera Vencida',
          'type'     => 'bar',
          'labels'   => ['1-30 días', '31-60 días', '61-90 días', '91-120 días', '120+ días'],
          'datasets' => [[
            'label' => 'Saldo (S/)',
            'data'  => [
              $this->pen($aging->d_1_30),
              $this->pen($aging->d_31_60),
              $this->pen($aging->d_61_90),
              $this->pen($aging->d_91_120),
              $this->pen($aging->d_120_plus),
            ],
          ]],
        ],
        [
          'id'       => 'top_sellers',
          'title'    => 'Top 10 Vendedores por Saldo',
          'type'     => 'bar',
          'labels'   => $topSellers->pluck('label')->values()->toArray(),
          'datasets' => [[
            'label' => 'Saldo (S/)',
            'data'  => $topSellers->pluck('value')->map(fn($v) => $this->pen($v))->values()->toArray(),
          ]],
        ],
      ],
    ];
  }

  private function pen(mixed $value): float
  {
    return round((float)($value ?? 0), 2);
  }

  private function mapRow(array $row, string $company, array $sedeMap, string $now): array
  {
    $branch = trim($row['Sucursal'] ?? '');
    $abreviatura = trim($row['Abreviatura'] ?? '');
    $currency = strtoupper(trim($row['Moneda'] ?? 'PEN'));
    $amount = (float)($row['DocumentoImporte'] ?? 0);
    $balance = (float)($row['DocumentoDeuda'] ?? 0);
    $rate = (float)($row['DocumentoTasaCambio'] ?? 1) ?: 1;

    $amountPen = $currency === 'PEN' ? $amount : $amount * $rate;
    $balancePen = $currency === 'PEN' ? $balance : $balance * $rate;

    return [
      'company'           => $company,
      'sede_id'           => $this->resolveSede($abreviatura, $sedeMap),
      'seller'            => $row['Vendedor'] ?? null,
      'cashier'           => $row['Caja'] ?? null,
      'document_number'   => trim($row['DocumentoNumero'] ?? ''),
      'client_id'         => $row['ClienteId'] ?? null,
      'client_name'       => $row['ClienteNombre'] ?? null,
      'client_id_real'    => $row['ClienteIdReal'] ?: null,
      'client_name_real'  => $row['ClienteNombreReal'] ?: null,
      'document_date'     => $this->parseDate($row['DocumentoFecha'] ?? null),
      'document_due_date' => $this->parseDate($row['DocumentoVencimiento'] ?? null),
      'due_year'          => $row['Anho_Vencimiento'] ?? null,
      'due_month'         => $row['Mes_Vencimiento'] ?? null,
      'overdue_days'      => $row['DiasVencidos'] ?? null,
      'overdue_status'    => $row['Estado_Vencido'] ?? null,
      'currency'          => $row['Moneda'] ?? null,
      'exchange_rate'     => $row['DocumentoTasaCambio'] ?? null,
      'amount'            => $amount,
      'balance'           => $balance,
      'amount_pen'        => $amountPen,
      'balance_pen'       => $balancePen,
      'branch'            => $branch ?: null,
      'observations'      => $row['Observaciones'] ?: null,
      'collection_date'   => $this->parseDate(trim($row['CobroFecha'] ?? '')),
      'synced_at'         => $now,
      'created_at'        => $now,
      'updated_at'        => $now,
    ];
  }

  private function buildSedeMap(): array
  {
    return Sede::select('id', 'suc_abrev')
      ->where('status_deleted', 1)
      ->get()
      ->mapWithKeys(fn($s) => [strtoupper(trim($s->suc_abrev ?? '')) => $s->id])
      ->toArray();
  }

  private function resolveSede(string $abreviatura, array $sedeMap): ?int
  {
    if (empty($abreviatura)) {
      return null;
    }
    return $sedeMap[strtoupper($abreviatura)] ?? null;
  }

  private function parseDate(?string $value): ?string
  {
    if (empty($value) || trim($value) === '') {
      return null;
    }
    try {
      return \Carbon\Carbon::parse(trim($value))->toDateString();
    } catch (\Throwable) {
      return null;
    }
  }
}
