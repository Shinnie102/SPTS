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
        Schema::create('faculty_major', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('major_id');
            $table->timestamps();
            
            $table->foreign('faculty_id')
                ->references('faculty_id')
                ->on('faculty')
                ->onDelete('cascade');
            
            $table->foreign('major_id')
                ->references('major_id')
                ->on('major')
                ->onDelete('cascade');
            
            // Prevent duplicate relationships
            $table->unique(['faculty_id', 'major_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faculty_major');
    }
};
