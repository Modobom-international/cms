<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'name',
    ];

    /**
     * Get the pages that belong to this site.
     */
    public function pages()
    {
        return $this->hasMany(Page::class, 'site_id');
    }
}