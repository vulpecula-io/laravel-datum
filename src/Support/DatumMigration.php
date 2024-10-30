<?php

declare(strict_types=1);

namespace Vulpecula\Datum\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatumMigration extends Migration
{
    /**
     * Get the migration connection name.
     */
    public function getConnection(): ?string
    {
        return Config::get('datum.storage.database.connection');
    }

    /**
     * Determine if the migration should run.
     */
    protected function shouldRun(): bool
    {
        if (in_array($this->driver(), ['mariadb', 'mysql', 'pgsql', 'sqlite'])) {
            return true;
        }

        if (! App::environment('testing')) {
            throw new RuntimeException("Datum does not support the [{$this->driver()}] database driver.");
        }

        if (Config::get('datum.enabled')) {
            throw new RuntimeException("Datum does not support the [{$this->driver()}] database driver. You can disable Datum in your testsuite by adding `<env name=\"DATUM_ENABLED\" value=\"false\"/>` to your project's `phpunit.xml` file.");
        }

        return false;
    }

    /**
     * Get the database connection driver.
     */
    protected function driver(): string
    {
        return DB::connection($this->getConnection())->getDriverName();
    }
}
