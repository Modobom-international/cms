<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\PageExport;

class ProcessPageExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The export record instance.
     *
     * @var PageExport
     */
    protected $export;

    /**
     * Create a new job instance.
     *
     * @param PageExport $export
     * @return void
     */
    public function __construct(PageExport $export)
    {
        $this->export = $export;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $exporterPath = base_path('exporter');
        $exportId = $this->export->id;

        Log::info("Processing page export job for export ID: {$exportId}");

        try {
            // Run the exporter as a process
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows implementation
                $process = proc_open(
                    "cd {$exporterPath} && pnpm build",
                    [
                        0 => ["pipe", "r"],  // stdin
                        1 => ["pipe", "w"],  // stdout
                        2 => ["pipe", "w"]   // stderr
                    ],
                    $pipes
                );

                if (is_resource($process)) {
                    // Read output
                    $output = stream_get_contents($pipes[1]);
                    $errors = stream_get_contents($pipes[2]);

                    // Close all pipes
                    foreach ($pipes as $pipe) {
                        fclose($pipe);
                    }

                    // Close the process
                    $exitCode = proc_close($process);

                    if ($exitCode !== 0) {
                        Log::error("Export failed with exit code {$exitCode}. Error: {$errors}");
                    } else {
                        Log::info("Export completed successfully. Output: {$output}");
                    }
                } else {
                    Log::error("Failed to start exporter process");
                }
            } else {
                // Unix implementation
                $command = "cd {$exporterPath} && pnpm build";
                exec($command, $output, $exitCode);

                if ($exitCode !== 0) {
                    Log::error("Export failed with exit code {$exitCode}. Output: " . implode("\n", $output));
                } else {
                    Log::info("Export completed successfully. Output: " . implode("\n", $output));
                }
            }

            Log::info("Page export job completed for export ID: {$exportId}");
        } catch (\Exception $e) {
            Log::error("Error processing page export: " . $e->getMessage());
            $this->fail($e);
        }
    }
}
