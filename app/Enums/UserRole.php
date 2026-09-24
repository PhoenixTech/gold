<?php

namespace App\Enums;

enum UserRole: string
{
    case Developer = 'DEVELOPER';
    case Admin = 'ADMIN';
    case User = 'USER';
    case Suspended = 'SUSPENDED';
    case Visitor = 'VISITOR';
    case Courier = 'COURIER';

    public function label(): string
    {
        return __($this->value);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
