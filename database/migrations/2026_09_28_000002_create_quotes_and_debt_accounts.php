<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('number', 24)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('work_order_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('drafted')->index();
            $table->text('complaint');
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('parts_cost', 12, 2)->default(0);
            $table->decimal('other_cost', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->string('authorization_note')->nullable();
            $table->timestamps();
        });

        Schema::create('debt_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('company_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->date('due_at')->nullable();
            $table->string('agreement')->nullable();
            $table->string('status', 16)->default('open')->index();
            $table->timestamps();
            $table->index(['customer_id', 'status']);
        });

        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamp('paid_at')->index();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
        Schema::dropIfExists('debt_accounts');
        Schema::dropIfExists('quotes');
    }
};
