<?php

namespace Tests\Unit;

use App\Models\Domain;
use App\Repositories\DomainRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_and_find_domain()
    {
        $repo = new DomainRepository();
        $domain = $repo->updateOrCreate([
            'domain' => 'example.com'
        ], [
            'is_locked' => false,
            'registrar' => 'test-registrar',
            'status' => 'active'
        ]);

        $this->assertInstanceOf(Domain::class, $domain);
        $this->assertEquals('example.com', $domain->domain);

        $found = $repo->findByDomain('example.com');
        $this->assertNotNull($found);
        $this->assertEquals($domain->id, $found->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_update_domain()
    {
        $repo = new DomainRepository();
        $domain = $repo->updateOrCreate([
            'domain' => 'update.com'
        ], [
            'is_locked' => false,
            'registrar' => 'test-registrar',
            'status' => 'active'
        ]);

        $updated = $repo->update($domain->id, ['is_locked' => true]);
        $this->assertTrue($updated->is_locked);
    }
} 