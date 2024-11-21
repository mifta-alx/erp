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
        Schema::create('rfq_sections', function (Blueprint $table) {
            $table->id('rfq_section_id');
            $table->unsignedBigInteger('rfq_id');
            $table->foreign('rfq_id')->references('rfq_id')->on('rfqs')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_sections');
    }
};
