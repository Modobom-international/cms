<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\PageExportRepository;
use App\Repositories\PageRepository;
use Illuminate\Support\Facades\Log;

class ProcessSinglePageExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page:process-export {export_id : The ID of the export to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process a specific page export request by ID';

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
        $exportId = $this->argument('export_id');
        $this->info("Starting to process page export ID: {$exportId}");

        // Get the export request
        $export = $this->pageExportRepository->getExportById($exportId);

        if (!$export) {
            $this->error("Export ID {$exportId} not found.");
            return 1;
        }

        if ($export->status !== 'pending') {
            $this->warn("Export ID {$exportId} is not pending (current status: {$export->status}).");
            return 0;
        }

        try {
            // Mark as processing
            $export->markAsProcessing();
            $this->info("Export ID {$exportId} marked as processing");

            // Get the pages data
            $slugs = $export->slugs;
            $pages = $this->pageRepository->findBySlugs($slugs);

            if ($pages->isEmpty()) {
                $export->markAsFailed('No pages found with the requested slugs');
                $this->error("Export ID {$exportId} failed: No pages found");
                return 1;
            }

            $this->info("Found " . $pages->count() . " pages to export");

            // Here you would implement the actual export logic
            // For example, you might generate files, send to a queue, etc.
            // For this example, we'll simulate successful processing

            // Simulate some processing time
            $this->info("Processing the export...");
            sleep(2); // In a real scenario, replace this with actual processing

            // Generate a mock result path (in a real app, this would be the actual export file or location)
            $resultPath = 'exports/pages-' . $export->id . '-' . date('Ymd-His') . '.json';

            // Mark as completed with the result path
            $export->markAsCompleted($resultPath);

            $this->info("Export ID {$exportId} completed successfully. Result: {$resultPath}");

        } catch (\Exception $e) {
            // Log the error
            Log::error("Error processing export ID {$exportId}: " . $e->getMessage());

            // Mark as failed
            $export->markAsFailed($e->getMessage());

            $this->error("Export ID {$exportId} failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
