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
}