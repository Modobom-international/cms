<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\PageExportRepository;
use App\Repositories\PageRepository;
use Illuminate\Support\Facades\Log;

class ProcessPageExports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page:process-exports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending page export requests';

    /**
     * The page export repository instance.
     *
     * @var PageExportRepository
     */
    protected $pageExportRepository;

    /**
     * The page repository instance.
     *
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * Create a new command instance.
     *
     * @param PageExportRepository $pageExportRepository
     * @param PageRepository $pageRepository
     * @return void
     */
    public function __construct(
        PageExportRepository $pageExportRepository,
        PageRepository $pageRepository
    ) {
        parent::__construct();
        $this->pageExportRepository = $pageExportRepository;
        $this->pageRepository = $pageRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to process page exports...');

        // Get all pending exports
        $pendingExports = $this->pageExportRepository->getPendingExports();

        if ($pendingExports->isEmpty()) {
            $this->info('No pending exports to process.');
            return 0;
        }

        $this->info('Found ' . $pendingExports->count() . ' pending exports to process.');

        foreach ($pendingExports as $export) {
            $this->info("Processing export ID: {$export->id}");

            try {
                // Mark as processing
                $export->markAsProcessing();

                // Get the pages data
                $slugs = $export->slugs;
                $pages = $this->pageRepository->findBySlugs($slugs);

                if ($pages->isEmpty()) {
                    $export->markAsFailed('No pages found with the requested slugs');
                    $this->error("Export ID {$export->id} failed: No pages found");
                    continue;
                }

                // Here you would implement the actual export logic
                // For example, you might generate files, send to a queue, etc.
                // For this example, we'll simulate successful processing

                // Generate a mock result path (in a real app, this would be the actual export file or location)
                $resultPath = 'exports/pages-' . $export->id . '-' . date('Ymd-His') . '.json';

                // Mark as completed with the result path
                $export->markAsCompleted($resultPath);

                $this->info("Export ID {$export->id} completed successfully. Result: {$resultPath}");

            } catch (\Exception $e) {
                // Log the error
                Log::error("Error processing export ID {$export->id}: " . $e->getMessage());

                // Mark as failed
                $export->markAsFailed($e->getMessage());

                $this->error("Export ID {$export->id} failed: " . $e->getMessage());
            }
        }

        $this->info('Finished processing page exports.');

        return 0;
    }
}
