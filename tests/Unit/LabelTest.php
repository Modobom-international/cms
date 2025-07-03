<?php

namespace Tests\Unit;

use App\Models\Label;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_a_label()
    {
        $label = Label::create([
            'name' => 'Test Label',
            'color' => '#ff0000',
        ]);

        $this->assertDatabaseHas('labels', [
            'name' => 'Test Label',
            'color' => '#ff0000',
        ]);
    }
} 