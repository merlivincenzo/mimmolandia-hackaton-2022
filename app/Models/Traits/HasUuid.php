<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    public static function bootHasUuid()
    {
        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? Str::uuid();
        });
    }

    protected function getArrayableItems(array $values)
    {
        if (!in_array('id', $this->hidden)) {
            // $this->hidden[] = 'id';
        }

        return parent::getArrayableItems($values);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
