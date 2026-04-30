<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip', 50)->nullable()->after('phone');
            $table->string('no_ktp', 50)->nullable()->after('nip');
            $table->date('dob')->nullable()->after('no_ktp');
            $table->enum('akses', ['pasien', 'karyawan', 'diklat', 'umum'])->default('umum')->after('dob');
            $table->boolean('profile_completed')->default(true)->after('akses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nip', 'no_ktp', 'dob', 'akses', 'profile_completed']);
        });
    }
};
