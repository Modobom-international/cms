<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_out'
    ];
}
