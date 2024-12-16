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
        Schema::create('sales_components', function (Blueprint $table) {
            $table->id('sales_component_id');
            $table->unsignedBigInteger('sales_id');
            $table->foreign('sales_id')->references('sales_id')->on('sales')->onDelete('restrict');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('restrict');
            $table->string('description')->nullable();
            $table->string('display_type')->nullable();
            $table->double('qty');
            $table->double('unit_price');
            $table->double('tax');
            $table->double('subtotal');
            $table->double('qty_received')->default(0);
            $table->double('qty_to_invoice')->default(0);
            $table->double('qty_invoiced')->default(0);
            $table->double('reserved')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_components');
    }
};
