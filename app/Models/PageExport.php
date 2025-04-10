<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsModelActivity;

class PageExport extends Model
{
    use HasFactory, LogsModelActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slugs',
        'result_path',
        'status',
        'site_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'slugs' => 'array',
    ];

    /**
     * Get the site that owns this export.
     */
    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
