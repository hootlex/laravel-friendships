<?php

function createUser($overrides = [], $limit = 1){
    return factory(\App\User::class, $limit)->create($overrides);
}
