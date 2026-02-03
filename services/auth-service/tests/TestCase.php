<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up Passport for testing
        if (class_exists(Passport::class)) {
            Passport::useClientModel(\Laravel\Passport\Client::class);
        }
    }
}
