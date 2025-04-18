<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogActivityUser extends Model
{
    protected $fillable = [
        'user_id',
        'card_id',
        'action_type',
        'target_type',
        'target_id',
        'content',
    ];
    
    /**
     * Người thực hiện hành động
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Card liên quan (nếu có)
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
