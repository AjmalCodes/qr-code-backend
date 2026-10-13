<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Restaurant = 'restaurant';
    case Waiter = 'waiter';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Restaurant => 'Restaurant Owner',
            self::Waiter => 'Waiter',
            self::User => 'User',
        };
    }
}
