<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsModelActivity;

class Page extends Model
{
    use HasFactory, LogsModelActivity;

    protected $fillable = [
        'site_id',
        'name',
        'slug',
        'content',
        'provider',
        'tracking_script'
    ];

    // Only log lightweight columns to keep activity logs short
    protected array $loggableAttributes = [
        'site_id',
        'name',
        'slug',
        'provider'
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
