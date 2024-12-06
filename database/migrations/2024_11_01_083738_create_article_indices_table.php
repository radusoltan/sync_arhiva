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
        Schema::create('article_indices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_number');
            $table->string('elastic_id');
            $table->string('language');

            $table->unique(['article_number', 'elastic_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_indices');
    }
};
