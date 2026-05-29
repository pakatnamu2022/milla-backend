<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // These JOIN columns are not part of the original create-table migrations
        // and cause full table scans on the 3-table JOIN chain used by rawUserStats / getSedeRanking.
        $this->addIndexIfMissing('usr_users', 'usr_users_partner_id_index', 'partner_id');
        $this->addIndexIfMissing('rrhh_persona', 'rrhh_persona_sede_id_index', 'sede_id');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('usr_users', 'usr_users_partner_id_index');
        $this->dropIndexIfExists('rrhh_persona', 'rrhh_persona_sede_id_index');
    }

    private function addIndexIfMissing(string $table, string $indexName, string $column): void
    {
        $exists = collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]))->isNotEmpty();
        if (!$exists) {
            DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$column}`)");
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $exists = collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]))->isNotEmpty();
        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }
    }
};
