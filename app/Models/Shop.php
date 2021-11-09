<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopeCategory($query, $category_id)
    {

        return $query->select([
            'shops.id',
            'shops.name'
        ])->distinct()->join('coupons', function ($q) {
            $q->on('coupons.shop_id', '=', 'shops.id');
        })->where('coupons.type', 'category')->where('coupons.source_id', $category_id);
    }
}
