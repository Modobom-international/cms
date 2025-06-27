<?php

namespace App\Enums;

final class Site
{
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    // Platform constants
    const PLATFORM_GOOGLE = 'google';
    const PLATFORM_FACEBOOK = 'facebook';
    const PLATFORM_TIKTOK = 'tiktok';

    /**
     * Get all available platforms
     *
     * @return array
     */
    public static function getPlatforms()
    {
        return [
            self::PLATFORM_GOOGLE,
            self::PLATFORM_FACEBOOK,
            self::PLATFORM_TIKTOK,
        ];
    }
}