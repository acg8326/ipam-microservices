<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop indexes using procedure to check if they exist
        $this->dropIndexIfExists('ip_addresses', 'ip_addresses_status_index');
        $this->dropIndexIfExists('ip_addresses', 'ip_addresses_hostname_index');
        $this->dropIndexIfExists('ip_addresses', 'ip_addresses_mac_address_index');

        // Drop columns that exist
        $columnsToDrop = [];
        $columnsToCheck = ['subnet_id', 'hostname', 'mac_address', 'status', 'device_type', 'description', 'assigned_to', 'last_seen'];
        
        foreach ($columnsToCheck as $column) {
            if (Schema::hasColumn('ip_addresses', $column)) {
                $columnsToDrop[] = $column;
            }
        }
        
        if (!empty($columnsToDrop)) {
            Schema::table('ip_addresses', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        Schema::table('ip_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('ip_addresses', 'subnet_id')) {
                $table->unsignedBigInteger('subnet_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('ip_addresses', 'hostname')) {
                $table->string('hostname')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('ip_addresses', 'mac_address')) {
                $table->string('mac_address', 17)->nullable()->after('hostname');
            }
            if (!Schema::hasColumn('ip_addresses', 'status')) {
                $table->enum('status', ['available', 'assigned', 'reserved', 'dhcp'])->default('available')->after('mac_address');
            }
            if (!Schema::hasColumn('ip_addresses', 'device_type')) {
                $table->string('device_type')->nullable()->after('status');
            }
            if (!Schema::hasColumn('ip_addresses', 'description')) {
                $table->text('description')->nullable()->after('device_type');
            }
            if (!Schema::hasColumn('ip_addresses', 'assigned_to')) {
                $table->string('assigned_to')->nullable()->after('description');
            }
            if (!Schema::hasColumn('ip_addresses', 'last_seen')) {
                $table->timestamp('last_seen')->nullable()->after('assigned_to');
            }
        });
    }
    
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        if (!empty($indexes)) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$indexName}");
        }
    }
};
