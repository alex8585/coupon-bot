<?php

namespace App\Models;

use Illuminate\Support\Facades\URL;
use Modules\Coupon\Entities\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Logo extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getUrlAttribute()
    {
        return "http://176.119.147.16/storage/logo/1634037122_20551-48e32a7541e22f3b.jpg";
        //return URL::to($this->new_url);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'logo_id');
    }
}
