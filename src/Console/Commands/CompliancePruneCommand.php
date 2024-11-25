<?php

namespace Motomedialab\Compliance\Console\Commands;

use Illuminate\Console\Command;
use Motomedialab\Compliance\Contracts\HasComplianceRules;
use Motomedialab\Compliance\Models\ComplianceCheck;

class CompliancePruneCommand extends Command
{
    protected $signature = 'compliance:prune';
    protected $description = 'Prune models that have been marked for compliance deletion';

    public function handle(): int
    {
        $count = 0;

        $records = ComplianceCheck::query()
            ->where('deletion_date', '<=', now())
            ->with('model')
            ->cursor();

        if ($records->isEmpty()) {
            $this->info('No records to prune');
            return 0;
        }

        $this->info('Processing ' . $records->count() . ' records');

        $records
            ->each(function (ComplianceCheck $check) use (&$count): void {
                if ($check->model instanceof HasComplianceRules && $check->model->complianceMeetsDeletionCriteria()) {
                    $check->model->complianceDeleteRecord();
                    ++$count;
                    return;
                }

                // if we reach here, we can delete this compliance check.
                $check->delete();
            });

        $this->info('Deleted ' . $count . ' non-compliant records');
        return 0;
    }
}
