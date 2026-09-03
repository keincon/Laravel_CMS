<?php

declare(strict_types=1);

namespace App\Enums;

enum MetaType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Float = 'float';
    case Boolean = 'boolean';
    case Json = 'json';
    case Text = 'text';
    case Datetime = 'datetime';

    public function cast(mixed $value): mixed
    {
        return match ($this) {
            self::String, self::Text => $value === null ? null : (string) $value,
            self::Integer => $value === null || $value === '' ? null : (int) $value,
            self::Float => $value === null || $value === '' ? null : (float) $value,
            self::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
            self::Json => is_string($value)
                ? json_decode($value, true, 512, JSON_THROW_ON_ERROR)
                : $value,
            self::Datetime => $value,
        };
    }

    public function serialize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::Json => is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR),
            self::Boolean => $value ? '1' : '0',
            self::Datetime => $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d H:i:s')
                : (string) $value,
            default => (string) $value,
        };
    }
}
