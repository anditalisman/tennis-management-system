<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'group'])]
class SystemSetting extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function put(string $key, mixed $value, string $group = 'general'): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }
}
