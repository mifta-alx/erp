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
        Schema::create('sales', function (Blueprint $table) {
            $table->id('sales_id');
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('restrict');
            $table->double('taxes');
            $table->double('total');
            $table->dateTime('expiration');
            $table->dateTime('confirmation_date')->nullable();
            $table->integer('invoice_status');
            $table->integer('state');
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->foreign('payment_term_id')->references('payment_term_id')->on('payment_terms')->onDelete('restrict');
            $table->string('reference');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
