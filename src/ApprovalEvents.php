<?php

namespace XLaravel\EloquentApproval;

use Closure;

trait ApprovalEvents
{
    public static function approving(Closure|string|array $callback): void
    {
        static::registerModelEvent('approving', $callback);
    }

    public static function approved(Closure|string|array $callback): void
    {
        static::registerModelEvent('approved', $callback);
    }

    public static function suspending(Closure|string|array $callback): void
    {
        static::registerModelEvent('suspending', $callback);
    }

    public static function suspended(Closure|string|array $callback): void
    {
        static::registerModelEvent('suspended', $callback);
    }

    public static function rejecting(Closure|string|array $callback): void
    {
        static::registerModelEvent('rejecting', $callback);
    }

    public static function rejected(Closure|string|array $callback): void
    {
        static::registerModelEvent('rejected', $callback);
    }

    public static function approvalChanged(Closure|string|array $callback): void
    {
        static::registerModelEvent('approvalChanged', $callback);
    }

    public function getObservableEvents(): array
    {
        return array_merge(parent::getObservableEvents(), [
            'approving',
            'suspending',
            'rejecting',
            'approved',
            'suspended',
            'rejected',
            'approvalChanged',
        ]);
    }
}
