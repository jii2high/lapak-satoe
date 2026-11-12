<?php

use App\Enums\ApplicationStatus;
use App\Enums\OrderStatus;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Enums\StoreStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', Role::values());

            $table->primary(['user_id', 'role']);
        });

        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();

            $table->index('user_id', 'idx_otp_user');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique('uq_categories_name');
            $table->string('slug', 80)->unique('uq_categories_slug');
            $table->timestamps();
        });

        Schema::create('seller_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name', 100);
            $table->string('nis', 30);
            $table->string('whatsapp', 20);
            $table->string('store_name', 60);
            $table->text('description');
            $table->enum('status', ApplicationStatus::values())->default(ApplicationStatus::Pending->value);
            $table->text('reject_note')->nullable();
            $table->timestamp('agreed_terms_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_app_status');
            $table->index('user_id', 'idx_app_user');
        });

        Schema::create('application_categories', function (Blueprint $table) {
            $table->foreignId('application_id')->constrained('seller_applications')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            $table->primary(['application_id', 'category_id']);
        });

        Schema::create('application_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('seller_applications')->cascadeOnDelete();
            $table->enum('type', ['product_photo', 'student_card']);
            $table->string('path');
            $table->timestamp('created_at')->nullable();

            $table->index('application_id', 'idx_appfile_app');
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('slug', 80)->unique('uq_stores_slug');
            $table->text('description')->nullable();
            $table->string('whatsapp', 20);
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->enum('status', StoreStatus::values())->default(StoreStatus::Closed->value);
            $table->boolean('is_suspended')->default(false);
            $table->timestamps();

            $table->index('status', 'idx_stores_status');
        });

        Schema::create('store_name_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('old_name', 60);
            $table->timestamp('changed_at');

            $table->index(['store_id', 'changed_at'], 'idx_snh_store');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('reserved')->default(0);
            $table->unsignedInteger('sold_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_suspended')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'is_active'], 'idx_products_store');
            $table->index('category_id', 'idx_products_cat');
            $table->index('price', 'idx_products_price');
            $table->index('sold_count', 'idx_products_sold');
            $table->index('name', 'idx_products_name');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products ADD CONSTRAINT chk_products_reserved CHECK (reserved <= stock)');
        }

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('sort')->default(0);

            $table->index(['product_id', 'sort'], 'idx_pimg_product');
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->timestamps();

            $table->unique(['cart_id', 'product_id'], 'uq_cartitem');
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'product_id'], 'uq_wish_product');
            $table->unique(['user_id', 'store_id'], 'uq_wish_store');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wishlists ADD CONSTRAINT chk_wish_target CHECK ((product_id IS NULL) <> (store_id IS NULL))');
        }

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique('uq_orders_code');
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->enum('status', OrderStatus::values())->default(OrderStatus::Unconfirmed->value);
            $table->unsignedInteger('total');
            $table->enum('cancelled_by', ['buyer', 'seller', 'system'])->nullable();
            $table->text('cancel_note')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('remind_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('seller_done_at')->nullable();
            $table->timestamp('auto_confirm_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status', 'created_at'], 'idx_orders_store');
            $table->index(['buyer_id', 'status', 'created_at'], 'idx_orders_buyer');
            $table->index(['status', 'expires_at'], 'idx_orders_expire');
            $table->index(['status', 'remind_at', 'reminded_at'], 'idx_orders_remind');
            $table->index(['status', 'auto_confirm_at'], 'idx_orders_autoconfirm');
            $table->index(['store_id', 'completed_at'], 'idx_orders_completed');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name', 120);
            $table->unsignedInteger('price');
            $table->unsignedInteger('qty');
            $table->unsignedInteger('subtotal');

            $table->index('order_id', 'idx_oi_order');
            $table->index('product_id', 'idx_oi_product');
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reported_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('violation_type', 50);
            $table->text('description');
            $table->enum('status', ReportStatus::values())->default(ReportStatus::New->value);
            $table->text('reporter_note')->nullable();
            $table->text('reported_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_reports_status');
            $table->index('reported_user_id', 'idx_reports_reported');
        });

        // MySQL 8.4 rejects a CHECK on FK columns with ON DELETE SET NULL; ReportService validates the target instead.
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'idx_notif_notifiable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('store_name_histories');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('application_files');
        Schema::dropIfExists('application_categories');
        Schema::dropIfExists('seller_applications');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('email_otps');
        Schema::dropIfExists('user_roles');
    }
};
