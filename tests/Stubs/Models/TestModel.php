<?php

namespace Motomedialab\Compliance\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Motomedialab\Compliance\Contracts\HasCompliance;
use Motomedialab\Compliance\Tests\Stubs\Factories\TestModelFactory;
use Motomedialab\Compliance\Traits\Compliance;

class TestModel extends Model implements HasCompliance
{
    use Compliance;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'allow_delete' => 'boolean',
        'allow_before_deletion' => 'boolean',
    ];

    public function complianceMeetsDeletionCriteria(): bool
    {
        return $this->allow_delete === true;
    }

    public function beforeComplianceDeletion(): bool
    {
        return $this->getAttribute('allow_before_deletion') ?? true;
    }

    protected static function newFactory(): TestModelFactory
    {
        return new TestModelFactory();
    }
}
