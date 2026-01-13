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
        Schema::create('grading_scheme', function (Blueprint $table) {
            $table->id('scheme_id');
            $table->string('scheme_code', 50)->unique();
            $table->string('scheme_name', 200);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('status_id')
                ->references('status_id')
                ->on('grading_scheme_status')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_scheme');
    }
};
