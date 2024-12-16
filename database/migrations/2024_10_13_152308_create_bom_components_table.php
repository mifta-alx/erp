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
        Schema::create('bom_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bom_id');
            $table->foreign('bom_id')->references('bom_id')->on('boms')->onDelete('restrict');
            $table->unsignedBigInteger('material_id');
            $table->foreign('material_id')->references('material_id')->on('materials')->onDelete('restrict');
            $table->double('material_qty');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_components');
    }
};
