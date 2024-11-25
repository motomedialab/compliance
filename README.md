# MotoMediaLab Compliance

MotoMediaLab compliance is a highly configurable package that helps
you to stay on the right side of modern data regulations by automatically
deleting records that are no longer necessary to be stored. For example, a user
that hasn't logged in to your system in three years.

## How does it work?

This package provides an interface and a trait that can be applied to any model.
These have a series of methods that you can modify in order to change the desired
functionality on a per-model basis.

By default, the package will look for a `last_login_at` column on the model and
queue the record for deletion if it's older than the configured number of days
(defaults to 365 * 3 / 3 years). The `last_login_at` is of course geared
towards a `User` model, but the query is completely customisable per model.

### Scheduled tasks

Two commands are automatically scheduled, one for checks and one for pruning.

#### The check command `compliance:check`

On a daily basis, the check job runs through all of your defined models and searches for records
that meet the deletion criteria. If the criteria is met, it'll create a `ComplianceCheck`
model. This will also emit a `ComplianceRecordPendingDeletion` event.
This `ComplianceCheck` model also stores the date on which the record should be deleted.

#### The prune command `compliance:prune`

On a daily basis, the prune job runs through all of the `ComplianceCheck` records that
have exceeded the `deletion_date` date. Much like the check job, it'll then once again check
for compliance. If the deletion criteria is still met, it'll delete the model and the associated
check record. Before deletion, the `ComplianceDeleting` event will be emitted.

### Events

The package emits two events, `ComplianceRecordPendingDeletion` which is emitted when a model
is marked for deletion and `ComplianceDeleting` which is emitted just before the model is deleted.

These events allow you to easily take action or notify the customer that their account will
be closed without their action.

## Installation

You can install the package via composer:

```bash
composer require motomedialab/compliance
```

After installing the package, you'll need to publish the configuration file. From here, you can
specify the models that should be checked for compliance. You'll also need to run the migrations.

```bash
php artisan vendor:publish compliance
php artisan migrate
```

## Example

Example model:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Motomedialab\Compliance\Traits\ComplianceRules;
use Motomedialab\Compliance\Contracts\HasComplianceRules;

class User implements HasComplianceRules
{
    use ComplianceRules;    
}
```

The above implementation will use the default configuration. It'll search for all users
that have a `last_login_at` column that is older than 3 years.

## Configuration

The configuration file is located at `config/compliance.php`. Here you can specify the models
that should be checked for compliance. You can also specify the number of days that a record
should be kept before deletion and the grace period between 'checking' and deletion.

```php
return [
    'models' => [
        App\Models\User::class => [
            // the default date column to check on
            'column' => 'last_login_at',
            // the number of days (relative to `column`) before a record will looked at for deletion
            'delete_after_days' => 365 * 3,
            // the number of days between the record being marked for deletion and actually being deleted
            'deletion_grace_period' => 15,
        ],
    ],
];
```
## Advanced configuration

There are a number of methods that you can override in the `ComplianceRules` trait. These methods allow you
to customise the query that is run, the checks that are performed, and most importantly, an additional check
to see if a record should be deleted.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Motomedialab\Compliance\Traits\ComplianceRules;
use Motomedialab\Compliance\Contracts\HasComplianceRules;

class User implements HasComplianceRules
{
    use ComplianceRules;
    
    // override to only delete users that have never logged in
    public function complianceQueryBuilder() : Builder
    {
        // make sure you eager load any relationships that you need!
        return $this->newQuery()
            ->with('subscriptions')
            ->whereNull('last_login_at');    
    }
    
    // the default column to check against
    // (irrelevant if you are already overriding complianceQueryBuilder)
    public function complianceCheckColumn() : string{
        return 'last_login_at';
    }
    
    // manipulate the number of days before a record is marked for deletion
    // (irrelevant if you are already overriding complianceQueryBuilder)
    public function complianceDeleteAfterDays() : int{
       return 365;
    }
    
    // manipulate the number of days between marking for deletion
    // and actually deleting the model
    public function complianceGracePeriod() : int
    {
        return 50;
    }
    
    // here you can set a boolean flag to assert
    // whether it should be possible to delete the record.
    // this will be checked both initially and right before the record
    // is deleted.
    public function complianceMeetsDeletionCriteria(): bool
    {
        // example: only delete if a user has no subscriptions.
        return $this->subscriptions->count() === 0;
    }
}
```