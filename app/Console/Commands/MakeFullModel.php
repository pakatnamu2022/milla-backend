<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeFullModel extends Command
{
    protected $signature = 'make:fullmodel {path}';
    protected $description = 'Crear modelo con controller, requests (Index, Store, Update), resource y service en estructura de carpetas.';

    public function handle(): int
    {
        $inputPath = str_replace('\\', '/', $this->argument('path'));
        $modelName = Str::studly(class_basename($inputPath));
        $relativePath = Str::beforeLast($inputPath, '/') ?: '';
        $normalizedPath = str_replace('/', '\\', $inputPath);
        $modelClass = "App\\Models\\{$normalizedPath}";

        // 1. Modelo
        $this->call('make:model', ['name' => $inputPath]);

        // 2. Controller (sin duplicar Http/)
        $this->call('make:controller', [
            'name' => "{$relativePath}/{$modelName}Controller",
            '--model' => $modelClass,
        ]);

        // 3. Migration (sí se deja)
        $this->call('make:migration', [
            'name' => 'create_' . Str::snake($modelName) . '_table',
        ]);

        // 4. Requests
        foreach (['Index', 'Store', 'Update'] as $type) {
            $this->call('make:request', [
                'name' => "{$relativePath}/{$type}{$modelName}Request",
            ]);
        }

        // 5. Resource
        $this->call('make:resource', [
            'name' => "{$relativePath}/{$modelName}Resource",
        ]);

        // 6. Service (manual)
        $servicePath = app_path("Http/Services/{$relativePath}");
        $serviceFile = "{$servicePath}/{$modelName}Service.php";
        if (!File::exists($servicePath)) {
            File::makeDirectory($servicePath, 0755, true);
        }

        File::put($serviceFile, $this->generateServiceContent($relativePath, $modelName, $modelClass));

        $this->info("Modelo {$modelName} y estructura principal generada correctamente.");
        return Command::SUCCESS;
    }

    private function generateServiceContent(string $relativePath, string $modelName, string $modelClass): string
    {
        $namespace = 'App\\Http\\Services' . ($relativePath ? '\\' . str_replace('/', '\\', $relativePath) : '');
        return <<<PHP
<?php

namespace {$namespace};

use {$modelClass};

class {$modelName}Service extends BaseService
{
    // Aquí va la lógica del servicio para {$modelName}
}
PHP;
    }
}
