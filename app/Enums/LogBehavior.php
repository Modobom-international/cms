<?php

namespace App\Enums;

final class LogBehavior
{
    const COUNTRIES = [
        'Czech',
        'Croatia',
        'DenMark',
        'Malaysia',
        'Montenegro',
        'Luxembourg',
        'Thailand',
        'Slovenia',
        'Romania'
    ];

    const PLATFORMS = [
        'Google',
        'Facebook',
        'Twitter',
        'Tiktok',
        'Twitch'
    ];

    const PARAM = [
        'id',
        'app',
        'platform',
        'country',
        'network',
        'date',
        'timeutc'
    ];
    
    const CACHE_DATE = 'cache_date';
    const CACHE_MENU = 'cache_menu';
    const CACHE_KEYWORD = 'cache_keyword';
}
