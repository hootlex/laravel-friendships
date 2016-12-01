<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateFriendshipGroupsTable
 */
class CreateFriendshipGroupsTable extends Migration
{

    public function up() {

        Schema::create(config('friendships.tables.fr_groups'), function (Blueprint $table) {

            $table->increments('id');
            $table->string('slug');
            $table->string('name');
            $table->timestamps();

        });

    }

    public function down() {
        Schema::dropIfExists(config('friendships.tables.fr_groups'));
    }

}