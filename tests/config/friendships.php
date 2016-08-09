<?php

return [

    'tables' => [
        'fr_pivot' => 'friendships',
        'fr_groups_pivot' => 'user_friendship_groups'
    ],

    'groups' => [
        'close_friends' => 0,
        'acquaintances' => 1
    ]

];