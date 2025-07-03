<?php

namespace Tests\Unit;

use App\Services\ApplicationLogger;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class ApplicationLoggerTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_log_site_page_export_deploy()
    {
        $logger = new ApplicationLogger();
        $this->assertNull($logger->logSite('test_action', ['foo' => 'bar']));
        $this->assertNull($logger->logPage('test_action', ['foo' => 'bar']));
        $this->assertNull($logger->logExport('test_action', ['foo' => 'bar']));
        $this->assertNull($logger->logDeploy('test_action', ['foo' => 'bar']));
    }
} 