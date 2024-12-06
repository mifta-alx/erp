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
        Schema::create('register_payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->string('reference');
            $table->unsignedBigInteger('invoice_id');
            $table->foreign('invoice_id')->references('invoice_id')->on('invoices')->onDelete('no action');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->foreign('vendor_id')->references('vendor_id')->on('vendors')->onDelete('no action');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('no action');
            $table->integer('journal');
            $table->double('amount');
            $table->timestamp('payment_date');
            $table->string('memo');
            $table->string('payment_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('register_payments');
    }
};
