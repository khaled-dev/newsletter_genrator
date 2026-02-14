<?php

namespace App\Enums;

enum Sources: string
{
    case GUARDIAN = 'guardian';
    case NEWS_API = 'news_api';
    case NY_TIMES = 'ny_times';

    public function label(): string
    {
        return __("sources.{$this->value}");
    }
}
