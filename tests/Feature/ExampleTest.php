<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_halaman_mahasiswa_dapat_dibuka(): void
    {
        $this->get('/mahasiswa')->assertOk();
    }
}
