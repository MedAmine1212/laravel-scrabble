<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->timestamp('dateCreation')->default(\DB::raw('CURRENT_TIMESTAMP'));;
            $table->string('contenu', 50);
            $table->boolean('statusMessage');
            $table->unsignedBigInteger('partie')->nullable();
            $table->unsignedBigInteger('envoyeur');
            $table->foreign('partie')->references('id')->on('parties')->onDelete('cascade');
            $table->foreign('envoyeur')->references('id')->on('joueurs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
}
