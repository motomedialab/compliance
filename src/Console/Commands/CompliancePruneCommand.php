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

            $modelName = $model;
            $model = new $model();

            if (!$model instanceof HasCompliance) {
                return;
            }

            $records = ComplianceCheck::query()
                ->where('model_type', $this->getMorphAlias($modelName))
                ->where('deletion_date', '<', now())
                ->with(['model' => fn ($builder) => $model->complianceQueryBuilder($builder)])
                ->cursor();

            if ($records->isEmpty()) {
                $this->info('No records to prune for ' . $modelName);
                return;
            }

            $this->info('Processing ' . $modelName . ' records');

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
            $this->info("Found {$count} non-compliant {$modelName} records that {$action} deleted.");
        });
        return 0;
    }

    protected function getMorphAlias(string|object $model): string
    {
        $model = is_object($model) ? get_class($model) : $model;

        $map = array_flip(Relation::morphMap());

        return $map[$model] ?? $model;
    }

}
