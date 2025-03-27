<?php

namespace App\Jobs;

use App\Services\RunBashService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateLandingPage implements ShouldQueue
{
    use Queueable;

    protected $dir;
    protected $slug;

    /**
     * Create a new job instance.
     */
    public function __construct($dir, $slug, $user_id)
    {
        $this->dir = $dir;
        $this->slug = $slug;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $runBash = new RunBashService();
        $result = $runBash->runScriptCreateLanding($dir, $slug);

        if (array_key_exists('error', $result)) {
            broadcast(new NotificationSystem(
                [
                    'message' => ' ❌ Lỗi không cài đặt được landing page',
                    'user_id'  => $user_id,
                    'status_read' => 0
                ],
            ));

            return;
        } else {
            broadcast(new NotificationSystem(
                [
                    'message' => ' ✅ Hoàn tất cài đặt được landing page',
                    'user_id'  => $user_id,
                    'status_read' => 0
                ],
            ));
        }
    }
}
