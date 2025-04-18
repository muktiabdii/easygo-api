<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'places';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',         // Nama tempat (opsional, jika Anda ingin menyimpannya)
        'address',      // Alamat tempat (opsional)
        'description',  // Deskripsi tempat (opsional)
        'latitude',     // Latitude (wajib)
        'longitude',    // Longitude (wajib)
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'latitude' => 'decimal:8',  // Cast latitude ke tipe decimal
        'longitude' => 'decimal:8', // Cast longitude ke tipe decimal
    ];
}