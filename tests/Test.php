<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class Test extends TestCase
{
    public function setUp() {
        parent::setUp();

        /*
         * To show deprecated errors, you can set the env PHPUNIT_SHOW_DEPRECATED as below:
         * PHPUNIT_SHOW_DEPRECATED=1 vendor/bin/phpunit
         */
        if (getenv('PHPUNIT_SHOW_DEPRECATED') == true) {
            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                switch ($errno) {
                    case E_USER_DEPRECATED:
                        echo("Deprecated : $errstr \n in $errfile on line $errline \n");
                        break;
                }

                return true;
            }, E_USER_DEPRECATED);
        }
    }
}
