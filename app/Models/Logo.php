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
        //return public_path($this->new_url);
        $pathParts = pathinfo($this->new_url);
        if ($pathParts['extension'] == 'svg') {
            $path = $pathParts['dirname'] . '/' .  $pathParts['filename'] . '.png';
            //dump($path);
            return public_path($path);
        } else {
            return public_path($this->new_url);
        }



        //return URL::to($this->new_url);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'logo_id');
    }
}
