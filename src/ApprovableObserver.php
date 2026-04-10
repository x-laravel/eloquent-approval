<?php

namespace XLaravel\EloquentApproval;

use Illuminate\Database\Eloquent\Model;

class ApprovableObserver
{
    public function creating(Model $model): void
    {
        $this->initializeApprovalStatus($model);
    }

    public function updating(Model $model): void
    {
        $this->resetApprovalStatus($model);
    }

    protected function initializeApprovalStatus(Model $model): void
    {
        if ($model->isDirty($model->getApprovalStatusColumn())) {
            return;
        }

        $this->suspend($model);
    }

    protected function resetApprovalStatus(Model $model): void
    {
        $modifiedAttributes = array_keys(
            $model->getDirty()
        );

        foreach ($modifiedAttributes as $name) {
            if ($model->isApprovalRequired($name)) {
                $this->suspend($model);

                return;
            }
        }
    }

    protected function suspend(Model $model): void
    {
        $model->setAttribute(
            $model->getApprovalStatusColumn(),
            ApprovalStatuses::PENDING
        );

        $model->setAttribute(
            $model->getApprovalAtColumn(),
            null
        );
    }
}
