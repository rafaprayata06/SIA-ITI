<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Membuat ID berprefiks (usr_, std_, lec_, sp_) seperti diminta kontrak bagian 2.2,
 * jadi tidak ada ID berupa angka berurutan.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasPrefixedId
{
    public static function bootHasPrefixedId(): void
    {
        static::creating(function (Model $model): void {
            $key = $model->getKeyName();

            if (blank($model->{$key})) {
                $model->{$key} = $model->idPrefix().'_'.Str::uuid();
            }
        });
    }

    abstract public function idPrefix(): string;
}
