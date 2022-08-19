<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasUuid;

class Apartment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasUuid;

    protected $guarded = ['id'];

    public function typologies()
    {
        return $this->belongsToMany(Typology::class, 'apartment_typology', 'apartment_id', 'typology_id')->withTimestamps();
    }

    public function apartmentHistories()
    {
        return $this->hasMany(ApartmentHistory::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
