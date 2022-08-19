<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->string('ad_headline');
            $table->text('ad_blob')->nullable();
            $table->string('url');
            $table->unsignedBigInteger('external_id');
            $table->string('address')->nullable();
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->double('price')->nullable();
            $table->double('condo_fees')->default(0);
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('area_id')->nullable();
            $table->integer('square_mt')->nullable();
            $table->integer('floor')->nullable();
            $table->boolean('garden')->default(0);
            $table->boolean('garage')->default(0);
            $table->string('contract')->default('vendita');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('apartments');
    }
};
