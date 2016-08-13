<?php

return [

    'tables' => [
        'fr_pivot' => 'friendships',
        'fr_groups_pivot' => 'user_friendship_groups'
    ],

    'groups' => [
        'acquaintances' => 0,
        'close_friends' => 1,
        'family' => 2
    ]

];