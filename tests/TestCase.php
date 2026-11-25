<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        foreach (['DB_CONNECTION', 'DB_DATABASE'] as $key) {
            if (isset($_ENV[$key])) {
                $_SERVER[$key] = $_ENV[$key];
            }
        }

        parent::setUp();
    }
}
