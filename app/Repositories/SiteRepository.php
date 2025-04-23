<?php

namespace App\Repositories;

use App\Models\Site;

class SiteRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Site::class;
    }

    /**
     * Find a site by ID with relationships
     *
     * @param int $id
     * @return \App\Models\Site|null
     */
    public function findWithRelations($id)
    {
        return $this->model->with(['user'])->find($id);
    }

    /**
     * Get all sites with relationships
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllWithRelations()
    {
        return $this->model->with(['user'])->latest()->get();
    }

    /**
     * Create a new site
     *
     * @param array $data
     * @return \App\Models\Site
     */
    public function create($data)
    {
        return $this->model->create($data);
    }

    /**
     * Update a site
     *
     * @param array $data
     * @param int $id
     * @return bool
     */
    public function update($data, $id)
    {
        $site = $this->model->find($id);
        if (!$site) {
            throw new \Exception('Site not found');
        }
        return $site->update($data);
    }

    public function getSlugByDomain($domain)
    {
        return $this->model->with('pages')->where('domain', $domain)->first();
    }

    public function getDomainExists($domain)
    {
        return $this->model->where('domain', $domain)->exists();
    }

    /**
     * Get all domains that are currently used in sites
     *
     * @return array
     */
    public function getAllSiteDomains()
    {
        return $this->model->pluck('domain')->toArray();
    }
}
