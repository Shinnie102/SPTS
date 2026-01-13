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
        Schema::create('course', function (Blueprint $table) {
            $table->id('course_id');
            $table->string('course_code', 20)->unique();
            $table->string('course_name', 200);
            $table->integer('credits');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('status_id')
                ->references('status_id')
                ->on('course_status')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course');
    }
};
