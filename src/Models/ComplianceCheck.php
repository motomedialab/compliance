<?php

namespace Motomedialab\Compliance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Motomedialab\Compliance\Contracts\HasCompliance;

/**
 * @property null|HasCompliance $model
 */
class ComplianceCheck extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    public function model(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }
}
