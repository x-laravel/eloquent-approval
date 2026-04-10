# Eloquent Approval

[![Tests](https://github.com/x-laravel/eloquent-approval/actions/workflows/tests.yml/badge.svg)](https://github.com/x-laravel/eloquent-approval/actions/workflows/tests.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12%20|%2013-red)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE.md)

A Laravel package that adds a three-state approval workflow to Eloquent models — **pending**, **approved**, and **rejected**.

## How It Works

- Newly created models are automatically set to **pending**
- Only **approved** models are returned by default queries
- Updating attributes that require approval re-suspends the model back to **pending**
- Status changes dispatch model events you can hook into

## Requirements

- PHP ^8.2
- Laravel ^12.0 | ^13.0

## Installation

```bash
composer require x-laravel/eloquent-approval
```

The service provider is registered automatically via Laravel's package discovery.

## Setup

### 1. Migration

Add the approval columns to your table:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->approvals(); // adds approval_status (enum) + approval_at (timestamp)
    $table->timestamps();
});
```

### 2. Model

Add the `Approvable` trait to your model:

```php
use Illuminate\Database\Eloquent\Model;
use XLaravel\EloquentApproval\Approvable;

class Post extends Model
{
    use Approvable;
}
```

#### Custom Column Names

```php
class Post extends Model
{
    use Approvable;

    const APPROVAL_STATUS = 'status';
    const APPROVAL_AT = 'status_changed_at';
}
```

> Cast `approval_at` to `datetime` in your model's `$casts` to get `Carbon` instances.

## Usage

### Querying

```php
// Default: only approved records
Post::all();
Post::find(1); // null if pending or rejected

// Include all statuses
Post::withAnyApproval()->get();
Post::withAnyApproval()->find(1);

// Filter by status
Post::onlyPending()->get();
Post::onlyApproved()->get();
Post::onlyRejected()->get();
```

To disable the approval scope globally on a model:

```php
class Post extends Model
{
    use Approvable;

    public $approvalScopeDisabled = true;
}
```

### Updating Status

#### On a model instance

```php
$post->approve();  // ?bool — true on success, false if already approved, null if not persisted
$post->reject();
$post->suspend();
```

#### On a query builder

```php
Post::whereIn('id', $ids)->approve(); // returns number of updated rows
Post::whereIn('id', $ids)->reject();
Post::whereIn('id', $ids)->suspend();
```

### Checking Status

```php
$post->isApproved(); // ?bool
$post->isRejected(); // ?bool
$post->isPending();  // ?bool
```

### Approval Required Attributes

By default, all attribute changes trigger re-suspension. You can customise this:

```php
class Post extends Model
{
    use Approvable;

    // Only these attributes trigger re-suspension
    public function approvalRequired(): array
    {
        return ['title', 'body'];
    }

    // These attributes never trigger re-suspension
    public function approvalNotRequired(): array
    {
        return ['view_count'];
    }
}
```

`approvalRequired()` acts as a blacklist, `approvalNotRequired()` as a whitelist — same logic as Eloquent's `$fillable` and `$guarded`.

> Re-suspension only happens when updating via a model instance, not through a query builder.

### Duplicate Approvals

Setting the status to its current value is a no-op — no events are dispatched, `approval_at` is not updated, and the method returns `false`.

## Events

Each approval action dispatches a before and after event:

| Action    | Before      | After      |
|-----------|-------------|------------|
| `approve` | `approving` | `approved` |
| `suspend` | `suspending`| `suspended`|
| `reject`  | `rejecting` | `rejected` |

A general `approvalChanged` event is also dispatched on every status change.

Returning `false` from a before-event listener halts the operation.

```php
// Via static callbacks
Post::approving(function (Post $post) {
    // return false to halt
});

Post::approved(function (Post $post) {
    // notify the author
});

Post::approvalChanged(function (Post $post) {
    // fires on any status change
});

// Via an observer
Post::observe(PostApprovalObserver::class);
```

```php
class PostApprovalObserver
{
    public function approving(Post $post): void
    {
        //
    }

    public function approved(Post $post): void
    {
        //
    }
}
```

## Factory States

Add `ApprovalFactoryStates` to your factory to create models with a specific status:

```php
use Illuminate\Database\Eloquent\Factories\Factory;
use XLaravel\EloquentApproval\ApprovalFactoryStates;

class PostFactory extends Factory
{
    use ApprovalFactoryStates;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
        ];
    }
}
```

```php
Post::factory()->approved()->create();
Post::factory()->rejected()->create();
Post::factory()->suspended()->create();
```

## HTTP Approval Controller

Use the `HandlesApproval` trait in a controller to handle approval requests:

```php
use App\Http\Controllers\Controller;
use App\Models\Post;
use XLaravel\EloquentApproval\HandlesApproval;

class PostApprovalController extends Controller
{
    use HandlesApproval;

    protected function model(): string
    {
        return Post::class;
    }
}
```

```php
Route::post('admin/posts/{key}/approval', [PostApprovalController::class, 'performApproval'])
    ->middleware(['auth', 'can:manage-approvals']);
```

The request must include an `approval_status` field with one of: `approved`, `pending`, `rejected`.

## Testing

```bash
# Build first (once per PHP version)
DOCKER_BUILDKIT=0 docker compose --profile php82 build

# Run tests
docker compose --profile php82 up
docker compose --profile php83 up
docker compose --profile php84 up
docker compose --profile php85 up
```

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/license/MIT).
