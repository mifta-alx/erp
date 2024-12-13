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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id('invoice_id');
            $table->string('transaction_type');
            $table->string('reference');
            $table->unsignedBigInteger('vendor_id')->nullable()->nullable();
            $table->foreign('vendor_id')->references('vendor_id')->on('vendors')->onDelete('cascade');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->unsignedBigInteger('rfq_id')->nullable();
            $table->foreign('rfq_id')->references('rfq_id')->on('rfqs')->onDelete('cascade');
            $table->unsignedBigInteger('sales_id')->nullable();
            $table->foreign('sales_id')->references('sales_id')->on('sales')->onDelete('cascade');
            $table->dateTime('invoice_date')->nullable();
            $table->dateTime('accounting_date')->nullable();
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->foreign('payment_term_id')->references('payment_term_id')->on('payment_terms')->onDelete('cascade');
            $table->dateTime('due_date')->nullable();
            $table->dateTime('delivery_date')->nullable();
            $table->integer('state');
            $table->string('source_document');
            $table->integer('payment_status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
