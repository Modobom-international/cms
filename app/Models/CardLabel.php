<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardLabel extends Model
{
    use HasFactory;
    protected $table = 'card_labels';  // Đặt tên bảng (nếu khác tên mặc định)
    protected $fillable = ['card_id', 'label_id'];  // Các cột có thể điền vào
    public $timestamps = false;  // Bảng này không cần timestamps
    
    // Không cần định nghĩa quan hệ ở đây vì đã có trong model Card và Label
}