<?php

namespace XLaravel\EloquentApproval;

use Illuminate\Http\Request;

trait HandlesApproval
{
    public function performApproval(int|string $key, Request $request): array
    {
        $model = $this->findOrFail($key);

        if ($request->input('approval_status') == ApprovalStatuses::APPROVED) {
            $result = $model->approve();
        } elseif ($request->input('approval_status') == ApprovalStatuses::PENDING) {
            $result = $model->suspend();
        } elseif ($request->input('approval_status') == ApprovalStatuses::REJECTED) {
            $result = $model->reject();
        } else {
            abort(422, 'Invalid approval_status value');
        }

        if (!$result) {
            abort(403, 'The operation failed.');
        }

        return [
            $model->getApprovalStatusColumn() =>
                $model->{$model->getApprovalStatusColumn()},

            $model->getApprovalAtColumn() =>
                $model->{$model->getApprovalAtColumn()},
        ];
    }

    protected function findOrFail(int|string $key): mixed
    {
        return $this->model()::withAnyApproval()->findOrFail($key);
    }

    abstract protected function model(): string;
}
