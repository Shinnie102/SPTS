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
        Schema::create('class_grading_scheme', function (Blueprint $table) {
            $table->id('class_scheme_id');
            $table->unsignedBigInteger('class_section_id');
            $table->unsignedBigInteger('scheme_id');
            
            $table->foreign('class_section_id')
                ->references('class_section_id')
                ->on('class_section')
                ->onDelete('cascade');
            
            $table->foreign('scheme_id')
                ->references('scheme_id')
                ->on('grading_scheme')
                ->onDelete('cascade');
            
            // Prevent duplicate grading schemes for a class
            $table->unique(['class_section_id', 'scheme_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_grading_scheme');
    }
};
