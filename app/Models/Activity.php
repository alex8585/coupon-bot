<?php

namespace App\Models;

use App\Models\TgUser;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Activity extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'date:d-m-Y H:i',
        'updated_at' => 'date:d-m-Y H:i',
    ];

    public function user()
    {
        return $this->belongsTo(TgUser::class, 'tguser_id');
    }

    public function category()
    {
        return $this->belongsTo(Source::class, 'category_id');
    }
    public function shop()
    {
        return $this->belongsTo(Source::class, 'shop_id');
    }

    public function catsShop()
    {
        return $this->belongsTo(Shop::class, 'is_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }
}
