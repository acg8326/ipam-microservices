<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->string('hostname')->nullable()->after('ip_address');
            $table->string('mac_address', 17)->nullable()->after('hostname');
            $table->enum('status', ['available', 'assigned', 'reserved', 'dhcp'])->default('available')->after('mac_address');
            $table->string('device_type')->nullable()->after('status');
            $table->text('description')->nullable()->after('device_type');
            $table->string('assigned_to')->nullable()->after('description');
            $table->timestamp('last_seen')->nullable()->after('assigned_to');
            
            $table->index('status');
            $table->index('hostname');
            $table->index('mac_address');
        });
    }

    public function down(): void
    {
        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['hostname']);
            $table->dropIndex(['mac_address']);
            
            $table->dropColumn([
                'hostname',
                'mac_address',
                'status',
                'device_type',
                'description',
                'assigned_to',
                'last_seen',
            ]);
        });
    }
};
