<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJoueursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('joueurs', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('nom',50)->unique();
            $table->string('password',255)->unique();
            $table->string('image', 255)->nullable();
            $table->string('chevalet', 7)->nullable();
            $table->integer('score')->nullable();
            $table->boolean('statusJoueur');
            $table->integer('ordre')->nullable();
            $table->boolean('disconnected')->default(false);
            $table->boolean('voted')->default(false);
            $table->timestamp("pingedAt")->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->unsignedBigInteger('partieId')->nullable();
            $table->foreign('partieId')->references('id')->on('parties')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('joueurs');
    }
}
