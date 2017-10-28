<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up ()
    {
        Schema::create( 'cars', function ( Blueprint $table ) {
            $table->increments( 'id' );
            $table->string( 'uid' )->unique();
            $table->integer( 'year' )->unsigned();
            $table->string( 'brand' );
            $table->string( 'series' );
            $table->string( 'fuel' );
            $table->string( 'body' );
            $table->string( 'model' );
            $table->string( 'version' )->nullable();
            $table->json( 'data' );
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down ()
    {
        Schema::dropIfExists( 'cars' );
    }
}
