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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'comment' => 'nullable|string',
            'rating' => 'required|integer|min:1|max:5',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'file|mimes:jpeg,png,jpg|max:5120',
            'facilities' => 'nullable|json',
        ]);

        // Ambil user yang sedang login
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Update last_active user
        $user->update(['last_active' => now()]);

        DB::beginTransaction();

        try {
            // Buat tempat baru dengan user_id yang login
            $place = Place::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'address' => $validated['address'],
                'comment' => $validated['comment'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'status' => 'pending',
            ]);

            // Buat rating untuk tempat
            $rating = $place->ratings()->create([
                'user_id' => $user->id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
            ]);

            // Upload gambar ke Dropbox
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $dropboxUrl = $this->dropboxService->uploadFile($imageFile);
                    if (!$dropboxUrl) {
                        throw new \Exception('Image upload to Dropbox failed');
                    }

                    PlaceImage::create([
                        'place_id' => $place->id,
                        'image' => $dropboxUrl
                    ]);
                }
            }

            // Tambahkan fasilitas jika ada
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
                'rating' => $rating,
                'message' => 'Place submitted and pending approval'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function index()
    {
        $places = Place::with(['images', 'facilities', 'ratings'])
            ->where('status', 'approved')
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

    public function approve($id)
    {
        $place = Place::findOrFail($id);

        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        auth()->user()->update(['last_active' => now()]);

        // Update status place menjadi approved
        $place->update(['status' => 'approved']);

        // Ambil user yang menambahkan place ini (asumsi ada kolom user_id di places)
        $user = $place->user; // Pastikan di model Place ada relasi user()

        if ($user) {
            // Tambah reviews_count user tersebut sebanyak 1
            $user->increment('reviews_count');
        }

        return response()->json(['message' => 'Place approved successfully', 'place' => $place]);
    }

    public function reject($id)
    {
        $place = Place::findOrFail($id);

        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        auth()->user()->update(['last_active' => now()]);

        $place->update(['status' => 'rejected']);
        return response()->json(['message' => 'Place rejected successfully', 'place' => $place]);
    }

    public function destroy($id)
    {
        \Log::info("Starting deletion for place ID: {$id}");
        $place = Place::findOrFail($id);
        if (auth()->user()->role !== 'admin') {
            \Log::warning("Unauthorized attempt to delete place ID: {$id}");
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        auth()->user()->update(['last_active' => now()]);

        DB::beginTransaction();
        try {
            foreach ($place->images as $image) {
                try {
                    \Log::info("Attempting to delete Dropbox file: {$image->image}");
                    $this->dropboxService->deleteFile($image->image);
                } catch (\Exception $e) {
                    \Log::warning("Failed to delete Dropbox file: {$image->image}. Error: {$e->getMessage()}");
                }
                $image->delete();
            }
            \Log::info("Detaching facilities for place ID: {$id}");
            $place->facilities()->detach();
            \Log::info("Deleting ratings for place ID: {$id}");
            $place->ratings()->delete();
            \Log::info("Deleting place ID: {$id}");
            $place->delete();
            DB::commit();
            \Log::info("Place ID: {$id} deleted successfully");
            return response()->json(['message' => 'Place deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error deleting place ID: {$id}. Message: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function pending(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        auth()->user()->update(['last_active' => now()]);

        $places = Place::with(['images', 'facilities', 'ratings'])
            ->get()
            ->map(function ($place) {
                $place->average_rating = $place->ratings->avg('rating');
                return $place;
            });

        return response()->json($places);
    }
}