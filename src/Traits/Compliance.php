<?php

namespace Motomedialab\Compliance\Traits;

use Carbon\CarbonInterface;
use Motomedialab\Compliance\Events;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Query\Builder;
use Motomedialab\Compliance\Models\ComplianceCheck;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Motomedialab\Compliance\Contracts\HasCompliance;

/**
 * @mixin Model
 * @mixin HasCompliance
 */
trait Compliance // @phpstan-ignore trait.unused
{
    public static function bootComplianceRules(): void
    {
        static::deleting(fn (HasCompliance $model) => $model->complianceCheckRecord()->delete());
    }

    /**
     * The default query builder used by the compliance package.
     * This gives an opportunity to scope down your query.
     */
    public function complianceQueryBuilder(): Builder
    {
        return $this->newQuery()
            ->where($this->complianceCheckColumn(), '<', now()->subDays($this->complianceDeleteAfterDays()));
    }

    public function complianceCheckRecord(): MorphOne
    {
        return $this->morphOne(ComplianceCheck::class, 'model');
    }

    /**
     * The default column we'll use to check if a record is ready for deletion.
     * Typically, it'll look at the updated_at column.
     */
    public function complianceCheckColumn(): string
    {
        return config('compliance.models.' . static::class . '.column', 'updated_at');
    }

    /**
     * The default number of days this record will be looked at for deletion.
     */
    public function complianceDeleteAfterDays(): int
    {
        return config('compliance.models.' . static::class . '.delete_after_days', 365 * 3);
    }

    /**
     * The default grace period. When a deletion is scheduled, a record
     * of the deletion will be set in the database, with the calculated deletion date.
     */
    public function complianceGracePeriod(): int
    {
        return config('compliance.models.' . static::class . '.deletion_grace_period', 15);
    }

    /**
     * Whether a record should be force deleted or not by default.
     */
    public function complianceShouldForceDelete(): int
    {
        return config('compliance.models.' . static::class . '.force_delete', true);
    }

    /**
     * Called by the compliance check command when scheduling
     * deletion of the model. This emits an event that can be used
     * to email the user, for example - notifying them that their
     * account is scheduled for deletion.
     */
    public function complianceScheduleDeletion(CarbonInterface $deleteOn): void
    {
        $record = $this->complianceCheckRecord()->create([
            'deletion_date' => $deleteOn,
        ])->setRelation('model', $this);

        event(new Events\ComplianceRecordPendingDeletion($record));
    }

    /**
     * This method is called by the scheduled prune command. It offers
     * an additional opportunity to prevent the deletion.
     *
     * It also emits an event and calls on the beforeComplianceDeletion()
     * method against the model - just to be 100% sure!
     */
    public function complianceDeleteRecord(): void
    {
        event(new Events\ComplianceDeleting($this));

        if (!$this->beforeComplianceDeletion()) {
            return;
        }

        $this->complianceShouldForceDelete() ? $this->forceDelete() : $this->delete();
    }

    /**
     * When running the scheduled check for records that meet the retention criteria,
     * this method will be called. This is a further opportunity to perform additional
     * checks before scheduling the record for deletion.
     *
     * This method will be called an additional time, right before deletion - just
     * in case something has changed, and it shouldn't be deleted.
     *
     * @return bool
     */
    public function complianceMeetsDeletionCriteria(): bool
    {
        return true;
    }

    /**
     * An opportunity to perform additional actions
     * and hook onto this method right before deleting the record.
     *
     * This method must return a boolean value. True will continue
     * with the model deletion, and false will abort the deletion.
     *
     * @return bool
     */
    public function beforeComplianceDeletion(): bool
    {
        return true;
    }

}
