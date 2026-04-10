<?php

namespace XLaravel\EloquentApproval\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use XLaravel\EloquentApproval\Approvable;
use XLaravel\EloquentApproval\Tests\Database\Factories\EntityFactory;

class Entity extends Model
{
    use Approvable, HasFactory;

    static protected function newFactory()
    {
        return new EntityFactory;
    }

    protected $guarded = [];
}
