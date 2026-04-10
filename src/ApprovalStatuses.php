<?php

namespace XLaravel\EloquentApproval;

final class ApprovalStatuses
{
    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';

    public static function isValid(string $status): bool
    {
        return in_array($status, [self::PENDING, self::APPROVED, self::REJECTED], true);
    }

    private function __construct()
    {
    }
}
