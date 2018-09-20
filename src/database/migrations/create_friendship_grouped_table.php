<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateFriendshipGroupedTable
 */
class CreateFriendshipGroupedTable extends Migration
{

    public function up() {

        Schema::create(config('friendships.tables.fr_groups_pivot'), function (Blueprint $table) {

            $table->integer('friendship_id')->unsigned();
            $table->integer('group_id')->unsigned();
            $table->morphs('friend');

            $table->foreign('group_id')
                ->references('id')
                ->on(config('friendships.tables.fr_groups'))
                ->onDelete('cascade');

            $table->foreign('friendship_id')
                ->references('id')
                ->on(config('friendships.tables.fr_pivot'))
                ->onDelete('cascade');

            $table->unique(['friendship_id', 'friend_id', 'friend_type', 'group_id'], 'unique');

        });

    }

    public function down() {
        Schema::dropIfExists(config('friendships.tables.fr_groups_pivot'));
    }

}