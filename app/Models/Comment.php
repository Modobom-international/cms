<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'card_id',
        'user_id',
        'content',
        'parent_id'
    ];
    
    // ✅ Quan hệ với User (người viết comment)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // ✅ Quan hệ với Card (thuộc thẻ nào)
    public function card()
    {
        return $this->belongsTo(Card::class);
    }
    
    // ✅ Quan hệ replies (dùng để lấy các comment trả lời)
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }
    
    // ✅ Quan hệ với comment cha (nếu là comment trả lời)
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }
}
