<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── USERS ──────────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 100)->unique()->after('id');
            $table->string('full_name', 200)->after('username');
            $table->string('phone', 20)->nullable()->after('email');
            $table->enum('role', ['admin', 'owner', 'staff', 'customer'])->default('customer')->after('phone');
            $table->boolean('email_notif')->default(true)->after('role');
            $table->boolean('wa_notif')->default(false)->after('email_notif');
            $table->boolean('status')->default(true)->after('wa_notif');
            $table->boolean('is_deleted')->default(false)->after('status');
            $table->string('company_code', 50)->default('UNIV')->after('is_deleted');
            $table->string('created_by', 100)->nullable()->after('company_code');
            $table->string('updated_by', 100)->nullable()->after('created_by');
        });

        // ── TENANTS ────────────────────────────────────────────
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tenant_name', 200);
            $table->string('slug', 220)->unique();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('photo')->nullable();
            $table->string('banner')->nullable();
            $table->decimal('min_order', 12, 2)->default(0);
            $table->boolean('is_open')->default(false);
            $table->json('open_hours')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->string('company_code', 50)->default('UNIV');
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });

        // ── TENANT_USER (Staff Pivot) ──────────────────────────
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id']);
        });

        // ── CATEGORIES ────────────────────────────────────────
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->string('icon', 10)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->string('company_code', 50)->default('UNIV');
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });

        // ── MENUS ─────────────────────────────────────────────
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('photo')->nullable();
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->string('company_code', 50)->default('UNIV');
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });

        // ── ORDERS ────────────────────────────────────────────
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['cart', 'pending_payment', 'paid', 'processing', 'completed', 'expired', 'cancelled', 'refunded'])->default('cart');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('service_fee', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('company_code', 50)->default('UNIV');
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });

        // ── ORDER ITEMS ───────────────────────────────────────
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('menu_id')->nullable()->constrained()->nullOnDelete();
            $table->string('menu_name', 200);
            $table->decimal('price', 12, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 12, 2);
            $table->string('company_code', 50)->default('UNIV');
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });

        // ── PAYMENTS ──────────────────────────────────────────
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->onDelete('cascade');
            $table->string('transaction_id', 200)->nullable();
            $table->string('snap_token', 500)->nullable();
            $table->string('payment_type', 100)->nullable();
            $table->enum('status', ['pending', 'paid', 'expired', 'refunded', 'failed'])->default('pending');
            $table->decimal('gross_amount', 12, 2);
            $table->json('midtrans_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('company_code', 50)->default('UNIV');
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });

        // ── SUBSCRIPTIONS ─────────────────────────────────────
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('plan', 50);
            $table->date('billing_start');
            $table->date('billing_end');
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('billing_status', ['active', 'expired', 'cancelled', 'trial'])->default('trial');
            $table->string('invoice_number', 100)->nullable();
            $table->string('company_code', 50)->default('UNIV');
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });

        // ── ACTIVITY LOGS ─────────────────────────────────────
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->text('description');
            $table->string('ip_address', 50)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('company_code', 50)->default('UNIV');
            $table->timestamps();
        });

        // ── ERROR LOGS ────────────────────────────────────────
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level', 20)->default('error');
            $table->text('message');
            $table->longText('stack_trace')->nullable();
            $table->string('endpoint', 500)->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->enum('resolved_status', ['open', 'in_progress', 'resolved'])->default('open');
            $table->string('resolved_by', 100)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('company_code', 50)->default('UNIV');
            $table->timestamps();
        });

        // ── SYSTEM SETTINGS ───────────────────────────────────
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->string('group_name', 50)->default('general');
            $table->string('label', 200)->nullable();
            $table->text('description')->nullable();
            $table->json('options')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->string('company_code', 50)->default('UNIV');
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });

        // ── CONFIG VERSIONS ───────────────────────────────────
        Schema::create('config_versions', function (Blueprint $table) {
            $table->id();
            $table->integer('version');
            $table->string('changed_key', 100);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('changed_by', 100);
            $table->string('company_code', 50)->default('UNIV');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_versions');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('error_logs');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'full_name', 'phone', 'role', 'email_notif', 'wa_notif', 'status', 'is_deleted', 'company_code', 'created_by', 'updated_by']);
        });
    }
};
