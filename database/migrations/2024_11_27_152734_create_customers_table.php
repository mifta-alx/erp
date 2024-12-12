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
        Schema::create('customers', function (Blueprint $table) {
            $table->id('customer_id');
            $table->integer('type')->default(1);
            $table->string('company');
            $table->string('name');
            $table->string('street');
            $table->string('city');
            $table->string('state');
            $table->string('zip', 10)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('mobile', 15)->nullable();
            $table->string('email')->unique();
            $table->string('image_url')->nullable();
            $table->string('image_uuid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
