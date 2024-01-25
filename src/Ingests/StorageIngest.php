<?php

declare(strict_types=1);

namespace Vulpecula\Datum\Ingests;

use Illuminate\Support\Collection;
use Vulpecula\Datum\Contracts\Ingest;
use Vulpecula\Datum\Contracts\Storage;
use Vulpecula\Datum\Entry;

/**
 * @internal
 */
class StorageIngest implements Ingest
{
    /**
     * Create a new Storage Ingest instance.
     */
    public function __construct(protected Storage $storage)
    {
        //
    }

    /**
     * Ingest the items.
     *
     * @param  Collection<int, Entry>  $items
     */
    public function ingest(Collection $items): void
    {
        $this->storage->store($items);
    }

    /**
     * Trim the ingest.
     */
    public function trim(): void
    {
        $this->storage->trim();
    }

    /**
     * Digest the ingested items.
     */
    public function digest(Storage $storage): int
    {
        return 0;
    }
}
