<?php

namespace Motomedialab\Compliance\Events;

use Motomedialab\Compliance\Models\ComplianceCheck;

class ComplianceRecordPendingDeletion
{
    public function __construct(public ComplianceCheck $record)
    {
        //
    }
}
