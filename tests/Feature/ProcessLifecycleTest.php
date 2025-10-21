<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_workflow_structure_is_available(): void
    {
        $this->markTestSkipped('Implement once authentication & API scaffolding are wired.');
    }
}
