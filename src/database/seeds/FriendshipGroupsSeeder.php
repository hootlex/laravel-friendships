<?php

use Illuminate\Database\Seeder;
use Hootlex\Friendships\Models\FriendshipGroup;


class FriendshipGroupsSeeder extends Seeder {


    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {

       $data = [

           ['slug'=>'acquaintances', 'name'=>'Acquaintances'],
           ['slug'=>'close_friends', 'name'=>'Close Friends'],
           ['slug'=>'family',        'name'=>'Family']

       ];

        foreach ($data as $group) {

            $newGroup = new FriendshipGroup();
            $newGroup->slug = $group['slug'];
            $newGroup->name = $group['name'];
            $newGroup->save();

        }

    }

}