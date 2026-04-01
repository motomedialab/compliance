<?php

namespace Motomedialab\Compliance\Console\Commands;

use Exception;
use Motomedialab\Compliance\Contracts\HasCompliance as Record;
use Motomedialab\Compliance\Repositories\ComplianceModelsRepository;

class ComplianceCheckCommand extends Command
{
    protected $signature = 'compliance:check {--dry-run : Simulate the check without making any changes}';
    protected $description = 'Check for non-conforming compliance records and mark them for deletion';

    private bool $isDryRun = false;

    public function handle(ComplianceModelsRepository $repository): int
    {
        $hasErrors = false;

        if ($this->isDryRun = (bool)$this->option('dry-run')) {
            $this->warn('Running in dry-run mode. No records will be scheduled for deletion.');
        }

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
        $count = 0;

        // get our records that meet our criteria
        $records = $repository->getModelsByClassName($model, whereDoesntHaveCheck: true)
            ->filter(fn (Record $model) => $model->complianceMeetsDeletionCriteria() === true);

        if ($this->isDryRun) {
            $records->each(function (Record $model) use (&$count): void {
                $count++;
                $this->line('  - Model ID: ' . $model->getKey());
            });

            $this->info('Found ' . $count . ' ' . $model . ' records that would be scheduled for deletion.');
            return;
        }

        // and schedule them for deletion
        $records->each(function (Record $model) use (&$count): void {
            $count++;
            $model->complianceScheduleDeletion(now()->addDays($model->complianceGracePeriod()));
        });

        $this->info('Scheduled ' . $count . ' ' . $model . ' records for deletion');
    }
}
