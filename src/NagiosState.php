<?php declare(strict_types=1);

namespace FredBradley\CranleighSNMP;

enum NagiosState: int
{
    case Ok       = 0;
    case Warning  = 1;
    case Critical = 2;
    case Unknown  = 3;

    public function cssClass(): string
    {
        return match ($this) {
            self::Ok       => 'ok',
            self::Warning  => 'warning',
            self::Critical => 'notok',
            self::Unknown  => 'unknown',
        };
    }
}
