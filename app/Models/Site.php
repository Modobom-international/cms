<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'domain',
        'description',
        'cloudflare_project_name',
        'cloudflare_domain_status',
        'branch',
        'user_id',
        'status',
        'language',
        'platform'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'language' => 'en',
        'platform' => 'google'
    ];

    /**
     * Get the user that owns the site.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the pages that belong to this site.
     */
    public function pages()
    {
        return $this->hasMany(Page::class, 'site_id');
    }
}
