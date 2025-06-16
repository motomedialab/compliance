<?php

namespace Motomedialab\Compliance\Console\Commands;

use Illuminate\Database\Eloquent\Relations\Relation;
use Motomedialab\Compliance\Contracts\HasCompliance;
use Motomedialab\Compliance\Models\ComplianceCheck;
use Motomedialab\Compliance\Repositories\ComplianceModelsRepository;

class CompliancePruneCommand extends Command
{
    protected $signature = 'compliance:prune {--dry-run : Simulate the check without making any changes}';
    protected $description = 'Prune models that have been marked for compliance deletion';

    private bool $isDryRun = false;

    public function handle(ComplianceModelsRepository $repository): int
    {
        if ($this->isDryRun = (bool)$this->option('dry-run')) {
            $this->warn('Running in dry-run mode. No records will be deleted.');
        }

        $this->getModels()->each(function (string $model): void {
            $count = 0;

            $records = ComplianceCheck::query()
                ->where('model_type', $this->getMorphAlias($model))
                ->where('deletion_date', '<', now())
                ->with(['model' => fn () => (new $model())->complianceQueryBuilder()])
                ->cursor();

            if ($records->isEmpty()) {
                $this->info('No records to prune for ' . $model);
                return;
            }

            $this->info('Processing ' . $model . ' records');

            $this->withProgressBar($records, function (ComplianceCheck $record) use (&$count): void {
                if ($record->model instanceof HasCompliance && $record->model->complianceMeetsDeletionCriteria()) {
                    ++$count;

                    if (!$this->isDryRun) {
                        $record->model->complianceDeleteRecord();
                    }
                }

                if (!$this->isDryRun) {
                    $record->delete();
                }
            });

            $action = $this->isDryRun ? 'would be' : 'were';
            $this->info("Found {$count} non-compliant {$model} records that {$action} deleted.");
        });
        return 0;
    }

    protected function getMorphAlias(string $model): string
    {
        $map = array_flip(Relation::morphMap());

        return $map[$model] ?? $model;
    }

}
