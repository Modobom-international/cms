<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;

class CompanyIp extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'ip_address',
        'branch_name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Check if an IP address is a valid company IP
     */
    public static function isValidCompanyIp(string $ip): bool
    {
        return static::where('ip_address', $ip)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get branch name for an IP address
     */
    public static function getBranchName(string $ip): ?string
    {
        $companyIp = static::where('ip_address', $ip)
            ->where('is_active', true)
            ->first();

        return $companyIp ? $companyIp->branch_name : null;
    }
}
