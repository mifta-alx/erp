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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->unsignedBigInteger('product_category_id')->unsigned();
            $table->foreign('product_category_id')->references('id')->on('product_categories_table')->onDelete('cascade');
            $table->double('sales_price');
            $table->double('cost');
            $table->string('barcode');
            $table->string('internal_reference');
            $table->string('product_tag');
            $table->string('company');
            $table->text('notes');
            $table->string('image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
