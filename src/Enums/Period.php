<?php

declare(strict_types=1);

namespace Vulpecula\Datum\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;

enum Period: int
{
    case HOUR = 0;
    case SIXHOUR = 1;
    case HALFDAY = 2;
    case DAY = 3;
    case WEEK = 4;
    case MONTH = 5;
    case QUARTER = 6;
    case HALFYEAR = 7;
    case YEAR = 8;
    case TAXYEAR = 9;

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
            self::TAXYEAR => __('datum::periods.taxyear'),
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
        return $this->period() * 60;
    }

    /**
     * Function maxDataPoints.
     */
    public function maxDataPoints(): int
    {
        return match ($this) {
            self::HOUR => 60,
            self::SIXHOUR, self::YEAR, self::TAXYEAR => 12,
            self::HALFDAY, self::DAY => 24,
            self::WEEK => 7,
            self::MONTH => CarbonImmutable::now()->daysInMonth,
            self::QUARTER => (int) (CarbonImmutable::now()->daysInYear / 4),
            self::HALFYEAR => 6,
        };
    }

    /**
     * Function period.
     */
    public function graphPeriod(): ?Period
    {
        return match ($this) {
            self::HOUR => null,
            self::SIXHOUR => self::HOUR,
            self::HALFDAY => self::HOUR,
            self::DAY => self::HOUR,
            self::WEEK => self::DAY,
            self::MONTH => self::DAY,
            self::QUARTER => self::DAY,
            self::HALFYEAR => self::MONTH,
            self::YEAR => self::MONTH,
            self::TAXYEAR => self::MONTH,
        };
    }

    /**
     * Function period.
     */
    public function maxCount(): int
    {
        return match ($this) {
            self::HOUR => 24,
            self::SIXHOUR => 30 * 4,
            self::HALFDAY => 30 * 2,
            self::DAY => 365,
            self::WEEK => 4,
            self::MONTH => 12,
            self::QUARTER => 4,
            self::HALFYEAR => 2,
            self::YEAR => 10,
            self::TAXYEAR => 10,
        };
    }

    /**
     * Function period.
     */
    public function period(): int
    {
        return match ($this) {
            self::HOUR => 60,
            self::SIXHOUR => 60 * 6,
            self::HALFDAY => 60 * 12,
            self::DAY => 60 * 24,
            self::WEEK => 60 * 24 * 7,
            self::MONTH => 60 * 24 * 30,
            self::QUARTER => 60 * 24 * (int) 365 / 4,
            self::HALFYEAR => 60 * 24 * (int) 365 / 2,
            self::YEAR => 60 * 24 * 365,
            self::TAXYEAR => 60 * 24 * 364,
        };
    }

    /**
     * Function currentBucket.
     */
    public function currentBucket(): int
    {
        $now = CarbonImmutable::now();

        return $this->getBucketForTimestamp($now->getTimestamp());
    }

    /**
     * Function getBucketForTimestamp.
     */
    public function getBucketForTimestamp($timestamp): int
    {
        $now = CarbonImmutable::createFromTimestamp($timestamp);

        return match ($this) {
            self::HOUR => $now->startOfHour()->getTimestamp(),
            self::SIXHOUR => $now->startOfDay()->addHours((int) ($now->hour / 6) * 6)->getTimestamp(),
            self::HALFDAY => ($now->hour < 12 ? $now->startOfDay() : $now->startOfDay()->addHours(12))->getTimestamp(),
            self::DAY => $now->startOfDay()->getTimestamp(),
            self::WEEK => $now->startOfWeek()->getTimestamp(),
            self::MONTH => $now->startOfMonth()->getTimestamp(),
            self::QUARTER => $now->startOfQuarter()->getTimestamp(),
            self::HALFYEAR => $now->startOfYear()->addMonths((int) (($now->month - 1) / 6) * 6)->getTimestamp(),
            self::YEAR => $now->startOfYear()->getTimestamp(),
            self::TAXYEAR => ($now->gte(CarbonImmutable::createFromDate($now->year, 4, 6)->startOfDay())
                ? CarbonImmutable::createFromDate($now->year, 4, 6)
                : CarbonImmutable::createFromDate($now->year - 1, 4, 6)
            )->startOfDay()->getTimestamp(),
        };
    }

    /**
     * Function getBucketForTimestamp.
     */
    public function getOldestBucketTimestamp(): int
    {
        $now = CarbonImmutable::now();

        return match ($this) {
            self::HOUR => $now->startOfHour()->subHours($this->maxCount())->getTimestamp(),
            self::SIXHOUR => $now->startOfDay()->addHours((int) ($now->hour / 6) * 6)->subHours($this->maxCount() * 6)->getTimestamp(),
            self::HALFDAY => ($now->hour < 12 ? $now->startOfDay() : $now->startOfDay()->addHours(12))->subHours($this->maxCount() * 12)->getTimestamp(),
            self::DAY => $now->startOfDay()->subDays($this->maxCount())->getTimestamp(),
            self::WEEK => $now->startOfWeek()->subWeeks($this->maxCount())->getTimestamp(),
            self::MONTH => $now->startOfMonth()->subMonths($this->maxCount())->getTimestamp(),
            self::QUARTER => $now->startOfQuarter()->subQuarters($this->maxCount())->getTimestamp(),
            self::HALFYEAR => $now->startOfYear()->addMonths((int) (($now->month - 1) / 6) * 6)->subMonths($this->maxCount() * 6)->getTimestamp(),
            self::YEAR => $now->startOfYear()->subYears($this->maxCount())->getTimestamp(),
            self::TAXYEAR => ($now->gte(CarbonImmutable::createFromDate($now->year, 4, 6)->startOfDay())
                ? CarbonImmutable::createFromDate($now->year, 4, 6)
                : CarbonImmutable::createFromDate($now->year - 1, 4, 6)
            )->startOfDay()->subYears($this->maxCount())->getTimestamp(),
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
            self::WEEK, self::QUARTER, self::MONTH, self::TAXYEAR => 'Y-m-d',
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
            self::TAXYEAR => CarbonPeriodImmutable::create(
                $now->gte(CarbonImmutable::createFromDate($now->year, 4, 6))
                    ? CarbonImmutable::createFromDate($now->year + 1, 4, 6)->startOfDay()
                    : CarbonImmutable::createFromDate($now->year, 4, 6)->startOfDay(),
                '-1 month',
                $this->maxDataPoints() + 1
            ),
        };

        return collect($period->toArray())->reverse()->values()->all();
    }

    public function getWindow(): array
    {
        $now = CarbonImmutable::now()->startOfMinute();

        return match ($this) {
            self::HOUR => $window = [
                'start' => $now->subHour()->getTimestamp(),
                'end' => $now->getTimestamp(),
            ],
            self::SIXHOUR => $window = [
                'start' => $now->subHours(6)->startOfHour()->getTimestamp(),
                'end' => $now->startOfHour()->getTimestamp(),
            ],
            self::HALFDAY => $window = [
                'start' => $now->subHours(12)->startOfHour()->getTimestamp(),
                'end' => $now->startOfHour()->getTimestamp(),
            ],
            self::DAY => $window = [
                'start' => $now->subDay()->startOfHour()->getTimestamp(),
                'end' => $now->startOfHour()->getTimestamp(),
            ],
            self::WEEK => $window = [
                'start' => $now->subWeek()->startOfDay()->getTimestamp(),
                'end' => $now->startOfDay()->getTimestamp(),
            ],
            self::MONTH => $window = [
                'start' => $now->subMonth()->startOfDay()->getTimestamp(),
                'end' => $now->startOfDay()->getTimestamp(),
            ],
            self::QUARTER => $window = [
                'start' => $now->subMonths(3)->startOfDay()->getTimestamp(),
                'end' => $now->startOfDay()->getTimestamp(),
            ],
            self::HALFYEAR => $window = [
                'start' => $now->subMonths(6)->startOfDay()->getTimestamp(),
                'end' => $now->startOfDay()->getTimestamp(),
            ],
            self::YEAR => $window = [
                'start' => $now->subMonths(11)->startOfMonth()->getTimestamp(),
                'end' => $now->startOfMonth()->getTimestamp(),
            ],
            self::TAXYEAR => $window = [
                'start' => ($now->isAfter(CarbonImmutable::createFromDate($now->year, 4, 6)) ? CarbonImmutable::createFromDate($now->year, 4, 6) : CarbonImmutable::createFromDate($now->year - 1, 4, 6))->startOfDay()->getTimestamp(),
                'end' => ($now->isAfter(CarbonImmutable::createFromDate($now->year, 4, 6)) ? CarbonImmutable::createFromDate($now->year + 1, 4, 5) : CarbonImmutable::createFromDate($now->year, 4, 5))->endOfDay()->getTimestamp(),
            ],
        };
    }
}
