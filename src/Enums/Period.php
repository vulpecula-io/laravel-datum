<?php

declare(strict_types=1);

namespace Vulpecula\Datum\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;

enum Period: int
{
    case HOUR = 60;
    case SIXHOUR = 360;
    case HALFDAY = 720;
    case DAY = 1440;
    case WEEK = 10080;
    case MONTH = 43200;
    case QUARTER = 129600;
    case HALFYEAR = 259200;
    case YEAR = 525600;

    /**
     * Function label.
     */
    public function label(): string
    {
        return match ($this) {
            self::HOUR => __('datum::periods.hour'),
            self::SIXHOUR => __('datum::periods.sixhour'),
            self::HALFDAY => __('datum::periods.halfday'),
            self::DAY => __('datum::periods.day'),
            self::WEEK => __('datum::periods.week'),
            self::MONTH => __('datum::periods.month'),
            self::QUARTER => __('datum::periods.quarter'),
            self::HALFYEAR => __('datum::periods.halfyear'),
            self::YEAR => __('datum::periods.year'),
        };
    }

    /**
     * Function secondsPerPeriod.
     */
    public function secondsPerPeriod(): float|int
    {
        return $this->totalSeconds() / $this->maxDataPoints();
    }

    /**
     * Function totalSeconds.
     */
    public function totalSeconds(): int
    {
        return $this->value * 60;
    }

    /**
     * Function maxDataPoints.
     */
    public function maxDataPoints(): int
    {
        return match ($this) {
            self::HOUR => 60,
            self::SIXHOUR, self::YEAR => 12,
            self::HALFDAY, self::DAY => 24,
            self::WEEK => 7,
            self::MONTH => CarbonImmutable::now()->daysInMonth,
            self::QUARTER => (int) CarbonImmutable::now()->daysInYear / 4,
            self::HALFYEAR => 6,
        };
    }

    /**
     * Function period.
     */
    public function period(): int
    {
        return $this->value;
    }

    /**
     * Function currentBucket.
     */
    public function currentBucket(): int
    {
        $now = CarbonImmutable::now();

        return match ($this) {
            self::HOUR => $now->startOfMinute()->getTimestamp(),
            self::SIXHOUR, self::DAY, self::HALFDAY => $now->startOfHour()->getTimestamp(),
            self::WEEK, self::QUARTER, self::MONTH => $now->startOfDay()->getTimestamp(),
            self::HALFYEAR, self::YEAR => $now->startOfMonth()->getTimestamp(),
        };
    }

    /**
     * Function getBucketForTimestamp.
     */
    public function getBucketForTimestamp($timestamp): int
    {
        $now = CarbonImmutable::createFromTimestamp($timestamp);

        return match ($this) {
            self::HOUR => $now->startOfMinute()->getTimestamp(),
            self::SIXHOUR, self::DAY, self::HALFDAY => $now->startOfHour()->getTimestamp(),
            self::WEEK, self::QUARTER, self::MONTH => $now->startOfDay()->getTimestamp(),
            self::HALFYEAR, self::YEAR => $now->startOfMonth()->getTimestamp(),
        };
    }

    /**
     * Function getDateTimeFormat.
     */
    public function getDateTimeFormat(): string
    {
        return match ($this) {
            self::HOUR => 'Y-m-d H:i:s',
            self::SIXHOUR, self::DAY, self::HALFDAY => 'Y-m-d H:i',
            self::WEEK, self::QUARTER, self::MONTH => 'Y-m-d',
            self::HALFYEAR, self::YEAR => 'Y-m-01',
        };
    }

    /**
     * Function getBuckets.
     */
    public function getBuckets(): array
    {
        $now = CarbonImmutable::now();
        $period = match ($this) {
            self::HOUR => CarbonPeriodImmutable::create($now->startOfMinute(), '-1 minute', $this->maxDataPoints()),
            self::SIXHOUR => CarbonPeriodImmutable::create($now->startOfHour(), '-1 hour', $this->maxDataPoints()),
            self::HALFDAY => CarbonPeriodImmutable::create($now->startOfHour(), '-1 hour', $this->maxDataPoints()),
            self::DAY => CarbonPeriodImmutable::create($now->startOfHour(), '-1 day', $this->maxDataPoints()),
            self::WEEK => CarbonPeriodImmutable::create($now->startOfDay(), '-1 day', $this->maxDataPoints()),
            self::MONTH => CarbonPeriodImmutable::create($now->startOfDay(), '-1 day', $this->maxDataPoints()),
            self::QUARTER => CarbonPeriodImmutable::create($now->startOfDay(), '-1 day', $this->maxDataPoints()),
            self::HALFYEAR => CarbonPeriodImmutable::create($now->startOfMonth(), '-1 month', $this->maxDataPoints()),
            self::YEAR => CarbonPeriodImmutable::create($now->startOfMonth(), '-1 month', $this->maxDataPoints()),
        };

        return collect($period->toArray())->reverse()->values()->all();
    }
}
