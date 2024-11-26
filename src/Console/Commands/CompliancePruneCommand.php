<?php

namespace Motomedialab\Compliance\Console\Commands;

use Motomedialab\Compliance\Contracts\HasComplianceRules;
use Motomedialab\Compliance\Repositories\CompliantModelsRepository;

class CompliancePruneCommand extends Command
{
    protected $signature = 'compliance:prune';
    protected $description = 'Prune models that have been marked for compliance deletion';

    public function handle(CompliantModelsRepository $repository): int
    {
        $this->getModels()->each(function (string $model) use ($repository): void {
            $count = 0;
            $models = $repository->getModelsByClassName($model, whereHasCheck: true);

            if ($models->isEmpty()) {
                $this->info('No records to prune for ' . $model);
                return;
            }

            $this->info('Processing ' . $model . ' records');

            $this->withProgressBar($models, function (HasComplianceRules $model) use (&$count): void {
                if ($model->complianceMeetsDeletionCriteria()) {
                    ++$count;
                    $model->complianceDeleteRecord();
                    return;
                }

                $model->complianceCheckRecord()->delete();
            });

            $this->info('Deleted ' . $count . ' non-compliant ' . $model . ' records');
        });
        return 0;
    }
}
