<?php

namespace XLaravel\EloquentApproval;

class ApprovalSchemaMethods
{
    public function approvals(): \Closure
    {
        return function (array $options = []): void {
            $this->string($options['status_name'] ?? 'approval_status')
                ->default('pending');

            $this->timestamp($options['timestamp_name'] ?? 'approval_at')
                ->nullable();
        };
    }
}
