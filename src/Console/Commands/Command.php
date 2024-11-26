<?php

namespace Motomedialab\Compliance\Console\Commands;

use Illuminate\Console\Command as BaseCommand;
use Illuminate\Support\Collection;
use Motomedialab\Compliance\Repositories\CompliantModelsRepository;

abstract class Command extends BaseCommand
{
    abstract public function handle(CompliantModelsRepository $repository): int;

    protected function getModels(): Collection
    {
        return collect(config('compliance.models'))
            ->map(fn ($value, $key) => is_string($key) ? $key : $value)
            ->unique();
    }
}
