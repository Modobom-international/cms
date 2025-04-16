<?php

namespace App\Repositories;

use App\Models\Page;

class PageRepository extends BaseRepository
{
    public function model()
    {
        return Page::class;
    }

    /**
     * Find a page by its ID
     *
     * @param int $id
     * @return \App\Models\Page|null
     */
    public function find($id)
    {
        return $this->model->find($id);
    }

    /**
     * Find a page by its slug
     *
     * @param string $slug
     * @return \App\Models\Page|null
     */
    public function findBySlug($slug)
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    /**
     * Update a page
     *
     * @param array $data
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function update($data, $id)
    {
        $page = $this->find($id);
        if (!$page) {
            throw new \Exception('Page not found');
        }
        return $page->update($data);
    }

    /**
     * Find multiple pages by their slugs
     *
     * @param array $slugs
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findBySlugs(array $slugs)
    {
        return $this->model->whereIn('slug', $slugs)->get();
    }

    /**
     * Find a page by slug and site ID
     *
     * @param string $slug
     * @param int $siteId
     * @return \App\Models\Page|null
     */
    public function findBySlugAndSite($slug, $siteId)
    {
        return $this->model->where('slug', $slug)
            ->where('site_id', $siteId)
            ->first();
    }

    /**
     * Get all pages with relationships
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllWithRelations()
    {
        return $this->model->with(['site'])->latest()->get();
    }

    /**
     * Get all pages for a specific site with relationships
     *
     * @param int $siteId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBySiteId($siteId)
    {
        return $this->model->with(['site'])
            ->where('site_id', $siteId)
            ->latest()
            ->get();
    }
}
