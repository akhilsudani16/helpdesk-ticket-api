<?php

namespace App\Enums;

enum DeviceName: string
{
    case WEB_BROWSER = 'Web Browser';
    case MOBILE_APP = 'Mobile App';
    case DESKTOP_APP = 'Desktop App';
    case POSTMAN = 'Postman';
    case API_CLIENT = 'API Client';
    case CURL = 'cURL';
    case INSOMNIA = 'Insomnia';
    case THUNDER_CLIENT = 'Thunder Client';

    /**
     * Get all device name values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get device type category.
     */
    public function category(): string
    {
        return match ($this) {
            self::WEB_BROWSER => 'Browser',
            self::MOBILE_APP => 'Mobile',
            self::DESKTOP_APP => 'Desktop',
            self::POSTMAN, self::API_CLIENT, self::CURL, self::INSOMNIA, self::THUNDER_CLIENT => 'API Tool',
        };
    }

    /**
     * Check if device name is valid.
     */
    public static function isValid(string $deviceName): bool
    {
        return in_array($deviceName, self::values(), true);
    }
}