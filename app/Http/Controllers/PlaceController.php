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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'description' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'facilities' => 'nullable|json', 
        ]);
    
        DB::beginTransaction();
        
        try {
            $place = Place::create([
                'name' => $validated['name'],
                'address' => $validated['address'],
                'description' => $validated['description'] ?? null,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
            ]);
            
            if ($request->hasFile('image')) {
                // Upload to Dropbox and get the URL
                $dropboxUrl = $this->dropboxService->uploadFile($request->file('image'));
                
                if ($dropboxUrl) {
                    // Store the Dropbox URL in the database
                    PlaceImage::create([
                        'place_id' => $place->id,
                        'image' => $dropboxUrl  
                    ]);
                } else {
                    return response()->json(['error' => 'Image upload to Dropbox failed'], 500);
                }
            }
            
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