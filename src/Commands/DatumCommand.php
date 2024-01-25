<?php

namespace Vulpecula\Datum\Commands;

use Illuminate\Console\Command;

class DatumCommand extends Command
{
    public $signature = 'laravel-datum';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
