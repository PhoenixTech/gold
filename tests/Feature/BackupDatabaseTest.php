<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    public function test_pruning_only_removes_backups_for_current_database(): void
    {
        Storage::fake('s3');

        config(['database.connections.mysql.database' => 'behnam_gold']);
        config(['database.default' => 'mysql']);

        Storage::disk('s3')->put('db_backups/backup_behnam_gold_2026-11-03_100000.sql.gz', 'data');
        Storage::disk('s3')->put('db_backups/backup_behnam_gold_2026-11-03_120000.sql.gz', 'data');
        Storage::disk('s3')->put('db_backups/backup_other_site_2026-11-03_110000.sql.gz', 'data');
        Storage::disk('s3')->put('db_backups/backup_behnam_2026-11-03_090000.sql.gz', 'data');

        $this->artisan('db:backup', [
            '--prune-only' => true,
            '--disk' => 's3',
            '--database' => 'behnam_gold',
        ])->assertSuccessful();

        Storage::disk('s3')->assertMissing('db_backups/backup_behnam_gold_2026-11-03_100000.sql.gz');
        Storage::disk('s3')->assertExists('db_backups/backup_behnam_gold_2026-11-03_120000.sql.gz');
        Storage::disk('s3')->assertExists('db_backups/backup_other_site_2026-11-03_110000.sql.gz');
        Storage::disk('s3')->assertExists('db_backups/backup_behnam_2026-11-03_090000.sql.gz');
    }

    public function test_pruning_removes_old_backups_for_target_database_while_keeping_other_databases_old_backups(): void
    {
        Storage::fake('s3');
        Carbon::setTestNow('2026-11-03 12:00:00');

        config(['database.connections.mysql.database' => 'behnam_gold']);
        config(['database.default' => 'mysql']);

        Storage::disk('s3')->put('db_backups/backup_behnam_gold_2026-10-01_100000.sql.gz', 'data');
        Storage::disk('s3')->put('db_backups/backup_other_site_2026-10-01_100000.sql.gz', 'data');

        $this->artisan('db:backup', [
            '--prune-only' => true,
            '--disk' => 's3',
            '--days' => 14,
        ])->assertSuccessful();

        Storage::disk('s3')->assertMissing('db_backups/backup_behnam_gold_2026-10-01_100000.sql.gz');
        Storage::disk('s3')->assertExists('db_backups/backup_other_site_2026-10-01_100000.sql.gz');
    }
}
