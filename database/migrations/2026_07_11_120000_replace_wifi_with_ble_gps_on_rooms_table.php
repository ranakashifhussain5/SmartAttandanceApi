<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique(['wifi_mac']);
            $table->dropColumn(['wifi_name', 'wifi_mac']);

            // Room number as broadcast in the beacon's iBeacon Major field. No secret/token —
            // presence is proven by RSSI + GPS, not by a rotating code.
            $table->unsignedSmallInteger('beacon_major')->nullable()->unique()->after('room_no');
            // Server rejects submissions weaker than this — larger rooms may need a lower (more negative) value.
            $table->integer('rssi_threshold')->default(-75)->after('beacon_major');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['beacon_major', 'rssi_threshold']);
            $table->string('wifi_name', 100)->nullable();
            $table->string('wifi_mac', 17)->nullable()->unique();
        });
    }
};
