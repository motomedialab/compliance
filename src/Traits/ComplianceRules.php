<?php

namespace Motomedialab\Compliance\Traits;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Motomedialab\Compliance\Contracts\HasComplianceRules;
use Motomedialab\Compliance\Events;
use Motomedialab\Compliance\Models\ComplianceCheck;

/**
 * @mixin Model
 * @mixin HasComplianceRules
 */
trait ComplianceRules
{
    public static function bootComplianceRules(): void
    {
        static::deleting(function (HasComplianceRules $model) {
            $model->complianceCheckRecord()->delete();
        });
    }

    public function complianceQueryBuilder(Builder|null $builder = null): Builder
    {
        return ($builder ?? $this->newQuery())
            ->where($this->complianceCheckColumn(), '<', now()->subDays($this->complianceDeleteAfterDays()));
    }

    public function complianceCheckRecord(): MorphOne
    {
        return $this->morphOne(ComplianceCheck::class, 'model');
    }

    public function complianceCheckColumn(): string
    {
        return config('compliance.models.' . static::class . '.column', 'last_login_at');
    }

    public function complianceDeleteAfterDays(): int
    {
        return config('compliance.models.' . static::class . '.delete_after_days', 365 * 3);
    }

    public function complianceGracePeriod(): int
    {
        return config('compliance.models.' . static::class . '.deletion_grace_period', 15);
    }

    public function complianceScheduleDeletion(CarbonInterface $deleteOn): void
    {
        $record = $this->complianceCheckRecord()->create([
            'deletion_date' => $deleteOn,
        ])->setRelation('model', $this);

        event(new Events\ComplianceRecordPendingDeletion($record));
    }

    public function complianceDeleteRecord(): void
    {
        event(new Events\ComplianceDeleting($this));

        $this->forceDelete();
    }

    public function complianceMeetsDeletionCriteria(): bool
    {
        return true;
    }
}
