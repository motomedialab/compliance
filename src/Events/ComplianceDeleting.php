<?php

namespace Motomedialab\Compliance\Events;

use Motomedialab\Compliance\Contracts\HasComplianceRules;

class ComplianceDeleting
{
    public function __construct(public HasComplianceRules $model)
    {
        //
    }
}
