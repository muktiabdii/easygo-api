<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Place;

class PlaceController extends Controller
{
    public function index()
    {
        $places = Place::all();
        return response()->json($places);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
    
        $place = Place::create($validatedData);
        return response()->json($place, 201);
    }
}
