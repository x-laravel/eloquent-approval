<?php

namespace XLaravel\EloquentApproval;

trait ApprovalFactoryStates
{
    public function approved(): static
    {
        return $this->state(fn() => $this->approvalState(ApprovalStatuses::APPROVED));
    }

    public function suspended(): static
    {
        return $this->state(fn() => $this->approvalState(ApprovalStatuses::PENDING));
    }

    public function rejected(): static
    {
        return $this->state(fn() => $this->approvalState(ApprovalStatuses::REJECTED));
    }

    protected function approvalState(string $status): array
    {
        $model = new ($this->modelName());

        return [
            $model->getApprovalStatusColumn() => $status,
            $model->getApprovalAtColumn() => now(),
        ];
    }
}
