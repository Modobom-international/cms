<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class NotificationSystem extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'notification_system';

    protected $fillable = ['email', 'message', 'unread', 'created_at', 'updated_at'];
}
