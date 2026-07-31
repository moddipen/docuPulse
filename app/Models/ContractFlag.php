<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractFlag extends Model
{
    protected $fillable = ['amount', 'reason', 'tenant_id'];
}
