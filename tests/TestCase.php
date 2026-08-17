<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use Tests\Concerns\InteractsWithCaixaDatabase;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithCaixaDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpInteractsWithCaixaDatabase();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
