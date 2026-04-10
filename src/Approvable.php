<?php

namespace XLaravel\EloquentApproval;

use Exception;
use Illuminate\Database\Eloquent\Model;

trait Approvable
{
    use ApprovalRequired;
    use ApprovalEvents;

    public static function bootApprovable(): void
    {
        static::addGlobalScope(new ApprovalScope());

        static::creating(function (Model $model) {
            app(ApprovableObserver::class)->creating($model);
        });

        static::updating(function (Model $model) {
            app(ApprovableObserver::class)->updating($model);
        });
    }

    public function isApprovalScopeDisabled(): bool
    {
        return false;
    }

    public function getApprovalStatusColumn(): string
    {
        return defined('static::APPROVAL_STATUS') ? static::APPROVAL_STATUS : 'approval_status';
    }

    public function getQualifiedApprovalStatusColumn(): string
    {
        return $this->getTable() . '.' . $this->getApprovalStatusColumn();
    }

    public function getApprovalAtColumn(): string
    {
        return defined('static::APPROVAL_AT') ? static::APPROVAL_AT : 'approval_at';
    }

    public function approve(): ?bool
    {
        return $this->updateApproval(
            ApprovalStatuses::APPROVED,
            'approving',
            'approved'
        );
    }

    public function reject(): ?bool
    {
        return $this->updateApproval(
            ApprovalStatuses::REJECTED,
            'rejecting',
            'rejected'
        );
    }

    public function suspend(): ?bool
    {
        return $this->updateApproval(
            ApprovalStatuses::PENDING,
            'suspending',
            'suspended'
        );
    }

    /**
     * @throws Exception
     */
    protected function updateApproval(string $status, string $beforeEvent, string $afterEvent): ?bool
    {
        if (is_null($this->getKeyName())) {
            throw new Exception('No primary key defined on model.');
        }

        if (!$this->exists) {
            return null;
        }

        if ($this->{$this->getApprovalStatusColumn()} == $status) {
            return false;
        }

        if ($this->fireModelEvent($beforeEvent) === false) {
            return false;
        }

        $this->{$this->getApprovalStatusColumn()} = $status;

        $time = $this->freshTimestamp();

        $this->{$this->getApprovalAtColumn()} = $time;

        $columns = [
            $this->getApprovalStatusColumn() => $status,
            $this->getApprovalAtColumn() => $this->fromDateTime($time),
        ];

        $this->getConnection()
            ->table($this->getTable())
            ->where($this->getKeyName(), $this->getKey())
            ->update($columns);

        $this->fireModelEvent($afterEvent, false);
        $this->fireModelEvent('approvalChanged', false);

        return true;
    }

    public function isPending(): ?bool
    {
        return $this->hasApprovalStatus(ApprovalStatuses::PENDING);
    }

    public function isApproved(): ?bool
    {
        return $this->hasApprovalStatus(ApprovalStatuses::APPROVED);
    }

    public function isRejected(): ?bool
    {
        return $this->hasApprovalStatus(ApprovalStatuses::REJECTED);
    }

    protected function hasApprovalStatus(string $status): ?bool
    {
        if (!$this->exists) {
            return null;
        }

        return $this->{$this->getApprovalStatusColumn()} == $status;
    }
}
