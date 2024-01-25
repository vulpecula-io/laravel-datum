<?php

declare(strict_types=1);

namespace Vulpecula\Datum\Ingests;

use Vulpecula\Datum\Contracts\Ingest;
use Vulpecula\Datum\Contracts\Storage;
use Vulpecula\Datum\Entry;
use Illuminate\Support\Collection;

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
