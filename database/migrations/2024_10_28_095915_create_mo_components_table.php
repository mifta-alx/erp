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
        Schema::create('mo_components', function (Blueprint $table) {
            $table->id('mo_component_id');
            $table->unsignedBigInteger('mo_id');
            $table->foreign('mo_id')->references('mo_id')->on('manufacturing_orders')->onDelete('restrict');
            $table->double('to_consume');
            $table->unsignedBigInteger('material_id');
            $table->foreign('material_id')->references('material_id')->on('materials')->onDelete('restrict');
            $table->double('reserved');
            $table->double('consumed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mo_components');
    }
};
