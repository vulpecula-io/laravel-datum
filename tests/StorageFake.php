<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Collection;
use Vulpecula\Datum\Contracts\Storage;
use Vulpecula\Datum\Enums\Period;

class StorageFake implements Storage
{
    /**
     * Create a new fake instance.
     */
    public function __construct(public Collection $stored = new Collection)
    {
        //
    }

    /**
     * Store the items.
     *
     * @param  \Illuminate\Support\Collection<int, \Laravel\Pulse\Entry>  $items
     */
    public function store(Collection $items): void
    {
        $items->each(function ($el) {
            if ($el->remove ?? false) {
                $this->stored = $this->stored->reject(function ($store) use ($el) {
                    return $store->key === $el->key && $store->value === $el->value && $store->timestamp === $el->timestamp;
                });
            } else {
                $this->stored = $this->stored->merge([$el]);
            }
        });
    }

    /**
     * Trim the storage.
     */
    public function trim(): void
    {
        $this->stored = $this->stored->reject(fn ($record) => $record->timestamp <= now()->subYear()->timestamp);
    }

    /**
     * Purge the storage.
     *
     * @param  list<string>  $types
     */
    public function purge(?array $types = null): void
    {
        //
    }

    /**
     * Retrieve values for the given type.
     *
     * @param  list<string>  $keys
     * @return \Illuminate\Support\Collection<
     *     int,
     *     array<
     *         string,
     *         array{
     *             timestamp: int,
     *             type: string,
     *             key: string,
     *             value: string
     *         }
     *     >
     * >
     */
    public function values(string $type, ?array $keys = null): Collection
    {
        return new Collection;
    }

    /**
     * Retrieve aggregate values for plotting on a graph.
     *
     * @param  list<string>  $types
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<string, int|null>>>
     */
    public function graph(array $types, string $aggregate, Period $interval): Collection
    {
        return new Collection;
    }

    /**
     * Retrieve aggregate values for the given type.
     *
     * @param  list<string>  $aggregates
     * @return \Illuminate\Support\Collection<int, object{
     *     key: string,
     *     max?: int,
     *     sum?: int,
     *     avg?: int,
     *     count?: int
     * }>
     */
    public function aggregate(
        string $type,
        array|string $aggregates,
        Period $interval,
        ?string $orderBy = null,
        string $direction = 'desc',
        int $limit = 101,
    ): Collection {
        return new Collection;
    }

    /**
     * Retrieve aggregate values for the given types.
     *
     * @param  string|list<string>  $types
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function aggregateTypes(
        string|array $types,
        string $aggregate,
        Period $interval,
        ?string $orderBy = null,
        string $direction = 'desc',
        int $limit = 101,
    ): Collection {
        return new Collection;
    }

    /**
     * Retrieve an aggregate total for the given types.
     *
     * @param  string|list<string>  $types
     * @return \Illuminate\Support\Collection<string, int>
     */
    public function aggregateTotal(
        array|string $types,
        string $aggregate,
        Period $interval,
    ): Collection {
        return new Collection;
    }
}
