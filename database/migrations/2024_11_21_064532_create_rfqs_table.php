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
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id('rfq_id');
            $table->string('reference');
            $table->unsignedBigInteger('vendor_id');
            $table->foreign('vendor_id')->references('vendor_id')->on('vendors')->onDelete('restrict');
            $table->string('vendor_reference')->nullable();
            $table->integer('state');
            $table->double('taxes');
            $table->double('total');
            $table->dateTime('order_date');
            $table->dateTime('confirmation_date')->nullable();
            $table->integer('invoice_status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};
