<?php

namespace XLaravel\EloquentApproval;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ApprovalScope implements Scope
{
    protected array $extensions = [
        'WithAnyApproval',
        'OnlyPending',
        'OnlyRejected',
        'OnlyApproved',
        'Approve',
        'Reject',
        'Suspend',
    ];

    public function apply(Builder $builder, Model $model): void
    {
        if ($model->isApprovalScopeDisabled()) {
            return;
        }

        $builder->where(
            $model->getQualifiedApprovalStatusColumn(),
            ApprovalStatuses::APPROVED
        );
    }

    public function extend(Builder $builder): void
    {
        foreach ($this->extensions as $extension) {
            $this->{'add'.$extension}($builder);
        }
    }

    protected function addWithAnyApproval(Builder $builder): void
    {
        $builder->macro('withAnyApproval', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    protected function addOnlyPending(Builder $builder): void
    {
        $builder->macro('onlyPending', function (Builder $builder) {
            return $this->onlyWithStatus($builder, ApprovalStatuses::PENDING);
        });
    }

    protected function addOnlyRejected(Builder $builder): void
    {
        $builder->macro('onlyRejected', function (Builder $builder) {
            return $this->onlyWithStatus($builder, ApprovalStatuses::REJECTED);
        });
    }

    protected function addOnlyApproved(Builder $builder): void
    {
        $builder->macro('onlyApproved', function (Builder $builder) {
            return $this->onlyWithStatus($builder, ApprovalStatuses::APPROVED);
        });
    }

    protected function onlyWithStatus(Builder $builder, string $status): Builder
    {
        $model = $builder->getModel();

        $builder->withAnyApproval()->where(
            $model->getQualifiedApprovalStatusColumn(),
            $status
        );

        return $builder;
    }

    protected function addApprove(Builder $builder): void
    {
        $builder->macro('approve', function (Builder $builder) {
            return $this->updateStatus($builder, ApprovalStatuses::APPROVED);
        });
    }

    protected function addReject(Builder $builder): void
    {
        $builder->macro('reject', function (Builder $builder) {
            return $this->updateStatus($builder, ApprovalStatuses::REJECTED);
        });
    }

    protected function addSuspend(Builder $builder): void
    {
        $builder->macro('suspend', function (Builder $builder) {
            return $this->updateStatus($builder, ApprovalStatuses::PENDING);
        });
    }

    protected function updateStatus(Builder $builder, string $status): int
    {
        $model = $builder->getModel();

        $builder->withAnyApproval();

        $model->timestamps = false;

        return $builder->update([
            $model->getApprovalStatusColumn() => $status,
            $model->getApprovalAtColumn() => $model->freshTimestampString(),
        ]);
    }
}
