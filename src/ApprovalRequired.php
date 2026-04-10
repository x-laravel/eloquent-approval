<?php

namespace XLaravel\EloquentApproval;

trait ApprovalRequired
{
    public function approvalRequired(): array
    {
        return ['*'];
    }

    public function approvalNotRequired(): array
    {
        return [];
    }

    public function isApprovalRequired(string $key): bool
    {
        if ($this->isApprovalNotRequired($key)) {
            return false;
        }

        if (in_array($key, $this->approvalRequired())
            || $this->approvalRequired() == ['*']) {
            return true;
        }

        return ! empty($this->approvalNotRequired());
    }

    public function isApprovalNotRequired(string $key): bool
    {
        return in_array($key, $this->approvalNotRequired());
    }
}
