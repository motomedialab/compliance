<?php

namespace Motomedialab\Compliance\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Motomedialab\Compliance\Contracts\HasCompliance;
use Motomedialab\Compliance\Tests\Stubs\Factories\SoftDeletableModelFactory;
use Motomedialab\Compliance\Traits\Compliance;

class SoftDeletableModel extends Model implements HasCompliance
{
    use Compliance;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $table = 'soft_deletable_models';

    protected static function newFactory(): SoftDeletableModelFactory
    {
        return new SoftDeletableModelFactory();
    }
}
