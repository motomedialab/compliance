<?php

namespace Motomedialab\Compliance\Console\Commands;

use Illuminate\Database\Eloquent\Relations\Relation;
use Motomedialab\Compliance\Contracts\HasCompliance;
use Motomedialab\Compliance\Models\ComplianceCheck;
use Motomedialab\Compliance\Repositories\ComplianceModelsRepository;

class CompliancePruneCommand extends Command
{
    protected $signature = 'compliance:prune';
    protected $description = 'Prune models that have been marked for compliance deletion';

    public function handle(ComplianceModelsRepository $repository): int
    {
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
                    $record->model->complianceDeleteRecord();
                }

                $record->delete();
            });

            $this->info('Deleted ' . $count . ' non-compliant ' . $model . ' records');
        });
        return 0;
    }

    protected function getMorphAlias(string $model): string
    {
        $map = array_flip(Relation::morphMap());

        return $map[$model] ?? $model;
    }

}
