<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40);
            $table->string('company')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->string('unit', 30)->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'reviewing', 'quoted', 'closed', 'cancelled'])->default('new');
            $table->text('admin_note')->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->index('product_id');
            $table->timestamps();
            $table->index(['status', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
