# Compliance: Your Automated Data Retention Guardian

In today's world of data privacy regulations like GDPR, you can't hold onto user data forever. But manually
tracking and deleting old records is a headache waiting to happen. Compliance is a configurable
Laravel package that automates this process, ensuring you stay on the right side of the law by
effortlessly deleting records that are no longer needed.

Think of it as a fire-and-forget solution for data retention. Set up your rules, and let Compliance handle the rest.

## How It Works: The Two-Step Deletion Process

Compliance operates on a safe, two-step cycle to ensure records are never deleted by mistake.
This gives you a grace period to act, for instance, by notifying a user that their account is about to be removed.

1.  **The Check (`artisan compliance:check`)**:
    * Every day, a scheduled command runs through your specified models (like `User`).
    * It looks for records that meet your defined deletion criteria (e.g., users who haven't logged in for 3 years).
    * When a record matches, Compliance creates a `ComplianceCheck` entry in your database, marking the record with a future deletion date.
    * At this point, the `ComplianceRecordPendingDeletion` event is fired. This is your chance to hook in and, for example, send a notification email.

2.  **The Prune (`artisan compliance:prune`)**:
    * A second daily command scans for `ComplianceCheck` records where the deletion date has passed.
    * It performs a final check on the model to ensure it *still* meets the deletion criteria. This is a safety net... If a user logs in after being marked for deletion, their account will be spared.
    * If the criteria are still met, the `ComplianceDeleting` event is fired just before the model is permanently deleted.

## Installation

Getting started is easy, just follow these three steps...

1.  **Install via Composer:**
    ```bash
    composer require motomedialab/compliance
    ```

2.  **Publish Configuration & Run Migrations:**
    Publish the configuration file to specify which models to monitor. Then, run the migrations to create the `compliance_checks` table.
    ```bash
    php artisan vendor:publish --provider="Motomedialab\Compliance\Providers\ComplianceServiceProvider" --tag="config"
    php artisan migrate
    ```

## Quick Start: The Basic User Model

Out of the box, the package is configured to handle a typical `User` model. Just implement the `HasCompliance` contract and use the `ComplianceRules` trait.

```php
// app/Models/User.php
namespace App\Models;

use Motomedialab\Compliance\Traits\Compliance;
use Motomedialab\Compliance\Contracts\HasCompliance;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasCompliance
{
    use Compliance;
    // ... your other model code
}
```

Next, add your `User` model to the `config/compliance.php` file.

```php
// config/compliance.php
return [
    'models' => [
        App\Models\User::class => [
            // The column to check
            'column' => 'last_login_at',
            // How old the record must be to be considered for deletion
            'delete_after_days' => 365 * 3, // 3 years
            // The grace period between being marked and being deleted
            'deletion_grace_period' => 15 // 15 days
        ],
    ],
];
```

And that's it! The package will now automatically schedule users for deletion if their `last_login_at` date is over three years old.

## Advanced Scenarios & Powerful Examples

The real power of this package lies in its flexibility. You can override several methods from the `ComplianceRules` trait to build highly custom logic.

### Example 1: Deleting Only Users Who Have *Never* Logged In

You might want to clean up accounts that were created but never used. You can override the `complianceQueryBuilder` method to define a completely custom query.

```php
// app/Models/User.php
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable implements HasCompliance
{
    use Compliance;

    /**
     * Override the default query to only find users who have never logged in.
     */
    public function complianceQueryBuilder(): Builder
    {
        // Use the newQuery() method on the model for a clean builder instance.
        return $this->newQuery()->whereNull('last_login_at');
    }
}
```

### Example 2: Protecting VIPs or Active Subscribers

You never want to accidentally delete a paying customer. The `complianceMeetsDeletionCriteria` method is your
safety check, which runs both during the initial check and right before the final deletion.

```php
// app/Models/User.php
class User extends Authenticatable implements HasCompliance
{
    use Compliance;

    /**
     * This method is the final gatekeeper.
     * Only return true if the record can be safely deleted.
     */
    public function complianceMeetsDeletionCriteria(): bool
    {
        // Don't delete if the user is an admin or has an active subscription.
        if ($this->is_admin || $this->hasActiveSubscription()) {
            return false;
        }
        return true;
    }
}
```

### Example 3: Notifying Users of Pending Deletion

Compliance fires events to let you hook into the lifecycle. Here’s how you can listen for the `ComplianceRecordPendingDeletion` event to email a user.

First, create a listener:
`php artisan make:listener NotifyUserOfPendingDeletion`

Then, register it in your `EventServiceProvider`:

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \Motomedialab\Compliance\Events\ComplianceRecordPendingDeletion::class => [
        \App\Listeners\NotifyUserOfPendingDeletion::class,
    ],
];
```

Finally, implement the listener's logic:

```php
// app/Listeners/NotifyUserOfPendingDeletion.php
namespace App\Listeners;

use Illuminate\Support\Facades\Mail;
use App\Mail\AccountDeletionWarning;
use Motomedialab\Compliance\Events\ComplianceRecordPendingDeletion;

class NotifyUserOfPendingDeletion
{
    public function handle(ComplianceRecordPendingDeletion $event): void
    {
        $user = $event->record->model;
        $deletionDate = $event->record->deletion_date;

        // Send an email to the user
        Mail::to($user)->send(new AccountDeletionWarning($user, $deletionDate));
    }
}
```

### Example 4: Cleaning Up Old Log Entries

This package isn't just for users. You can apply it to any model. Imagine you have a `Log` model and you only want to keep records for 90 days.

```php
// config/compliance.php
'models' => [
    // ... other models
    App\Models\Log::class => [
        'column' => 'created_at',
        'delete_after_days' => 90,
        'deletion_grace_period' => 0 // Delete immediately
    ],
],
```

Then, simply apply the trait and interface to your `Log` model.

```php
// app/Models/Log.php
namespace App\Models;

use Motomedialab\Compliance\Traits\Compliance;
use Motomedialab\Compliance\Contracts\HasCompliance;
use Illuminate\Database\Eloquent\Model;

class Log extends Model implements HasCompliance
{
    use Compliance;
}
```