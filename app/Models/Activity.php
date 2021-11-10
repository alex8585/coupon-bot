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
}
