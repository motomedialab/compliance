<?php

namespace Motomedialab\Compliance\Events;

use Motomedialab\Compliance\Contracts\HasCompliance;

class ComplianceDeleting
{
    public function __construct(public HasCompliance $model)
    {
        //
    }
}
