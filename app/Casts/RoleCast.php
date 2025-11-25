<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class RoleCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return match ($value) {
            1 => 'accountant',
            2 => 'announcer',
            3 => 'marketer',
            4 => 'support',
            default => null
        };
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return match ($value) {
            'accountant' => 1,
            'announcer' => 2,
            'marketer' => 3,
            'support' => 4,
            default => null
        };
    }

    /**
     * Get the full list of available staff roles.
     * Returns an array of id-name pairs, suitable for frontend consumption.
     */
    public static function roles(): array
    {
        return [
            ['id' => 1, 'name' => 'accountant'],
            ['id' => 2, 'name' => 'announcer'],
            ['id' => 3, 'name' => 'marketer'],
            ['id' => 4, 'name' => 'support'],
        ];
    }
}
