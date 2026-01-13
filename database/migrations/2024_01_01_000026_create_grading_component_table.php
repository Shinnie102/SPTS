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
        Schema::create('grading_component', function (Blueprint $table) {
            $table->id('component_id');
            $table->unsignedBigInteger('scheme_id');
            $table->string('component_name', 200);
            $table->decimal('weight', 5, 2); // e.g., 30.00 for 30%
            $table->decimal('max_score', 5, 2)->default(10.00);
            $table->text('description')->nullable();
            
            $table->foreign('scheme_id')
                ->references('scheme_id')
                ->on('grading_scheme')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_component');
    }
};
