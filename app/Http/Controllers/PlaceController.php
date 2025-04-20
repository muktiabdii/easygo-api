<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\PlaceImage;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlaceController extends Controller
{
    /**
     * Store a newly created place in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'description' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'facilities' => 'nullable|json', // Validasi sebagai JSON string
        ]);
    
        DB::beginTransaction();
        
        try {
            // Create the place - without rating field
            $place = Place::create([
                'name' => $validated['name'],
                'address' => $validated['address'],
                'description' => $validated['description'] ?? null,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
            ]);
            
            // Handle image upload if present
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('place-images', 'public');
                
                PlaceImage::create([
                    'place_id' => $place->id,
                    'image' => $imagePath
                ]);
            }
            
            // Handle facilities if present
            if ($request->has('facilities')) {
                $facilityIds = json_decode($validated['facilities'], true); // Ubah JSON string menjadi array
                if (!empty($facilityIds)) {
                    foreach ($facilityIds as $facilityId) {
                        $facility = Facility::find($facilityId); // Temukan fasilitas berdasarkan ID
                        if ($facility) {
                            $place->facilities()->attach($facility->id); // Lampirkan fasilitas ke tempat
                        }
                    }
                }
            }
            
            DB::commit();
            
            return response()->json($place, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all places
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $places = Place::with(['images', 'facilities'])->get();
        $places->each(function ($place) {
            $place->images->each(function ($image) {
                $image->image_url = asset('storage/' . $image->image); // Tambahkan full URL
            });
        });
        return response()->json($places);
    }

    /**
     * Get a specific place
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $place = Place::with(['images', 'facilities'])->findOrFail($id);
        return response()->json($place);
    }
}