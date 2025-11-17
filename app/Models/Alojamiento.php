<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alojamiento extends Model
{
    
    use HasFactory;
     protected $fillable = [
        'user_id','title','type','price','price_period','guests',
        'city','neighborhood','address','description',
        'amenities','phone','cover_path','photos',
    ];

    protected $casts = [
        'amenities' => 'array',
        'photos' => 'array',
    ];

    public function user()
{
    return $this->belongsTo(User::class);
}

}
