<?php

namespace Motomedialab\Compliance\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin Model
 */
interface HasComplianceRules
{
    /**
     * The query builder used to find non-compliant models / models
     * that should be deleted.
     */
    public function complianceQueryBuilder(): Builder;

    /**
     * The default column to use for compliance checks.
     */
    public function complianceCheckColumn(): string;

    /**
     * The number of days after the value of `complianceCheckColumn`
     * which a record should be deleted.
     */
    public function complianceDeleteAfterDays(): int;

    /**
     * The number of days we should wait after a
     * compliance check has been performed, before deleting the record.
     */
    public function complianceGracePeriod(): int;

    /**
     * The associated compliance check record - no need to modify!
     */
    public function complianceCheckRecord(): MorphOne;

    /**
     * Determine if the model meets the criteria for deletion.
     * Returns a boolean value if it is ok to schedule the deletion.
     */
    public function complianceMeetsDeletionCriteria(): bool;

    /**
     * A helper method to schedule the deletion of the model.
     */
    public function complianceScheduleDeletion(CarbonInterface $deleteOn): void;

    /**
     * A helper method to perform the deletion of the model.
     */
    public function complianceDeleteRecord(): void;
}
