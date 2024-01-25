<?php

declare(strict_types=1);

namespace Vulpecula\Datum\Contracts;

use Illuminate\Support\Collection;
use Vulpecula\Datum\Entry;
use Vulpecula\Datum\Enums\Period;
use Vulpecula\Datum\Value;

interface Storage
{
    /**
     * Store the items.
     *
     * @param  Collection<int, Entry|Value>  $items
     */
    public function store(Collection $items): void;

    /**
     * Trim the storage.
     */
    public function trim(): void;

    /**
     * Purge the storage.
     *
     * @param  list<string>  $types
     */
    public function purge(?array $types = null): void;

    /**
     * Retrieve values for the given type.
     *
     * @param  list<string>  $keys
     * @return Collection<string, object{
     *     timestamp: int,
     *     key: string,
     *     value: string
     * }>
     */
    public function values(string $type, ?array $keys = null): Collection;

    /**
     * Retrieve aggregate values for plotting on a graph.
     *
     * @param  list<string>  $types
     * @return Collection<string, Collection<string, Collection<string, int|null>>>
     */
    public function graph(array $types, string $aggregate, Period $interval): Collection;

    /**
     * Retrieve aggregate values for the given type.
     *
     * @param  'count'|'min'|'max'|'sum'|'avg'|list<'count'|'min'|'max'|'sum'|'avg'>  $aggregates
     * @return Collection<int, object{
     *     key: string,
     *     min?: int,
     *     max?: int,
     *     sum?: int,
     *     avg?: int,
     *     count?: int
     * }>
     */
    public function aggregate(
        string $type,
        string|array $aggregates,
        Period $interval,
        ?string $orderBy = null,
        string $direction = 'desc',
        int $limit = 101,
    ): Collection;

    /**
     * Retrieve aggregate values for the given types.
     *
     * @param  string|list<string>  $types
     * @param  'count'|'min'|'max'|'sum'|'avg'  $aggregate
     * @return Collection<int, object>
     */
    public function aggregateTypes(
        string|array $types,
        string $aggregate,
        Period $interval,
        ?string $orderBy = null,
        string $direction = 'desc',
        int $limit = 101,
    ): Collection;

    /**
     * Retrieve an aggregate total for the given types.
     *
     * @param  string|list<string>  $types
     * @param  'count'|'min'|'max'|'sum'|'avg'  $aggregate
     * @return float|Collection<string, int>
     */
    public function aggregateTotal(
        array|string $types,
        string $aggregate,
        Period $interval,
    ): float|Collection;
}
