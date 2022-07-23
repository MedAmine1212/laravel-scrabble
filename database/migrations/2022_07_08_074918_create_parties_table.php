<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->integer('typePartie');
            $table->string('reserve', 109)->default("AAAAAAAAABBCCDDDEEEEEEEEEEEEEEEFFGGHHIIIIIIIIJKLLLLLMMMNNNNNNOOOOOOPPQRRRRRRSSSSSSTTTTTTUUUUUUVVWXYZ  ");
            $table->string('grille', 225)->nullable();
            $table->string('motsFormees', 255)->default(" ");
            $table->timestamp('dateCreation')->default(\DB::raw('CURRENT_TIMESTAMP'));;
            $table->timestamp('dateDebutPartie')->nullable();;
            $table->timestamp('dateFinPartie')->nullable();
            $table->timestamp('counterLastUpdated')->nullable();
            $table->string('statusPartie');
            $table->string('currentJobId');
            $table->integer('tempsJoueur')->nullable();
            $table->integer('endVotes')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parties');
    }
}
