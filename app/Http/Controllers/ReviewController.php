<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Rating;
use App\Models\RatingImage;
use App\Services\DropboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    protected $dropboxService;

    public function __construct(DropboxService $dropboxService)
    {
        $this->dropboxService = $dropboxService;
    }

    /**
     * Check if the authenticated user has reviewed a place.
     *
     * @param int $placeId
     * @return \Illuminate\Http\Response
     */
    public function hasReviewed($placeId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['hasReviewed' => false], 401);
        }

        $hasReviewed = Rating::where('place_id', $placeId)
            ->where('user_id', $user->id)
            ->exists();

        return response()->json(['hasReviewed' => $hasReviewed]);
    }

    /**
     * Store a newly created review in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'place_id' => 'required|integer|exists:places,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'images' => 'nullable|array|max:5',
            'images.*' => 'file|mimes:jpeg,png,jpg|max:5120',
            'facilities' => 'nullable|json',
        ]);

        // Check if user has already reviewed this place
        $existingReview = Rating::where('place_id', $validated['place_id'])
            ->where('user_id', Auth::id())
            ->exists();

        if ($existingReview) {
            return response()->json(['error' => 'Anda sudah memberikan ulasan untuk tempat ini'], 403);
        }

        DB::beginTransaction();

        try {
            // Find the place
            $place = Place::findOrFail($validated['place_id']);

            // Create the rating
            $rating = $place->ratings()->create([
                'user_id' => Auth::id(),
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? '',
            ]);

            // Handle images upload
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $dropboxUrl = $this->dropboxService->uploadFile($imageFile, '/review-images');

                    if ($dropboxUrl) {
                        RatingImage::create([
                            'rating_id' => $rating->id,
                            'image_url' => $dropboxUrl
                        ]);
                    } else {
                        throw new \Exception('Image upload to Dropbox failed');
                    }
                }
            }

            // Handle facilities
            if ($request->has('facilities')) {
                $facilityIds = json_decode($validated['facilities'], true);
                if (!empty($facilityIds)) {
                    $rating->confirmedFacilities()->attach($facilityIds);

                    foreach ($facilityIds as $facilityId) {
                        if (!$place->facilities()->where('facility_id', $facilityId)->exists()) {
                            $place->facilities()->attach($facilityId);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'rating' => $rating->load(['images', 'confirmedFacilities']),
                'message' => 'Ulasan berhasil ditambahkan'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all reviews for a place
     *
     * @param int $placeId
     * @return \Illuminate\Http\Response
     */
    public function getPlaceReviews($placeId)
    {
        $reviews = Rating::with(['user', 'images', 'confirmedFacilities'])
            ->where('place_id', $placeId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reviews);
    }

    /**
     * Get all reviews by the authenticated user
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserReviews()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $reviews = Rating::with(['place', 'place.facilities'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($review) {
                return [
                    'place_id' => $review->place->id,
                    'title' => $review->place->name,
                    'address' => $review->place->address,
                    'rating' => $review->place->ratings()->avg('rating') ?? 0,
                    'facilities' => $review->place->facilities->pluck('id')->toArray(),
                ];
            });

        return response()->json($reviews);
    }
}