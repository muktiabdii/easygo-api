<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'place_id',
        'rating',
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function images()
    {
        return $this->hasMany(RatingImage::class);
    }
    
    // Add this relationship method to connect with facilities via the existing pivot table
    public function confirmedFacilities()
    {
        return $this->belongsToMany(Facility::class, 'rating_confirmed_facility');
    }
}