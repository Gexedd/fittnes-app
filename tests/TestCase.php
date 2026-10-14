<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Los tests no compilan assets. Sin esto, cualquier vista que use @vite
        // explota con ViteManifestNotFoundException cuando no hay manifiesto ni
        // archivo "hot" (por ejemplo en CI, donde no se ejecuta npm).
        $this->withoutVite();
    }
}
