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
        Schema::create('rfq_components', function (Blueprint $table) {
            $table->id('rfq_component_id');
            $table->unsignedBigInteger('rfq_id');
            $table->foreign('rfq_id')->references('rfq_id')->on('rfqs')->onDelete('cascade');
            $table->unsignedBigInteger('material_id')->nullable();
            $table->foreign('material_id')->references('material_id')->on('materials')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->string('display_type')->nullable();
            $table->double('qty');
            $table->double('unit_price');
            $table->double('tax');
            $table->double('subtotal');
            $table->double('qty_received')->default(0);
            $table->double('qty_to_invoice')->default(0);
            $table->double('qty_invoiced')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_components');
    }
};
