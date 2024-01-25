<?php

declare(strict_types=1);

namespace Vulpecula\Datum;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Lottery;
use Illuminate\Support\Traits\ForwardsCalls;
use Throwable;
use Vulpecula\Datum\Contracts\Ingest;
use Vulpecula\Datum\Contracts\Storage;
use Vulpecula\Datum\Events\ExceptionReported;

/**
 * @mixin Storage
 */
class Datum
{
    use Concerns\ConfiguresAfterResolving, ForwardsCalls;

    /**
     * The list of metric recorders.
     *
     * @var Collection<int, object>
     */
    protected Collection $recorders;

    /**
     * The list of queued items.
     *
     * @var Collection<int, Entry|Value>
     */
    protected Collection $entries;

    /**
     * The list of queued lazy entry and value resolvers.
     *
     * @var Collection<int, callable>
     */
    protected Collection $lazy;

    /**
     * Indicates if Pulse should be recording.
     */
    protected bool $shouldRecord = true;

    /**
     * The entry filters.
     *
     * @var Collection<int, (callable(Entry|Value): bool)>
     */
    protected Collection $filters;

    /**
     * The remembered user's ID.
     */
    protected int|string|null $rememberedUserId = null;

    /**
     * Indicates if Pulse routes will be registered.
     */
    protected bool $registersRoutes = true;

    /**
     * Handle exceptions using the given callback.
     *
     * @var ?callable(Throwable): mixed
     */
    protected $handleExceptionsUsing = null;

    /**
     * Indicates that Pulse is currently evaluating the buffer.
     */
    protected bool $evaluatingBuffer = false;

    /**
     * Create a new Pulse instance.
     */
    public function __construct(protected Application $app)
    {
        $this->filters = collect([]);
        $this->recorders = collect([]);
        $this->entries = collect([]);
        $this->lazy = collect([]);
    }

    /**
     * Register a recorder.
     *
     * @param  array<class-string, array|boolean>  $recorders
     */
    public function register(array $recorders): self
    {
        $recorders = collect($recorders)->map(function ($recorder, $key) {
            if (false === $recorder || (is_array($recorder) && ! ($recorder['enabled'] ?? true))) {
                return;
            }

            return $this->app->make($key);
        })->filter()->values();

        $this->afterResolving($this->app, 'events', fn (Dispatcher $event) => $recorders
            ->filter(fn ($recorder) => $recorder->listen ?? null)
            ->each(fn ($recorder) => $event->listen(
                $recorder->listen,
                fn ($event) => $this->rescue(fn () => $recorder->record($event))
            ))
        );

        $recorders
            ->filter(fn ($recorder) => method_exists($recorder, 'register'))
            ->each(function ($recorder) {
                $this->app->call($recorder->register(...), [
                    'record' => fn (...$args) => $this->rescue(fn () => $recorder->record(...$args)),
                ]);
            });

        $this->recorders = collect([...$this->recorders, ...$recorders]);

        return $this;
    }

    /**
     * Filter items before storage using the provided filter.
     *
     * @param  (callable(Entry|Value): bool)  $filter
     */
    public function filter(callable $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Execute the given callback handling any exceptions.
     *
     * @template TReturn
     *
     * @param  (callable(): TReturn)  $callback
     * @return TReturn|null
     */
    public function rescue(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            ($this->handleExceptionsUsing ?? fn () => null)($e);
        }

        return null;
    }

    /**
     * Record an entry.
     */
    public function record(
        string $type,
        string $key,
        ?int $value = null,
        DateTimeInterface|int|null $timestamp = null,
    ): Entry {
        $timestamp ??= CarbonImmutable::now();

        $entry = new Entry(
            timestamp: $timestamp instanceof DateTimeInterface ? $timestamp->getTimestamp() : $timestamp,
            type: $type,
            key: $key,
            value: $value,
        );

        if ($this->shouldRecord) {
            $this->entries[] = $entry;

            $this->ingestWhenOverBufferSize();
        }

        return $entry;
    }

    /**
     * Start ingesting entries if over buffer size.
     */
    protected function ingestWhenOverBufferSize(): void
    {
        // To prevent recursion, we track when we are already evaluating the
        // buffer and resolving entries. When we are we may simply return
        // and the continue execution. We set the value to false later.
        if ($this->evaluatingBuffer) {
            return;
        }

        $buffer = $this->app->make('config')->get('datum.ingest.buffer');

        if (($this->entries->count() + $this->lazy->count()) > $buffer) {
            $this->evaluatingBuffer = true;

            $this->resolveLazyEntries();
        }

        if ($this->entries->count() > $buffer) {
            $this->evaluatingBuffer = true;

            $this->ingest();
        }

        $this->evaluatingBuffer = false;
    }

    /**
     * Resolve lazy entries.
     */
    protected function resolveLazyEntries(): void
    {
        $this->rescue(fn () => $this->lazy->each(fn ($lazy) => $lazy()));

        $this->lazy = collect([]);
    }

    /**
     * Ingest the entries.
     */
    public function ingest(): int
    {
        $this->resolveLazyEntries();

        return $this->ignore(function () {
            $entries = $this->rescue(fn () => $this->entries->filter($this->shouldRecord(...))) ?? collect([]);

            if ($entries->isEmpty()) {
                $this->flush();

                return 0;
            }

            $ingest = $this->app->make(Ingest::class);

            $count = $this->rescue(function () use ($entries, $ingest) {
                $ingest->ingest($entries);

                return $entries->count();
            }) ?? 0;

            $odds = $this->app->make('config')->get('datum.ingest.trim.lottery');

            Lottery::odds(...$odds)
                ->winner(fn () => $this->rescue(fn () => $ingest->trim(...)))
                ->choose();

            $this->flush();

            return $count;
        });
    }

    /**
     * Execute the given callback without recording.
     *
     * @template TReturn
     *
     * @param  (callable(): TReturn)  $callback
     * @return TReturn
     */
    public function ignore($callback): mixed
    {
        $cachedRecording = $this->shouldRecord;

        try {
            $this->shouldRecord = false;

            return $callback();
        } finally {
            $this->shouldRecord = $cachedRecording;
        }
    }

    /**
     * Flush the queue.
     */
    public function flush(): self
    {
        $this->entries = collect([]);

        $this->lazy = collect([]);

        $this->rememberedUserId = null;

        return $this;
    }

    /**
     * Record a value.
     */
    public function set(
        string $type,
        string $key,
        string $value,
        DateTimeInterface|int|null $timestamp = null,
    ): Value {
        $timestamp ??= CarbonImmutable::now();

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $value = new Value(
            timestamp: $timestamp instanceof DateTimeInterface ? $timestamp->getTimestamp() : $timestamp,
            type: $type,
            key: $key,
            value: $value,
        );

        if ($this->shouldRecord) {
            $this->entries[] = $value;

            $this->ingestWhenOverBufferSize();
        }

        return $value;
    }

    /**
     * Lazily capture items.
     */
    public function lazy(callable $closure): self
    {
        if ($this->shouldRecord) {
            $this->lazy[] = $closure;

            $this->ingestWhenOverBufferSize();
        }

        return $this;
    }

    /**
     * Report the throwable exception to Pulse.
     */
    public function report(Throwable $e): self
    {
        $this->rescue(fn () => $this->app->make('events')->dispatch(new ExceptionReported($e)));

        return $this;
    }

    /**
     * Start recording.
     */
    public function startRecording(): self
    {
        $this->shouldRecord = true;

        return $this;
    }

    /**
     * Stop recording.
     */
    public function stopRecording(): self
    {
        $this->shouldRecord = false;

        return $this;
    }

    /**
     * Digest the entries.
     */
    public function digest(): int
    {
        return $this->ignore(
            fn () => $this->app->make(Ingest::class)->digest($this->app->make(Storage::class))
        );
    }

    /**
     * Determine if Pulse wants to ingest entries.
     */
    public function wantsIngesting(): bool
    {
        return $this->lazy->isNotEmpty() || $this->entries->isNotEmpty();
    }

    /**
     * Get the registered recorders.
     *
     * @return Collection<int, object>
     */
    public function recorders(): Collection
    {
        return collect($this->recorders);
    }

    /**
     * Handle exceptions using the given callback.
     *
     * @param  (callable(Throwable): mixed)  $callback
     */
    public function handleExceptionsUsing(callable $callback): self
    {
        $this->handleExceptionsUsing = $callback;

        return $this;
    }

    /**
     * Set the container instance.
     *
     * @param  Application  $container
     * @return $this
     */
    public function setContainer($container)
    {
        $this->app = $container;

        return $this;
    }

    /**
     * Forward calls to the storage driver.
     *
     * @mixin Storage
     *
     * @param  string  $method
     * @param  array<mixed>  $parameters
     */
    public function __call($method, $parameters): mixed
    {
        return $this->ignore(fn () => $this->forwardCallTo($this->app->make(Storage::class), $method, $parameters));
    }

    /**
     * Determine if the given entry should be recorded.
     */
    protected function shouldRecord(Entry|Value $entry): bool
    {
        return $this->filters->every(fn (callable $filter) => $filter($entry));
    }
}
