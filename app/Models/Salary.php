<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsModelActivity;

class Salary extends Model
{
    use LogsModelActivity;
    
    protected $table = 'salaries';
    
    protected $fillable = [
        'employee_id',
        'basic_salary',
        'bonus',
        'deductions',
        'net_salary',
        'social_insurance',
        'payment_date'
    ];
}
