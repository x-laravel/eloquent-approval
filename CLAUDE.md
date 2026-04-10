# eloquent-approval

## Overview
A Laravel package that adds a three-state approval workflow (pending / approved / rejected) to Eloquent models. Forked from `mtvs/eloquent-approval` and modernized for Laravel 12/13 and PHP 8.2+.

- **Package name:** `x-laravel/eloquent-approval`
- **Namespace:** `XLaravel\EloquentApproval`
- **Location:** `~/Projects/x-laravel/eloquent-approval`

## Requirements
- PHP ^8.2
- Laravel (illuminate) ^12.0 | ^13.0
- Orchestra Testbench ^10.0 | ^11.0 (dev)
- PHPUnit ^11.0 | ^12.0 (dev)
- Mockery ^1.0 (dev)

## Source Files (`src/`)

| File | Type | Responsibility |
|------|------|----------------|
| `Approvable.php` | trait | Main trait added to models. Registers the global scope and observer hooks via `bootApprovable()`. |
| `ApprovableObserver.php` | class | Handles `creating` and `updating` events to manage `approval_status`. Registered as a singleton in the service provider. |
| `ApprovalScope.php` | class | Global scope that filters only `approved` records by default. Adds `withAnyApproval()`, `onlyPending()`, `onlyRejected()`, `onlyApproved()`, `approve()`, `reject()`, `suspend()` macros to the query builder. |
| `ApprovalStatuses.php` | final class | Holds `PENDING`, `APPROVED`, `REJECTED` string constants. Kept as a class — converting to enum would be a breaking change. |
| `ApprovalRequired.php` | trait | Determines which attribute changes require re-approval. `approvalRequired()` and `approvalNotRequired()` can be overridden per model. |
| `ApprovalEvents.php` | trait | Registers `approving`, `approved`, `suspending`, `suspended`, `rejecting`, `rejected`, `approvalChanged` model events. |
| `ApprovalFactoryStates.php` | trait | Adds `approved()`, `suspended()`, `rejected()` states to model factories. |
| `ApprovalSchemaMethods.php` | class | Mixes `approvals()` into Blueprint (adds enum status column + nullable timestamp). |
| `ApprovalServiceProvider.php` | class | Registers `ApprovableObserver` as a singleton and calls `Blueprint::mixin`. |
| `HandlesApproval.php` | trait | Controller helper — `performApproval()` handles approval HTTP requests. |
| `UiCommand.php` | class | `php artisan approval:ui` — copies Vue.js components into the host application. |

## Key Design Decisions

### `bootApprovable()` — closures instead of `observe()`
Laravel 13 throws a `LogicException` when `static::observe()` is called from within a `boot*` trait method (recursive boot guard). The observer is registered via direct event closures instead:

```php
static::creating(function (Model $model) {
    app(ApprovableObserver::class)->creating($model);
});
```

### `ApprovalStatuses` — final class, not enum
Converting to a PHP 8.1 enum would be a breaking change. Users compare `$entity->approval_status === ApprovalStatuses::APPROVED` (string); with an enum they would need `->value`. The class is marked `final` with a `private __construct()` to prevent instantiation.

### Tests — Mockery instead of `addMethods()`
PHPUnit 12 removed `MockBuilder::addMethods()`. `ApprovalEventsTest` uses Mockery and includes `MockeryPHPUnitIntegration` trait. The `it_supports_observers` test uses an anonymous class with real methods (required by Laravel's `observe()` which calls `method_exists()`), registered in the container via `app()->instance()`.

### PHP 8.4 return type enforcement
PHP 8.4 made it a fatal error to override a typed method without a matching return type. Anonymous class method overrides in `SuspensionOnUpdateTest` were updated with explicit `array` return types.

## Git Commits

Never create a commit unless the user explicitly requests it. Always wait for a clear instruction before running `git commit`.

## Running Tests

```bash
# Locally
vendor/bin/phpunit

# Via Docker (specific PHP version)
docker compose --profile php82 run --rm php82
docker compose --profile php83 run --rm php83
docker compose --profile php84 run --rm php84
docker compose --profile php85 run --rm php85
```

## CI/CD
`.github/workflows/tests.yml` runs a matrix of PHP 8.2–8.5 × Laravel 12–13 (7 combinations). PHP 8.2 + Laravel 13 is excluded because Laravel 13 requires PHP ^8.3.
