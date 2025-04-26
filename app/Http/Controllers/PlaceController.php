<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\PlaceImage;
use App\Models\Facility;
use App\Services\DropboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlaceController extends Controller
{
    protected $dropboxService;

    public function __construct(DropboxService $dropboxService)
    {
        $this->dropboxService = $dropboxService;
    }

    /**
     * Store a newly created place in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // PlaceController.php
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'comment' => 'nullable|string', // Changed from description to comment
            'rating' => 'required|integer|min:1|max:5',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'facilities' => 'nullable|json', 
        ]);

        DB::beginTransaction();
        
        try {
            // Create the place
            $place = Place::create([
                'name' => $validated['name'],
                'address' => $validated['address'],
                'description' => null, // Keep description null
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
            ]);
            
            // Create the rating
            $rating = $place->ratings()->create([
                'user_id' => $request->user()->id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
            ]);

            // Handle image upload
            if ($request->hasFile('image')) {
                $dropboxUrl = $this->dropboxService->uploadFile($request->file('image'));
                
                if ($dropboxUrl) {
                    PlaceImage::create([
                        'place_id' => $place->id,
                        'image' => $dropboxUrl  
                    ]);
                } else {
                    return response()->json(['error' => 'Image upload to Dropbox failed'], 500);
                }
            }
            
            // Handle facilities
            if ($request->has('facilities')) {
                $facilityIds = json_decode($validated['facilities'], true);
                if (!empty($facilityIds)) {
                    foreach ($facilityIds as $facilityId) {
                        $facility = Facility::find($facilityId);
                        if ($facility) {
                            $place->facilities()->attach($facility->id);
                        }
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'place' => $place,
                'rating' => $rating
            ], 201);
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
// PlaceController.php
    public function index()
    {
        $places = Place::with(['images', 'facilities', 'ratings'])
            ->get()
            ->map(function ($place) {
                $place->average_rating = $place->ratings->avg('rating');
                return $place;
            });
        
        return response()->json($places);
    }

    public function show($id)
    {
        $place = Place::with(['images', 'facilities', 'ratings'])
            ->findOrFail($id);
        
        $place->average_rating = $place->ratings->avg('rating');
        
        return response()->json($place);
    }
}