<?php

namespace Tests\Unit;

use App\Models\Workspace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_a_workspace()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'name' => 'Test Workspace',
            'owner_id' => $user->id,
        ]);

        $this->assertDatabaseHas('workspaces', [
            'name' => 'Test Workspace',
            'owner_id' => $user->id,
        ]);
    }
} 