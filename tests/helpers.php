<?php

function createUser($overrides = [], $amount = 1){
    return factory(\App\User::class, $amount)->create($overrides);
}
