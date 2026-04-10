<?php

namespace XLaravel\EloquentApproval;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class ApprovalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ApprovableObserver::class, function () {
            return new ApprovableObserver();
        });
    }

    public function boot(): void
    {
        Blueprint::mixin(new ApprovalSchemaMethods);
    }
}
