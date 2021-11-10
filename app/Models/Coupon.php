<?php

namespace App\Models;

use App\Models\Logo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory;

    protected $guarded = [];



    public function logo()
    {
        return $this->belongsTo(Logo::class, 'logo_id');
    }

    public function scopeSourceShop($query, $shop_id)
    {
        return $query->where('type', 'shop')->where('source_id', $shop_id)
            ->where(function ($query) {
                $query->where('date_end', '>', now());
                $query->orWhere('date_end', null);
            })
            ->with('logo')->orderBy('date_start', 'DESC');
    }

    public function scopeCategory($query, $category_id)
    {
        return $query->where('type', 'category')
            ->where('source_id', $category_id)
            ->where(function ($query) {
                $query->where('date_end', '>', now());
                $query->orWhere('date_end', null);
            })
            ->with('logo')
            ->orderBy('date_start', 'DESC');
    }

    public function scopeExpiring($query, $category_id)
    {
        return $query->category($category_id)->where('date_end', '<', now()->endOfDay()->addMinute(1));
    }
}
