<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Vulpecula\Datum\Enums\Period;
use Vulpecula\Datum\Facades\Datum;
use Vulpecula\Datum\Storage\DatabaseStorage;

it('trims values at or past expiry', function () {
    Date::setTestNow('2000-01-01 00:00:04');
    Datum::set('type', 'foo', 'value');
    Date::setTestNow('2000-01-01 00:00:05');
    Datum::set('type', 'bar', 'value');
    Date::setTestNow('2000-01-01 00:00:06');
    Datum::set('type', 'baz', 'value');
    Datum::ingest();

    Datum::stopRecording();
    Date::setTestNow('2001-01-01 00:00:05');
    App::make(DatabaseStorage::class)->trim();

    expect(DB::table('datum_values')->pluck('key')->all())->toBe(['baz']);
});

it('trims entries at or before year after timestamp', function () {
    Date::setTestNow('2000-01-01 00:00:04');
    Datum::record('foo', 'xxxx', 1);
    Date::setTestNow('2000-01-01 00:00:05');
    Datum::record('bar', 'xxxx', 1);
    Date::setTestNow('2000-01-01 00:00:06');
    Datum::record('baz', 'xxxx', 1);
    Datum::ingest();

    Datum::stopRecording();
    Date::setTestNow('2001-01-01 00:00:05');
    App::make(DatabaseStorage::class)->trim();

    expect(DB::table('datum_entries')->pluck('type')->all())->toBe(['baz']);
});

it('trims aggregates once the 1 hour bucket is no longer relevant', function () {
    Date::setTestNow('2000-01-01 00:00:59'); // Bucket: 2000-01-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 60)->count()))->toBe(1);

    Date::setTestNow('2000-01-01 01:00:01'); // Bucket: 2000-01-01 00:01:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 60)->count()))->toBe(2);

    Datum::stopRecording();
    Date::setTestNow('2000-01-01 23:59:59'); // 1 second before the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', 60)->count())->toBe(2);

    Date::setTestNow('2000-01-02 00:00:00'); // The second the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', 60)->count())->toBe(1);
});

it('trims aggregates once the 6 hour bucket is no longer relevant', function () {
    Date::setTestNow('2000-01-01 00:05:59'); // Bucket: 2000-01-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 360)->count()))->toBe(1);

    Date::setTestNow('2000-01-01 06:06:00'); // Bucket: 2000-01-01 00:06:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 360)->count()))->toBe(2);

    Datum::stopRecording();
    Date::setTestNow('2000-01-01 05:59:59'); // 1 second before the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', 360)->count())->toBe(2);

    Date::setTestNow('2000-01-31 00:00:00'); // The second the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', 360)->count())->toBe(1);
});

it('trims aggregates once the 12 hour bucket is no longer relevant', function () {
    Date::setTestNow('2000-01-01 00:05:59'); // Bucket: 2000-01-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 360)->count()))->toBe(1);

    Date::setTestNow('2000-01-01 16:06:00'); // Bucket: 2000-01-01 00:06:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 360)->count()))->toBe(2);

    Datum::stopRecording();
    Date::setTestNow('2000-01-01 11:59:59'); // 1 second before the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', 360)->count())->toBe(2);

    Date::setTestNow('2000-01-31 00:00:00'); // The second the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', 360)->count())->toBe(1);
});

it('trims aggregates once the 24 hour bucket is no longer relevant', function () {
    Date::setTestNow('2000-01-01 00:23:59'); // Bucket: 2000-01-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 1440)->count()))->toBe(1);

    Date::setTestNow('2000-01-02 00:24:00'); // Bucket: 2000-01-02 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 1440)->count()))->toBe(2);

    Datum::stopRecording();
    Date::setTestNow('2000-01-30 23:59:59'); // 1 second before the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', 1440)->count())->toBe(2);

    Date::setTestNow('2000-01-31 00:00:00'); // The second the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', 1440)->count())->toBe(1);
});

it('trims aggregates once the 7 day bucket is no longer relevant', function () {
    Date::setTestNow('2000-01-01 02:23:59'); // Bucket: 1999-12-27 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 10080)->count()))->toBe(1);

    Date::setTestNow('2000-01-03 02:24:00'); // Bucket: 2000-01-03 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 10080)->count()))->toBe(2);

    Datum::stopRecording();
    Date::setTestNow('2000-01-23 23:59:59'); // 1 second before the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', 10080)->count())->toBe(2);

    Date::setTestNow('2000-01-24 00:00:00'); // The second the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', 10080)->count())->toBe(1);
});

it('trims aggregates once the 1 month bucket is no longer relevant', function () {
    Date::setTestNow('2000-01-01 02:23:59'); // Bucket: 2000-01-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 60 * 24 * 30)->count()))->toBe(1);

    Date::setTestNow('2000-02-01 02:24:00'); // Bucket: 2000-02-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 60 * 24 * 30)->count()))->toBe(2);

    Datum::stopRecording();
    Date::setTestNow('2000-12-25 23:59:59'); // 1 second before the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', 60 * 24 * 30)->count())->toBe(2);

    Date::setTestNow('2000-12-26 00:00:00'); // The second the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', 60 * 24 * 30)->count())->toBe(1);
});

it('trims aggregates once the quarter bucket is no longer relevant', function () {
    $period = Period::QUARTER;

    Date::setTestNow('2000-01-01 02:23:59'); // Bucket: 2000-01-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', $period->value)->count()))->toBe(1);

    Date::setTestNow('2000-04-01 02:24:00'); // Bucket: 2000-04-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', $period->value)->count()))->toBe(2);

    Datum::stopRecording();
    Date::setTestNow('2000-12-30 23:59:59'); // 1 second before the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', $period->value)->count())->toBe(2);

    Date::setTestNow('2000-12-31 00:00:00'); // The second the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', $period->value)->count())->toBe(1);
});

it('trims aggregates once the half year bucket is no longer relevant', function () {
    $period = Period::HALFYEAR;

    Date::setTestNow('2000-01-01 02:23:59'); // Bucket: 2000-01-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', $period->value)->count()))->toBe(1);

    Date::setTestNow('2000-07-01 02:24:00'); // Bucket: 2000-07-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', $period->value)->count()))->toBe(2);

    Datum::stopRecording();
    Date::setTestNow('2000-12-30 23:59:59'); // 1 second before the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', $period->value)->count())->toBe(2);

    Date::setTestNow('2000-12-31 00:00:00'); // The second the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', $period->value)->count())->toBe(1);
});

it('trims aggregates once the year bucket is no longer relevant', function () {
    $period = Period::YEAR;

    Date::setTestNow('2000-01-01 02:23:59'); // Bucket: 2000-01-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', $period->value)->count()))->toBe(1);

    Date::setTestNow('2001-01-01 02:24:00'); // Bucket: 2000-07-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', $period->value)->count()))->toBe(2);

    Datum::stopRecording();
    Date::setTestNow('2009-12-28 23:59:59'); // 1 second before the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', $period->value)->count())->toBe(2);

    Date::setTestNow('2009-12-29 00:00:00'); // The second the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', $period->value)->count())->toBe(1);
});

it('trims aggregates once the tax year bucket is no longer relevant', function () {
    $period = Period::TAXYEAR;

    Date::setTestNow('2000-01-01 02:23:59'); // Bucket: 1999-04-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', $period->value)->count()))->toBe(1);

    Date::setTestNow('2001-01-01 02:24:00'); // Bucket: 2000-04-01 00:00:00
    Datum::record('foo', 'xxxx', 1)->count();
    Datum::ingest();
    expect(Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', $period->value)->count()))->toBe(2);

    Datum::stopRecording();
    Date::setTestNow('2009-03-23 23:59:59'); // 1 second before the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', $period->value)->count())->toBe(2);

    Date::setTestNow('2009-03-24 00:00:00'); // The second the oldest bucket become irrelevant.
    App::make(DatabaseStorage::class)->trim();
    expect(DB::table('datum_aggregates')->where('period', $period->value)->count())->toBe(1);
});
