<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasUuid;

class Typology extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasUuid;
    
    protected $guarded = ['id'];
}
