<?php

namespace Motomedialab\Compliance\Console\Commands;

use Illuminate\Console\Command;
use Motomedialab\Compliance\Contracts\HasComplianceRules as Record;
use Motomedialab\Compliance\Repositories\CompliantModelsRepository;

class ComplianceCheckCommand extends Command
{
    protected $signature = 'compliance:check';
    protected $description = 'Check for non-conforming compliance records and mark them for deletion';

    public function handle(CompliantModelsRepository $repository): int
    {
        $config = config('compliance');
        $hasErrors = false;

        foreach ($this->getModels() as $model) {
            try {
                // get our records that meet our criteria
                $records = $repository->getModelsByClassName($model, whereDoesntHaveCheck: true)
                    ->filter(fn (Record $model) => $model->complianceMeetsDeletionCriteria() === true);

                // store a count
                $count = $records->count();

                // and schedule them for deletion
                $records->each(
                    fn (Record $model) => $model
                    ->complianceScheduleDeletion(now()->addDays($model->complianceGracePeriod()))
                );

                $this->info('Scheduled ' . $count . ' ' . $model . ' records for deletion');
            } catch (\Exception $e) {
                $hasErrors = true;
                $this->error($e->getMessage());
                continue;
            }
        }

        return $hasErrors ? 1 : 0;
    }

    protected function getModels(): array
    {
        return collect(config('compliance.models'))
            ->map(fn ($value, $key) => is_string($key) ? $key : $value)
            ->unique()
            ->toArray();
    }
}
