<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subnets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('network'); // e.g., "10.0.0.0"
            $table->unsignedTinyInteger('cidr'); // e.g., 24
            $table->string('gateway')->nullable();
            $table->unsignedInteger('vlan_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('subnets')->onDelete('set null');
            $table->unsignedInteger('location_id')->nullable();
            $table->timestamps();
            
            $table->unique(['network', 'cidr']);
            $table->index('parent_id');
            $table->index('vlan_id');
        });

        // Add subnet_id to ip_addresses table
        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->foreignId('subnet_id')->nullable()->after('id')->constrained('subnets')->onDelete('cascade');
            $table->index('subnet_id');
        });
    }

    public function down(): void
    {
        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->dropForeign(['subnet_id']);
            $table->dropColumn('subnet_id');
        });
        
        Schema::dropIfExists('subnets');
    }
};
