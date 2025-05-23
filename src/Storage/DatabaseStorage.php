<?php

declare(strict_types=1);

namespace Vulpecula\Datum\Storage;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Config\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;
use Vulpecula\Datum\Contracts\Storage;
use Vulpecula\Datum\Entry;
use Vulpecula\Datum\Enums\Period;
use Vulpecula\Datum\Value;

/**
 * @phpstan-type AggregateRow array{bucket: int, period: int, type: string, aggregate: string, key: string, value: int|float, count?: int}
 *
 * @internal
 */
class DatabaseStorage implements Storage
{
    /**
     * Create a new Database storage instance.
     */
    public function __construct(
        protected DatabaseManager $db,
        protected Repository $config,
    ) {
        //
    }

    /**
     * Store the items.
     *
     * @param  Collection<int, Entry|Value>  $items
     */
    public function store(Collection $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $this->connection()->transaction(function () use ($items) {
            [$entries, $values] = $items->partition(fn (Entry|Value $entry) => $entry instanceof Entry);

            $entries
                ->reject->isOnlyBuckets()
                ->chunk($this->config->get('datum.storage.database.chunk'))
                ->each(fn ($chunk) => $this->connection()
                    ->table('datum_entries')
                    ->insert(
                        $this->requiresManualKeyHash()
                            ? $chunk->map(fn ($entry) => [
                                ...($attributes = $entry->attributes()),
                                'key_hash' => md5($attributes['key']),
                            ])->all()
                            : $chunk->map->attributes()->all()
                    )
                );

            [$counts, $minimums, $maximums, $sums, $averages] = array_values($entries
                ->reduce(function ($carry, $entry) {
                    foreach ($entry->aggregations() as $aggregation) {
                        $carry[$aggregation][] = $entry;
                    }

                    return $carry;
                }, ['count' => [], 'min' => [], 'max' => [], 'sum' => [], 'avg' => []])
            );

            $this
                ->preaggregateCounts(collect($counts))
                ->chunk($this->config->get('datum.storage.database.chunk'))
                ->each(fn ($chunk) => $this->upsertCount($chunk->all()));

            $this
                ->preaggregateMinimums(collect($minimums))
                ->chunk($this->config->get('datum.storage.database.chunk'))
                ->each(fn ($chunk) => $this->upsertMin($chunk->all()));

            $this
                ->preaggregateMaximums(collect($maximums))
                ->chunk($this->config->get('datum.storage.database.chunk'))
                ->each(fn ($chunk) => $this->upsertMax($chunk->all()));

            $this
                ->preaggregateSums(collect($sums))
                ->chunk($this->config->get('datum.storage.database.chunk'))
                ->each(fn ($chunk) => $this->upsertSum($chunk->all()));

            $this
                ->preaggregateAverages(collect($averages))
                ->chunk($this->config->get('datum.storage.database.chunk'))
                ->each(fn ($chunk) => $this->upsertAvg($chunk->all()));

            $this
                ->collapseValues($values)
                ->chunk($this->config->get('datum.storage.database.chunk'))
                ->each(fn ($chunk) => $this->connection()
                    ->table('datum_values')
                    ->upsert(
                        $this->requiresManualKeyHash()
                            ? $chunk->map(fn ($entry) => [
                                ...($attributes = $entry->attributes()),
                                'key_hash' => md5($attributes['key']),
                            ])->all()
                            : $chunk->map->attributes()->all(),
                        ['type', 'key_hash'],
                        ['timestamp', 'value']
                    )
                );
        });
    }

    /**
     * Resolve the database connection.
     */
    protected function connection(): Connection
    {
        return $this->db->connection($this->config->get('datum.storage.database.connection'));
    }

    /**
     * Determine whether a manually generated key hash is required.
     */
    protected function requiresManualKeyHash(): bool
    {
        return 'sqlite' === $this->connection()->getDriverName();
    }

    /**
     * Pre-aggregate entry counts.
     *
     * @param  Collection<int, Entry>  $entries
     * @return Collection<int, AggregateRow>
     */
    protected function preaggregateCounts(Collection $entries): Collection
    {
        return $this->preaggregate($entries, 'count', fn ($aggregate) => [
            ...$aggregate,
            'value' => ($aggregate['value'] ?? 0) + 1,
        ]);
    }

    /**
     * Pre-aggregate entries with a callback.
     *
     * @param  Collection<int, Entry>  $entries
     * @return Collection<int, AggregateRow>
     */
    protected function preaggregate(Collection $entries, string $aggregate, Closure $callback): Collection
    {
        $aggregates = [];

        foreach ($entries as $entry) {
            foreach (Period::cases() as $period) {
                // Exclude entries that would be trimmed.
                if ($entry->timestamp < $period->getOldestBucketTimestamp()) {
                    continue;
                }

                $bucket = $period->getBucketForTimestamp($entry->timestamp);

                $key = $entry->type.':'.$period->value.':'.$bucket.':'.$entry->key;

                if (! isset($aggregates[$key])) {
                    $aggregates[$key] = $callback([
                        'bucket' => $bucket,
                        'period' => $period->value,
                        'type' => $entry->type,
                        'aggregate' => $aggregate,
                        'key' => $entry->key,
                    ], $entry);

                    if ($this->requiresManualKeyHash()) {
                        $aggregates[$key]['key_hash'] = md5($entry->key);
                    }
                } else {
                    $aggregates[$key] = $callback($aggregates[$key], $entry);
                }
            }
        }

        return collect(array_values($aggregates));
    }

    /**
     * Insert new records or update the existing ones and update the count.
     *
     * @param  list<AggregateRow>  $values
     */
    protected function upsertCount(array $values): int
    {
        return $this->connection()->table('datum_aggregates')->upsert(
            $values,
            ['bucket', 'period', 'type', 'aggregate', 'key_hash'],
            [
                'value' => match ($driver = $this->connection()->getDriverName()) {
                    'mysql' => new Expression('`value` + values(`value`)'),
                    'pgsql', 'sqlite' => new Expression('"datum_aggregates"."value" + "excluded"."value"'),
                    default => throw new RuntimeException("Unsupported database driver [{$driver}]"),
                },
            ]
        );
    }

    /**
     * Pre-aggregate entry minimums.
     *
     * @param  Collection<int, Entry>  $entries
     * @return Collection<int, AggregateRow>
     */
    protected function preaggregateMinimums(Collection $entries): Collection
    {
        return $this->preaggregate($entries, 'min', fn ($aggregate, $entry) => [
            ...$aggregate,
            'value' => ! isset($aggregate['value'])
                ? $entry->value
                : (int) min($aggregate['value'], $entry->value),
        ]);
    }

    /**
     * Insert new records or update the existing ones and the minimum.
     *
     * @param  list<AggregateRow>  $values
     */
    protected function upsertMin(array $values): int
    {
        return $this->connection()->table('datum_aggregates')->upsert(
            $values,
            ['bucket', 'period', 'type', 'aggregate', 'key_hash'],
            [
                'value' => match ($driver = $this->connection()->getDriverName()) {
                    'mysql' => new Expression('least(`value`, values(`value`))'),
                    'pgsql' => new Expression('least("datum_aggregates"."value", "excluded"."value")'),
                    'sqlite' => new Expression('min("datum_aggregates"."value", "excluded"."value")'),
                    default => throw new RuntimeException("Unsupported database driver [{$driver}]"),
                },
            ]
        );
    }

    /**
     * Pre-aggregate entry maximums.
     *
     * @param  Collection<int, Entry>  $entries
     * @return Collection<int, AggregateRow>
     */
    protected function preaggregateMaximums(Collection $entries): Collection
    {
        return $this->preaggregate($entries, 'max', fn ($aggregate, $entry) => [
            ...$aggregate,
            'value' => ! isset($aggregate['value'])
                ? $entry->value
                : (int) max($aggregate['value'], $entry->value),
        ]);
    }

    /**
     * Insert new records or update the existing ones and the maximum.
     *
     * @param  list<AggregateRow>  $values
     */
    protected function upsertMax(array $values): int
    {
        return $this->connection()->table('datum_aggregates')->upsert(
            $values,
            ['bucket', 'period', 'type', 'aggregate', 'key_hash'],
            [
                'value' => match ($driver = $this->connection()->getDriverName()) {
                    'mysql' => new Expression('greatest(`value`, values(`value`))'),
                    'pgsql' => new Expression('greatest("datum_aggregates"."value", "excluded"."value")'),
                    'sqlite' => new Expression('max("datum_aggregates"."value", "excluded"."value")'),
                    default => throw new RuntimeException("Unsupported database driver [{$driver}]"),
                },
            ]
        );
    }

    /**
     * Pre-aggregate entry sums.
     *
     * @param  Collection<int, Entry>  $entries
     * @return Collection<int, AggregateRow>
     */
    protected function preaggregateSums(Collection $entries): Collection
    {
        return $this->preaggregate($entries, 'sum', fn ($aggregate, $entry) => [
            ...$aggregate,
            'value' => ($aggregate['value'] ?? 0) + $entry->value,
        ]);
    }

    /**
     * Insert new records or update the existing ones and the sum.
     *
     * @param  list<AggregateRow>  $values
     */
    protected function upsertSum(array $values): int
    {
        return $this->connection()->table('datum_aggregates')->upsert(
            $values,
            ['bucket', 'period', 'type', 'aggregate', 'key_hash'],
            [
                'value' => match ($driver = $this->connection()->getDriverName()) {
                    'mysql' => new Expression('`value` + values(`value`)'),
                    'pgsql', 'sqlite' => new Expression('"datum_aggregates"."value" + "excluded"."value"'),
                    default => throw new RuntimeException("Unsupported database driver [{$driver}]"),
                },
            ]
        );
    }

    /**
     * Pre-aggregate entry averages.
     *
     * @param  Collection<int, Entry>  $entries
     * @return Collection<int, AggregateRow>
     */
    protected function preaggregateAverages(Collection $entries): Collection
    {
        return $this->preaggregate($entries, 'avg', fn ($aggregate, $entry) => [
            ...$aggregate,
            'value' => ! isset($aggregate['value'])
                ? $entry->value
                : ($aggregate['value'] * $aggregate['count'] + $entry->value) / ($aggregate['count'] + 1),
            'count' => ($aggregate['count'] ?? 0) + 1,
        ]);
    }

    /**
     * Insert new records or update the existing ones and the average.
     *
     * @param  list<AggregateRow>  $values
     */
    protected function upsertAvg(array $values): int
    {
        return $this->connection()->table('datum_aggregates')->upsert(
            $values,
            ['bucket', 'period', 'type', 'aggregate', 'key_hash'],
            match ($driver = $this->connection()->getDriverName()) {
                'mysql' => [
                    'value' => new Expression('(`value` * `count` + (values(`value`) * values(`count`))) / (`count` + values(`count`))'),
                    'count' => new Expression('`count` + values(`count`)'),
                ],
                'pgsql', 'sqlite' => [
                    'value' => new Expression('("datum_aggregates"."value" * "datum_aggregates"."count" + ("excluded"."value" * "excluded"."count")) / ("datum_aggregates"."count" + "excluded"."count")'),
                    'count' => new Expression('"datum_aggregates"."count" + "excluded"."count"'),
                ],
                default => throw new RuntimeException("Unsupported database driver [{$driver}]"),
            }
        );
    }

    /**
     * Collapse the given values.
     *
     * @param  Collection<int, Value>  $values
     * @return Collection<int, Value>
     */
    protected function collapseValues(Collection $values): Collection
    {
        return $values->reverse()->unique(fn (Value $value) => [$value->key, $value->type]);
    }

    /**
     * Trim the storage.
     */
    public function trim(): void
    {
        $now = CarbonImmutable::now();

        $this->connection()
            ->table('datum_values')
            ->where('timestamp', '<=', $now->subYear()->getTimestamp())
            ->delete();

        $this->connection()
            ->table('datum_entries')
            ->where('timestamp', '<=', $now->subYear()->getTimestamp())
            ->delete();

        foreach (Period::cases() as $period) {
            $this->connection()
                ->table('datum_aggregates')
                ->where('period', $period->value)
                ->where('bucket', '<=', $period->getOldestBucketTimestamp())
                ->delete();
        }
    }

    /**
     * Purge the storage.
     *
     * @param  list<string>  $types
     */
    public function purge(?array $types = null): void
    {
        if (null === $types) {
            $this->connection()->table('datum_values')->truncate();
            $this->connection()->table('datum_entries')->truncate();
            $this->connection()->table('datum_aggregates')->truncate();

            return;
        }

        $this->connection()->table('datum_values')->whereIn('type', $types)->delete();
        $this->connection()->table('datum_entries')->whereIn('type', $types)->delete();
        $this->connection()->table('datum_aggregates')->whereIn('type', $types)->delete();
    }

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
    public function values(string $type, ?array $keys = null): Collection
    {
        return $this->connection()
            ->table('datum_values')
            ->select('timestamp', 'key', 'value')
            ->where('type', $type)
            ->when($keys, fn ($query) => $query->whereIn('key', $keys))
            ->get()
            ->keyBy('key');
    }

    /**
     * Retrieve aggregate values for plotting on a graph.
     *
     * @param  list<string>  $types
     * @param  'count'|'min'|'max'|'sum'|'avg'  $aggregate
     * @return Collection<string, Collection<string, Collection<string, int|null>>>
     */
    public function graph(array $types, string $aggregate, Period $interval): Collection
    {
        if (! in_array($aggregate, $allowed = ['count', 'min', 'max', 'sum', 'avg'])) {
            throw new InvalidArgumentException("Invalid aggregate type [$aggregate], allowed types: [".implode(', ', $allowed).'].');
        }

        $period = $interval;
        $buckets = $interval->getBuckets();
        $padding = collect()
            ->range(0, $interval->maxDataPoints() - 1)
            ->mapWithKeys(fn ($i) => [$buckets[$i]->format($interval->getDateTimeFormat()) => 0]);

        $structure = collect($types)->mapWithKeys(fn ($type) => [$type => $padding]);

        return $this->connection()->table('datum_aggregates')
            ->select(['bucket', 'type', 'key', 'value'])
            ->whereIn('type', $types)
            ->where('aggregate', $aggregate)
            ->where('period', $period->graphPeriod()?->value)
            ->where('bucket', '>=', $buckets[0]->getTimestamp())
            ->orderBy('bucket')
            ->get()
            ->groupBy('key')
            ->sortKeys()
            ->map(fn ($readings) => $structure->merge($readings
                ->groupBy('type')
                ->map(fn ($readings) => $padding->merge(
                    $readings->mapWithKeys(function ($reading) use ($interval) {
                        return [CarbonImmutable::createFromTimestamp($reading->bucket)->format($interval->getDateTimeFormat()) => $reading->value];
                    })
                ))
            ));
    }

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
        array|string $aggregates,
        Period $interval,
        ?string $orderBy = null,
        string $direction = 'desc',
        int $limit = 101,
    ): Collection {
        $aggregates = is_array($aggregates) ? $aggregates : [$aggregates];

        if ($invalid = array_diff($aggregates, $allowed = ['count', 'min', 'max', 'sum', 'avg'])) {
            throw new InvalidArgumentException('Invalid aggregate type(s) ['.implode(', ', $invalid).'], allowed types: ['.implode(', ', $allowed).'].');
        }

        $orderBy ??= $aggregates[0];

        return $this->connection()
            ->query()
            ->select([
                'key' => fn (Builder $query) => $query
                    ->select('key')
                    ->from('datum_entries', as: 'keys')
                    ->whereColumn('keys.key_hash', 'aggregated.key_hash')
                    ->limit(1),
                ...$aggregates,
            ])
            ->fromSub(function (Builder $query) use ($type, $aggregates, $interval, $orderBy, $direction, $limit) {
                $query->select('key_hash');

                foreach ($aggregates as $aggregate) {
                    $query->selectRaw(match ($aggregate) {
                        'count' => "sum({$this->wrap('count')})",
                        'min' => "min({$this->wrap('min')})",
                        'max' => "max({$this->wrap('max')})",
                        'sum' => "sum({$this->wrap('sum')})",
                        'avg' => "avg({$this->wrap('avg')})",
                    }." as {$this->wrap($aggregate)}");
                }

                $query->fromSub(function (Builder $query) use ($type, $aggregates, $interval) {
                    $now = CarbonImmutable::now();
                    $period = $interval->value;
                    $windowStart = (int) ($now->getTimestamp() - $interval->totalSeconds() + 1);
                    $oldestBucket = $interval->currentBucket() - $interval->totalSeconds();

                    // Tail
                    $query->select('key_hash');

                    foreach ($aggregates as $aggregate) {
                        $query->selectRaw(match ($aggregate) {
                            'count' => 'count(*)',
                            'min' => "min({$this->wrap('value')})",
                            'max' => "max({$this->wrap('value')})",
                            'sum' => "sum({$this->wrap('value')})",
                            'avg' => "avg({$this->wrap('value')})",
                        }." as {$this->wrap($aggregate)}");
                    }

                    $query
                        ->from('datum_entries')
                        ->where('type', $type)
                        ->where('timestamp', '>=', $windowStart)
                        ->where('timestamp', '<=', $oldestBucket - 1)
                        ->groupBy('key_hash');

                    // Buckets
                    foreach ($aggregates as $currentAggregate) {
                        $query->unionAll(function (Builder $query) use ($type, $aggregates, $currentAggregate, $period, $oldestBucket) {
                            $query->select('key_hash');

                            foreach ($aggregates as $aggregate) {
                                if ($aggregate === $currentAggregate) {
                                    $query->selectRaw(match ($aggregate) {
                                        'count' => "sum({$this->wrap('value')})",
                                        'min' => "min({$this->wrap('value')})",
                                        'max' => "max({$this->wrap('value')})",
                                        'sum' => "sum({$this->wrap('value')})",
                                        'avg' => "avg({$this->wrap('value')})",
                                    }." as {$this->wrap($aggregate)}");
                                } else {
                                    $query->selectRaw("null as {$this->wrap($aggregate)}");
                                }
                            }

                            $query
                                ->from('datum_aggregates')
                                ->where('period', $period)
                                ->where('type', $type)
                                ->where('aggregate', $currentAggregate)
                                ->where('bucket', '>=', $oldestBucket)
                                ->groupBy('key_hash');
                        });
                    }
                }, as: 'results')
                    ->groupBy('key_hash')
                    ->orderBy($orderBy, $direction)
                    ->limit($limit);
            }, as: 'aggregated')
            ->get();
    }

    /**
     * Wrap a value in keyword identifiers.
     */
    protected function wrap(string $value): string
    {
        return $this->connection()->getQueryGrammar()->wrap($value);
    }

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
    ): Collection {
        if (! in_array($aggregate, $allowed = ['count', 'min', 'max', 'sum', 'avg'])) {
            throw new InvalidArgumentException("Invalid aggregate type [$aggregate], allowed types: [".implode(', ', $allowed).'].');
        }

        $types = is_array($types) ? $types : [$types];
        $orderBy ??= $types[0];

        return $this->connection()
            ->query()
            ->select([
                'key' => fn (Builder $query) => $query
                    ->select('key')
                    ->from('datum_entries', as: 'keys')
                    ->whereColumn('keys.key_hash', 'aggregated.key_hash')
                    ->limit(1),
                ...$types,
            ])
            ->fromSub(function (Builder $query) use ($types, $aggregate, $interval, $orderBy, $direction, $limit) {
                $query->select('key_hash');

                foreach ($types as $type) {
                    $query->selectRaw(match ($aggregate) {
                        'count' => "sum({$this->wrap($type)})",
                        'min' => "min({$this->wrap($type)})",
                        'max' => "max({$this->wrap($type)})",
                        'sum' => "sum({$this->wrap($type)})",
                        'avg' => "avg({$this->wrap($type)})",
                    }." as {$this->wrap($type)}");
                }

                $query->fromSub(function (Builder $query) use ($types, $aggregate, $interval) {
                    $now = CarbonImmutable::now();
                    $period = $interval->value;
                    $windowStart = (int) ($now->getTimestamp() - $interval->totalSeconds());
                    $oldestBucket = $interval->currentBucket() - $interval->totalSeconds();

                    // Tail
                    $query->select('key_hash');

                    foreach ($types as $type) {
                        $query->selectRaw(match ($aggregate) {
                            'count' => "count(case when ({$this->wrap('type')} = ?) then true else null end)",
                            'min' => "min(case when ({$this->wrap('type')} = ?) then {$this->wrap('value')} else null end)",
                            'max' => "max(case when ({$this->wrap('type')} = ?) then {$this->wrap('value')} else null end)",
                            'sum' => "sum(case when ({$this->wrap('type')} = ?) then {$this->wrap('value')} else null end)",
                            'avg' => "avg(case when ({$this->wrap('type')} = ?) then {$this->wrap('value')} else null end)",
                        }." as {$this->wrap($type)}", [$type]);
                    }

                    $query
                        ->from('datum_entries')
                        ->whereIn('type', $types)
                        ->where('timestamp', '>=', $windowStart)
                        ->where('timestamp', '<=', $oldestBucket - 1)
                        ->groupBy('key_hash');

                    // Buckets
                    $query->unionAll(function (Builder $query) use ($types, $aggregate, $period, $oldestBucket) {
                        $query->select('key_hash');

                        foreach ($types as $type) {
                            $query->selectRaw(match ($aggregate) {
                                'count' => "sum(case when ({$this->wrap('type')} = ?) then {$this->wrap('value')} else null end)",
                                'min' => "min(case when ({$this->wrap('type')} = ?) then {$this->wrap('value')} else null end)",
                                'max' => "max(case when ({$this->wrap('type')} = ?) then {$this->wrap('value')} else null end)",
                                'sum' => "sum(case when ({$this->wrap('type')} = ?) then {$this->wrap('value')} else null end)",
                                'avg' => "avg(case when ({$this->wrap('type')} = ?) then {$this->wrap('value')} else null end)",
                            }." as {$this->wrap($type)}", [$type]);
                        }

                        $query
                            ->from('datum_aggregates')
                            ->where('period', $period)
                            ->whereIn('type', $types)
                            ->where('aggregate', $aggregate)
                            ->where('bucket', '>=', $oldestBucket)
                            ->groupBy('key_hash');
                    });
                }, as: 'results')
                    ->groupBy('key_hash')
                    ->orderBy($orderBy, $direction)
                    ->limit($limit);
            }, as: 'aggregated')
            ->get();
    }

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
    ): float|Collection {
        if (! in_array($aggregate, $allowed = ['count', 'min', 'max', 'sum', 'avg'])) {
            throw new InvalidArgumentException("Invalid aggregate type [$aggregate], allowed types: [".implode(', ', $allowed).'].');
        }

        $window = $interval->getWindow();

        return $this->connection()->query()
            ->when(is_array($types), fn ($query) => $query->addSelect('type'))
            ->selectRaw(match ($aggregate) {
                'count' => "sum({$this->wrap('count')})",
                'min' => "min({$this->wrap('min')})",
                'max' => "max({$this->wrap('max')})",
                'sum' => "sum({$this->wrap('sum')})",
                'avg' => "avg({$this->wrap('avg')})",
            }." as {$this->wrap($aggregate)}")
            ->fromSub(fn (Builder $query) => $query
            // Buckets
                ->addSelect('type')
                ->selectRaw(match ($aggregate) {
                    'count' => "sum({$this->wrap('value')})",
                    'min' => "min({$this->wrap('value')})",
                    'max' => "max({$this->wrap('value')})",
                    'sum' => "sum({$this->wrap('value')})",
                    'avg' => "avg({$this->wrap('value')})",
                }." as {$this->wrap($aggregate)}")
                ->from('datum_aggregates')
                ->where('period', $interval->graphPeriod()?->value)
                ->when(
                    is_array($types),
                    fn ($query) => $query->whereIn('type', $types),
                    fn ($query) => $query->where('type', $types)
                )
                ->where('aggregate', $aggregate)
                ->where('bucket', '>=', $window['start'])
                ->groupBy('type'), as: 'child'
            )
            ->groupBy('type')
            ->when(
                is_array($types),
                fn ($query) => $query->pluck($aggregate, 'type'),
                fn ($query) => (float) $query->value($aggregate)
            );
    }
}
