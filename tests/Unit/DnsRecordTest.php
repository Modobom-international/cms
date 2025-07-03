<?php

namespace Tests\Unit;

use App\Models\DnsRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DnsRecordTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_a_dns_record()
    {
        $dns = DnsRecord::create([
            'cloudflare_id' => 'cf123',
            'zone_id' => 'zone123',
            'domain' => 'test.com',
            'type' => 'A',
            'name' => 'test',
            'content' => '1.2.3.4',
        ]);

        $this->assertDatabaseHas('dns_records', [
            'cloudflare_id' => 'cf123',
            'zone_id' => 'zone123',
            'domain' => 'test.com',
            'type' => 'A',
            'name' => 'test',
            'content' => '1.2.3.4',
        ]);
    }
} 