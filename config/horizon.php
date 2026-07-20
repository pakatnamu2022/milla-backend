<?php

use Illuminate\Support\Str;

return [

  /*
  |--------------------------------------------------------------------------
  | Horizon Name
  |--------------------------------------------------------------------------
  |
  | This name appears in notifications and in the Horizon UI. Unique names
  | can be useful while running multiple instances of Horizon within an
  | application, allowing you to identify the Horizon you're viewing.
  |
  */

  'name' => env('HORIZON_NAME'),

  /*
  |--------------------------------------------------------------------------
  | Horizon Domain
  |--------------------------------------------------------------------------
  |
  | This is the subdomain where Horizon will be accessible from. If this
  | setting is null, Horizon will reside under the same domain as the
  | application. Otherwise, this value will serve as the subdomain.
  |
  */

  'domain' => env('HORIZON_DOMAIN'),

  /*
  |--------------------------------------------------------------------------
  | Horizon Path
  |--------------------------------------------------------------------------
  |
  | This is the URI path where Horizon will be accessible from. Feel free
  | to change this path to anything you like. Note that the URI will not
  | affect the paths of its internal API that aren't exposed to users.
  |
  */

  'path' => env('HORIZON_PATH', 'horizon'),

  /*
  |--------------------------------------------------------------------------
  | Horizon Redis Connection
  |--------------------------------------------------------------------------
  |
  | This is the name of the Redis connection where Horizon will store the
  | meta information required for it to function. It includes the list
  | of supervisors, failed jobs, job metrics, and other information.
  |
  */

  'use' => 'default',

  /*
  |--------------------------------------------------------------------------
  | Horizon Redis Prefix
  |--------------------------------------------------------------------------
  |
  | This prefix will be used when storing all Horizon data in Redis. You
  | may modify the prefix when you are running multiple installations
  | of Horizon on the same server so that they don't have problems.
  |
  */

  'prefix' => env(
    'HORIZON_PREFIX',
    Str::slug(env('APP_NAME', 'laravel'), '_') . '_horizon:'
  ),

  /*
  |--------------------------------------------------------------------------
  | Horizon Route Middleware
  |--------------------------------------------------------------------------
  |
  | These middleware will get attached onto each Horizon route, giving you
  | the chance to add your own middleware to this list or change any of
  | the existing middleware. Or, you can simply stick with this list.
  |
  */

  'middleware' => ['web', \App\Http\Middleware\DocsBasicAuth::class],

  /*
  |--------------------------------------------------------------------------
  | Queue Wait Time Thresholds
  |--------------------------------------------------------------------------
  |
  | This option allows you to configure when the LongWaitDetected event
  | will be fired. Every connection / queue combination may have its
  | own, unique threshold (in seconds) before this event is fired.
  |
  */

  'waits' => [
    'redis:default' => 60,
  ],

  /*
  |--------------------------------------------------------------------------
  | Job Trimming Times
  |--------------------------------------------------------------------------
  |
  | Here you can configure for how long (in minutes) you desire Horizon to
  | persist the recent and failed jobs. Typically, recent jobs are kept
  | for one hour while all failed jobs are stored for an entire week.
  |
  */

  'trim' => [
    'recent'        => 60,
    'pending'       => 60,
    'completed'     => 60,
    'recent_failed' => 10080,
    'failed'        => 10080,
    'monitored'     => 10080,
  ],

  /*
  |--------------------------------------------------------------------------
  | Silenced Jobs
  |--------------------------------------------------------------------------
  |
  | Silencing a job will instruct Horizon to not place the job in the list
  | of completed jobs within the Horizon dashboard. This setting may be
  | used to fully remove any noisy jobs from the completed jobs list.
  |
  */

  'silenced' => [
    // App\Jobs\ExampleJob::class,
  ],

  'silenced_tags' => [
    // 'notifications',
  ],

  /*
  |--------------------------------------------------------------------------
  | Metrics
  |--------------------------------------------------------------------------
  |
  | Here you can configure how many snapshots should be kept to display in
  | the metrics graph. This will get used in combination with Horizon's
  | `horizon:snapshot` schedule to define how long to retain metrics.
  |
  */

  'metrics' => [
    'trim_snapshots' => [
      'job'   => 24,
      'queue' => 24,
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Fast Termination
  |--------------------------------------------------------------------------
  |
  | When this option is enabled, Horizon's "terminate" command will not
  | wait on all of the workers to terminate unless the --wait option
  | is provided. Fast termination can shorten deployment delay by
  | allowing a new instance of Horizon to start while the last
  | instance will continue to terminate each of its workers.
  |
  */

  'fast_termination' => false,

  /*
  |--------------------------------------------------------------------------
  | Memory Limit (MB)
  |--------------------------------------------------------------------------
  |
  | This value describes the maximum amount of memory the Horizon master
  | supervisor may consume before it is terminated and restarted. For
  | configuring these limits on your workers, see the next section.
  |
  */

  'memory_limit' => 64,

  /*
  |--------------------------------------------------------------------------
  | Queue Worker Configuration
  |--------------------------------------------------------------------------
  |
  | Here you may define the queue worker settings used by your application
  | in all environments. These supervisors and settings handle all your
  | queued jobs and will be provisioned by Horizon during deployment.
  |
  */

  'defaults' => [
    'supervisor-electronic-documents'        => [
      'connection'          => 'redis',
      'queue'               => ['electronic_documents'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 4,
      'maxProcesses'        => 6,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 90,
      'nice'                => 0,
    ],
    'supervisor-purchase-orders'             => [
      'connection'          => 'redis',
      'queue'               => ['purchase_orders'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 2,
      'maxProcesses'        => 4,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 90,
      'nice'                => 0,
    ],
    'supervisor-shipping-guides'             => [
      'connection'          => 'redis',
      'queue'               => ['shipping_guides'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 2,
      'maxProcesses'        => 4,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 90,
      'nice'                => 0,
    ],
    'supervisor-shipping-guides-sale'        => [
      'connection'          => 'redis',
      'queue'               => ['shipping_guides_sale'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 2,
      'maxProcesses'        => 6,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 300,
      'nice'                => 0,
    ],
    'supervisor-invoice-sync'                => [
      'connection'          => 'redis',
      'queue'               => ['invoice_sync'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 2,
      'maxProcesses'        => 4,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 90,
      'nice'                => 0,
    ],
    'supervisor-credit-note-sync'            => [
      'connection'          => 'redis',
      'queue'               => ['credit_note_sync'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 2,
      'maxProcesses'        => 4,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 90,
      'nice'                => 0,
    ],
    'supervisor-shipping-guide-sync'         => [
      'connection'          => 'redis',
      'queue'               => ['shipping_guide_sync'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 2,
      'maxProcesses'        => 4,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 90,
      'nice'                => 0,
    ],
    'supervisor-images-inspections'          => [
      'connection'          => 'redis',
      'queue'               => ['images-vehicle-inspections'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 2,
      'maxProcesses'        => 4,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 120,
      'nice'                => 0,
    ],
    'supervisor-inventory-adjustments'       => [
      'connection'          => 'redis',
      'queue'               => ['inventory_adjustments'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 1,
      'maxProcesses'        => 2,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 90,
      'nice'                => 0,
    ],
    'supervisor-establishments'              => [
      'connection'          => 'redis',
      'queue'               => ['establishments', 'update-establishments'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 1,
      'maxProcesses'        => 2,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 90,
      'nice'                => 0,
    ],
    'supervisor-mail'                        => [
      'connection'          => 'redis',
      'queue'               => ['mail'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 1,
      'maxProcesses'        => 3,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 60,
      'nice'                => 0,
    ],
    'supervisor-evaluation-dashboards'       => [
      'connection'          => 'redis',
      'queue'               => ['evaluation-dashboards'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 1,
      'maxProcesses'        => 2,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 90,
      'nice'                => 0,
    ],
    'supervisor-validate-documents'          => [
      'connection'          => 'redis',
      'queue'               => ['validate-potential-buyers-documents'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 1,
      'maxProcesses'        => 2,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 90,
      'nice'                => 0,
    ],
    'supervisor-fac-invoice-sync'            => [
      'connection'          => 'redis',
      'queue'               => ['fac_invoice_sync'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 2,
      'maxProcesses'        => 4,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 90,
      'nice'                => 0,
    ],
    'supervisor-receivable-accounts'         => [
      'connection'          => 'redis',
      'queue'               => ['receivable-accounts'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 2,
      'maxProcesses'        => 4,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 90,
      'nice'                => 0,
    ],
    'supervisor-adoption-cache'              => [
      'connection'   => 'redis',
      'queue'        => ['adoption-cache'],
      'balance'      => 'simple',
      'minProcesses' => 1,
      'maxProcesses' => 1,
      'maxTime'      => 0,
      'maxJobs'      => 0,
      'memory'       => 256,
      'tries'        => 1,
      'timeout'      => 180,
      'nice'         => 10, // baja prioridad del proceso para no competir con otros workers
    ],
    'supervisor-attendance'                  => [
      'connection'          => 'redis',
      'queue'               => ['attendance'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 1,
      'maxProcesses'        => 2,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 120,
      'nice'                => 0,
    ],
    'supervisor-accounts-receivable-reports' => [
      'connection'   => 'redis',
      'queue'        => ['accounts-receivable-reports'],
      'balance'      => 'simple',
      'minProcesses' => 1,
      'maxProcesses' => 1,
      'maxTime'      => 0,
      'maxJobs'      => 0,
      'memory'       => 256,
      'tries'        => 1,
      'timeout'      => 600,
      'nice'         => 0,
    ],
    'supervisor-models-vn-sync'              => [
      'connection'          => 'redis',
      'queue'               => ['models_vn_sync'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 1,
      'maxProcesses'        => 2,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 60,
      'nice'                => 0,
    ],
    'supervisor-product-cost-recalculation'  => [
      'connection'          => 'redis',
      'queue'               => ['product_cost_recalculation'],
      'balance'             => 'auto',
      'autoScalingStrategy' => 'time',
      'minProcesses'        => 2,
      'maxProcesses'        => 2,
      'balanceMaxShift'     => 1,
      'balanceCooldown'     => 3,
      'maxTime'             => 0,
      'maxJobs'             => 0,
      'memory'              => 128,
      'tries'               => 3,
      'timeout'             => 90,
      'nice'                => 0,
    ],
  ],

  'environments' => [
    'production' => [
      'supervisor-electronic-documents'        => ['minProcesses' => 4, 'maxProcesses' => 6],
      'supervisor-purchase-orders'             => ['minProcesses' => 2, 'maxProcesses' => 4],
      'supervisor-shipping-guides'             => ['minProcesses' => 2, 'maxProcesses' => 4],
      'supervisor-shipping-guides-sale'        => ['minProcesses' => 2, 'maxProcesses' => 6],
      'supervisor-invoice-sync'                => ['minProcesses' => 2, 'maxProcesses' => 4],
      'supervisor-credit-note-sync'            => ['minProcesses' => 2, 'maxProcesses' => 4],
      'supervisor-shipping-guide-sync'         => ['minProcesses' => 2, 'maxProcesses' => 4],
      'supervisor-images-inspections'          => ['minProcesses' => 2, 'maxProcesses' => 4],
      'supervisor-inventory-adjustments'       => ['minProcesses' => 1, 'maxProcesses' => 2],
      'supervisor-establishments'              => ['minProcesses' => 1, 'maxProcesses' => 2],
      'supervisor-mail'                        => ['minProcesses' => 1, 'maxProcesses' => 3],
      'supervisor-evaluation-dashboards'       => ['minProcesses' => 1, 'maxProcesses' => 2],
      'supervisor-validate-documents'          => ['minProcesses' => 1, 'maxProcesses' => 2],
      'supervisor-fac-invoice-sync'            => ['minProcesses' => 2, 'maxProcesses' => 4],
      'supervisor-receivable-accounts'         => ['minProcesses' => 2, 'maxProcesses' => 4],
      'supervisor-attendance'                  => ['minProcesses' => 1, 'maxProcesses' => 2],
      'supervisor-accounts-receivable-reports' => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-models-vn-sync'              => ['minProcesses' => 1, 'maxProcesses' => 2],
    ],

    'local' => [
      'supervisor-electronic-documents'        => ['minProcesses' => 1, 'maxProcesses' => 2],
      'supervisor-purchase-orders'             => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-shipping-guides'             => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-shipping-guides-sale'        => ['minProcesses' => 1, 'maxProcesses' => 2],
      'supervisor-invoice-sync'                => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-credit-note-sync'            => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-shipping-guide-sync'         => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-images-inspections'          => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-inventory-adjustments'       => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-establishments'              => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-mail'                        => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-evaluation-dashboards'       => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-validate-documents'          => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-fac-invoice-sync'            => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-receivable-accounts'         => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-attendance'                  => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-accounts-receivable-reports' => ['minProcesses' => 1, 'maxProcesses' => 1],
      'supervisor-models-vn-sync'              => ['minProcesses' => 1, 'maxProcesses' => 1],
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | File Watcher Configuration
  |--------------------------------------------------------------------------
  |
  | The following list of directories and files will be watched when using
  | the `horizon:listen` command. Whenever any directories or files are
  | changed, Horizon will automatically restart to apply all changes.
  |
  */

  'watch' => [
    'app',
    'bootstrap',
    'config/**/*.php',
    'database/**/*.php',
    'public/**/*.php',
    'resources/**/*.php',
    'routes',
    'composer.lock',
    'composer.json',
    '.env',
  ],
];
