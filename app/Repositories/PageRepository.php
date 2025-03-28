<?php

namespace App\Repositories;

use App\Models\Page;

class PageRepository extends BaseRepository
{
    public function model()
    {
        return Page::class;
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    public function update($data, $id)
    {
        $page = $this->find($id);
        $page->update($data);
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
}
