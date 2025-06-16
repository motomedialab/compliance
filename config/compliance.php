<?php

return [
    /**
     * The models that should be checked for compliance.
     *
     * These models *need to* implement the HasComplianceRules interface
     * and use the ComplianceRules trait.
     */
    'models' => [
        App\Models\User::class => [
            'column' => 'last_login_at',
            'delete_after_days' => 365 * 3, // 3 years
            'deletion_grace_period' => 15, // 15 days
            'force_delete' => true,
        ]
    ],

    /**
     * The times that the scheduled commands will be run.
     *
     * check_time - when compliance should search for records that need deletion
     * prune_time - when compliance should perform record deletions.
     */
    'schedule' => [
        'check_time' => '08:55',
        'prune_time' => '09:30',
    ],
];
