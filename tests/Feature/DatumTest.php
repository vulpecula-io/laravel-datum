<?php

declare(strict_types=1);

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Tests\StorageFake;
use Vulpecula\Datum\Contracts\Storage;
use Vulpecula\Datum\Entry;
use Vulpecula\Datum\Facades\Datum;
use Vulpecula\Datum\Value;

it('can filter records', function () {
    App::instance(Storage::class, $storage = new StorageFake);

    Datum::filter(fn ($value) => Entry::class === $value::class && 'keep' === $value->key ||
        Value::class === $value::class && 'keep' === $value->key);

    Datum::record('foo', 'ignore', 0);
    Datum::record('foo', 'keep', 0);
    Datum::set('baz', 'keep', '');
    Datum::set('baz', 'ignore', '');
    Datum::ingest();

    expect($storage->stored)
        ->toHaveCount(2)
        ->and($storage->stored[0])
        ->toBeInstanceOf(Entry::class)
        ->and($storage->stored[0]->key)
        ->toBe('keep')
        ->and($storage->stored[1])
        ->toBeInstanceOf(Value::class)
        ->and($storage->stored[1]->key)
        ->toBe('keep');
});

it('can trim records', function () {
    App::instance(Storage::class, $storage = new StorageFake);

    Datum::record('foo', 'delete', 0, now()->subDays(366));
    Datum::record('foo', 'keep', 0);

    Datum::ingest();

    expect($storage->stored)->toHaveCount(1);
});

it('can lazily capture entries', function () {
    App::instance(Storage::class, $storage = new StorageFake);

    Datum::record('entry', 'eager');
    Datum::lazy(function () {
        Datum::record('entry', 'lazy');
        Datum::set('value', 'lazy', '1');
    });
    Datum::set('value', 'eager', '1');
    Datum::ingest();

    expect($storage->stored)
        ->toHaveCount(4)
        ->and($storage->stored[0])
        ->toBeInstanceOf(Entry::class)
        ->and($storage->stored[0]->key)
        ->toBe('eager')
        ->and($storage->stored[1])
        ->toBeInstanceOf(Value::class)
        ->and($storage->stored[1]->key)
        ->toBe('eager')
        ->and($storage->stored[2])
        ->toBeInstanceOf(Entry::class)
        ->and($storage->stored[2]->key)
        ->toBe('lazy')
        ->and($storage->stored[3])
        ->toBeInstanceOf(Value::class)
        ->and($storage->stored[3]->key)
        ->toBe('lazy');
});

it('can flush the queue', function () {
    App::instance(Storage::class, $storage = new StorageFake);

    Datum::record('entry', 'eager');
    Datum::lazy(function () {
        Datum::record('entry', 'lazy');
    });
    Datum::flush();

    expect(Datum::ingest())->toBe(0);
});

it('can limit the buffer size of entries', function () {
    Config::set('datum.ingest.buffer', 4);

    Datum::record('type', 'key');
    expect(Datum::wantsIngesting())->toBeTrue();
    Datum::record('type', 'key');
    expect(Datum::wantsIngesting())->toBeTrue();
    Datum::record('type', 'key');
    expect(Datum::wantsIngesting())->toBeTrue();
    Datum::record('type', 'key');
    expect(Datum::wantsIngesting())->toBeTrue();
    Datum::record('type', 'key');
    expect(Datum::wantsIngesting())->toBeFalse();

    Datum::set('type', 'key', 'value');
    expect(Datum::wantsIngesting())->toBeTrue();
    Datum::set('type', 'key', 'value');
    expect(Datum::wantsIngesting())->toBeTrue();
    Datum::set('type', 'key', 'value');
    expect(Datum::wantsIngesting())->toBeTrue();
    Datum::set('type', 'key', 'value');
    expect(Datum::wantsIngesting())->toBeTrue();
    Datum::set('type', 'key', 'value');
    expect(Datum::wantsIngesting())->toBeFalse();
});

it('resolves lazy entries when considering the buffer', function () {
    Config::set('datum.ingest.buffer', 4);

    Datum::lazy(fn () => Datum::record('type', 'key'));
    expect(Datum::wantsIngesting())->toBeTrue();
    Datum::lazy(fn () => Datum::set('type', 'key', 'value'));
    expect(Datum::wantsIngesting())->toBeTrue();
    Datum::lazy(fn () => Datum::record('type', 'key'));
    expect(Datum::wantsIngesting())->toBeTrue();
    Datum::lazy(fn () => Datum::set('type', 'key', 'value'));
    expect(Datum::wantsIngesting())->toBeTrue();
    Datum::lazy(fn () => Datum::record('type', 'key'));
    expect(Datum::wantsIngesting())->toBeFalse();
});

it('rescues exceptions that occur while filtering', function () {
    $handled = false;
    Datum::handleExceptionsUsing(function () use (&$handled) {
        $handled = true;
    });

    Datum::filter(function ($entry) {
        throw new RuntimeException('Whoops!');
    });
    Datum::record('type', 'key');
    Datum::ingest();

    expect($handled)->toBe(true);
});
