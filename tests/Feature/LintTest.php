<?php

use Symfony\Component\Process\Process;

test('no lint errors', function () {
    $process = new Process(
        ['php', 'vendor/bin/pint', '--test', '--no-ansi'],
        base_path()
    );
    $process->setTimeout(null);
    $process->run();

    expect($process->isSuccessful())->toBeTrue(
        "Pint encontró errores de estilo:\n" . $process->getOutput()
    );
});
