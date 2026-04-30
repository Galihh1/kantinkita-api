<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->date('trial_ends_at')->nullable()->after('status');
        });

        // Add approval_status to subscriptions for the admin approval flow
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('approval_status', 20)->default('pending')->after('billing_status');
            // pending -> approved -> rejected
            $table->text('admin_notes')->nullable()->after('approval_status');
            $table->unsignedBigInteger('approved_by')->nullable()->after('admin_notes');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('trial_ends_at');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'admin_notes', 'approved_by']);
        });
    }
};
