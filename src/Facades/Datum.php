<?php

declare(strict_types=1);

namespace Vulpecula\Datum\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Vulpecula\Datum\Datum
 */
class Datum extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Vulpecula\Datum\Datum::class;
    }
}
