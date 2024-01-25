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
class NullIngest implements Ingest
{
    /**
     * Ingest the items.
     *
     * @param  Collection<int, Entry>  $items
     */
    public function ingest(Collection $items): void
    {
        //
    }

    /**
     * Trim the ingest.
     */
    public function trim(): void
    {
        //
    }

    /**
     * Digest the ingested items.
     */
    public function digest(Storage $storage): int
    {
        return 0;
    }
}
