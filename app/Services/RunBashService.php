<?php

namespace App\Services;

class RunBashService
{
    public function runScriptCreateLanding($sourceDir, $slug)
    {
        try {
            $script = "bash /create_landing/create.sh {$sourceDir} {$slug}";
            $output = shell_exec($script . ' 2>&1');

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
