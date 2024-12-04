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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id('receipt_id');
            $table->string('transaction_type');
            $table->string('reference');
            $table->unsignedBigInteger('rfq_id')->nullable();
            $table->foreign('rfq_id')->references('rfq_id')->on('rfqs')->onDelete('cascade');
            $table->unsignedBigInteger('sales_id')->nullable();
            $table->foreign('sales_id')->references('sales_id')->on('sales')->onDelete('cascade');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->foreign('vendor_id')->references('vendor_id')->on('vendors')->onDelete('cascade');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->integer('state');
            $table->string('source_document');
            $table->timestamp('scheduled_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
