<?php

namespace Motomedialab\Compliance\Console\Commands;

use Exception;
use Motomedialab\Compliance\Contracts\HasComplianceRules as Record;
use Motomedialab\Compliance\Repositories\ComplianceModelsRepository;

class ComplianceCheckCommand extends Command
{
    protected $signature = 'compliance:check';
    protected $description = 'Check for non-conforming compliance records and mark them for deletion';

    public function handle(ComplianceModelsRepository $repository): int
    {
        $hasErrors = false;

        $this->getModels()->each(function (string $model) use ($repository, &$hasErrors): void {
            try {
                $this->info('Checking ' . $model . ' records');
                $this->processModel($repository, $model);
            } catch (Exception $e) {
                $hasErrors = true;
                $this->error($e->getMessage());
            }
        });

        return $hasErrors ? 1 : 0;
    }

    /**
     * @throws Exception
     */
    protected function processModel(ComplianceModelsRepository $repository, string $model): void
    {
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
    }
}
