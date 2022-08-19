<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasUuid;

class ApartmentTypologies extends Pivot
{
    use SoftDeletes;
    use HasUuid;

    protected $guarded = ['id'];
}
