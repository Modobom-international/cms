<?php

namespace App\Repositories;

use App\Models\PageExport;

class PageExportRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return PageExport::class;
    }

    /**
     * Truncate the page_exports table
     * 
     * @return void
     */
    public function truncate()
    {
        $this->model->query()->delete();
    }

    /**
     * Get the latest export
     *
     * @return mixed
     */
    public function getLatestExport()
    {
        return $this->model->latest()->first();
    }
}