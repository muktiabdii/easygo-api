<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'comment',
        'latitude',
        'longitude',
        'status',
    ];

    public function images()
    {
        return $this->hasMany(PlaceImage::class);
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class, 'place_facility');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}