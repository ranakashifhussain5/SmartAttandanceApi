<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn('wifi_mac_detected');

            // UUID the app actually read from the beacon's BLE advertisement.
            $table->string('detected_uuid', 36)->nullable()->after('student_id');
            // Room number the app actually read from the beacon's BLE advertisement.
            $table->unsignedSmallInteger('detected_major')->nullable()->after('detected_uuid');
            $table->integer('rssi')->nullable()->after('detected_major');
            $table->decimal('latitude', 10, 7)->nullable()->after('rssi');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn(['detected_uuid', 'detected_major', 'rssi', 'latitude', 'longitude']);
            $table->string('wifi_mac_detected', 17)->nullable();
        });
    }
};
