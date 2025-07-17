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
        Schema::create('chess_matches_results', function (Blueprint $table) {
            $table->id();
            $table->string('match_link');
            $table->string('match_type')->nullable();
            $table->string('white')->nullable();
            $table->string('black')->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->string('white_result')->nullable();
            $table->string('black_result')->nullable();
            $table->string('termination')->nullable();

            $table->unsignedBigInteger('challenge_id')->nullable();
            $table->foreign('challenge_id')
                ->references('id')
                ->on('challenges')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chess_matches_results', function (Blueprint $table) {
            $table->dropForeign(['challenge_id']);
        });

        Schema::dropIfExists('chess_matches_results');
    }
};
