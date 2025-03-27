<?php

namespace App\Services;

use Spatie\Ssh\Ssh;

class SSHService
{
    protected $server;
    protected $user;
    protected $privateKey;

    public function __construct($server)
    {
        $this->server = $server;
        $this->user = config('services.ssh.ssh_user');
        $this->privateKey = config('services.ssh.ssh_private_key');
    }

    public function runScript($sourceDir, $slug)
    {
        try {
            $script = "bash /create_landing/create.sh {$sourceDir} {$slug}";

            $output = Ssh::create($this->user, $this->server)
                ->execute($script);

            return $output->getOutput();
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    private function handleException(RequestException $e)
    {
        if ($e->hasResponse()) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }

        return ['error' => 'Something went wrong'];
    }
}
