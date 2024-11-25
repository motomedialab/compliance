<?php

namespace Motomedialab\Compliance\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Motomedialab\Compliance\Contracts\HasComplianceRules;
use Motomedialab\Compliance\Tests\Stubs\Factories\TestModelFactory;
use Motomedialab\Compliance\Traits\ComplianceRules;

class TestModel extends Model implements HasComplianceRules
{
    use ComplianceRules;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'allow_delete' => 'boolean',
    ];

    public function complianceMeetsDeletionCriteria(): bool
    {
        return $this->allow_delete === true;
    }

    protected static function newFactory(): TestModelFactory
    {
        return new TestModelFactory();
    }
}
