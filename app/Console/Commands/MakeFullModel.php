<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;

class MakeFullModel extends Command
{
    protected $signature = 'make:fullmodel {name}';
    protected $description = 'Crear modelo con -a, Index[Model]Request y [Model]Resource';

    public function handle(): int
    {
        $name = $this->argument('name');
        $modelName = Str::studly(class_basename($name));
        $requestName = "Index{$modelName}Request";
        $resourceName = "{$modelName}Resource";

        // Genera el modelo con -a
        $this->call('make:model', ['name' => $name, '-a' => true]);

        // Genera Index[Model]Request
        $this->call('make:request', ['name' => $requestName]);

        // Genera [Model]Resource
        $this->call('make:resource', ['name' => $resourceName]);

        $this->info("Modelo completo creado con Index{$modelName}Request y {$resourceName}");
        return Command::SUCCESS;
    }
}
