<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['name', 'phone']);
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color', 50)->nullable();
            $table->string('plate', 20)->unique();
            $table->string('vin', 40)->nullable()->unique();
            $table->unsignedInteger('odometer')->nullable();
            $table->timestamps();
            $table->index(['make', 'model']);
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 24)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('pending')->index();
            $table->text('complaint');
            $table->text('diagnosis')->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('parts_cost', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid', 12, 2)->default(0);
            $table->unsignedSmallInteger('warranty_days')->default(0);
            $table->text('warranty_terms')->nullable();
            $table->timestamps();
        });

        Schema::create('work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('supplier')->nullable();
            $table->decimal('stock', 10, 2)->default(0);
            $table->decimal('minimum_stock', 10, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 12);
            $table->decimal('quantity', 10, 2);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 24)->default('cash');
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->index();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('concept');
            $table->string('category', 60)->default('other');
            $table->string('supplier')->nullable();
            $table->decimal('amount', 12, 2);
            $table->timestamp('spent_at')->index();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('position');
            $table->string('phone', 30)->nullable();
            $table->decimal('salary', 12, 2)->default(0);
            $table->string('pay_frequency', 20)->default('weekly');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payroll_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_for');
            $table->timestamp('paid_at');
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('payroll_payments');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('work_order_items');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('customers');
    }
};
