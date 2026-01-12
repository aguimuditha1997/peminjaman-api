<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoomStoreRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function index()
    {
        try {
            $rooms = Room::all();
            
            return response()->json([
                'success' => true,
                'message' => 'Rooms retrieved successfully.',
                'data'    => $rooms->map(function($room) {
                    return [
                        'id'        => $room->id,
                        'nameroom'  => $room->nameroom,
                        'capacity'  => $room->capacity,
                        'detail'    => $room->detail,
                        'images'    => array_map(fn($path) => asset('storage/' . $path), $room->images ?? []),
                        'created_at'=> $room->created_at->format('Y-m-d H:i:s')
                    ];
                })
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rooms.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $room = Room::find($id);

            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Room details retrieved successfully.',
                'data'    => [
                    'id'        => $room->id,
                    'nameroom'  => $room->nameroom,
                    'capacity'  => $room->capacity,
                    'detail'    => $room->detail,
                    'images'    => array_map(fn($path) => asset('storage/' . $path), $room->images ?? []),
                    'created_at'=> $room->created_at->format('Y-m-d H:i:s')
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve room details.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function store(RoomStoreRequest $request)
{
    try {
            $validated = $request->validated();

            return DB::transaction(function () use ($request, $validated) {
                
                // 1. Logika Upload Multiple Images
                $imagePaths = [];
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $image) {
                        $path = $image->store('rooms', 'public');
                        $imagePaths[] = $path;
                    }
                }

                // 2. Simpan ke database dengan slug otomatis
                $room = Room::create([
                    'nameroom' => $validated['nameroom'],
                    'slug'     => Str::slug($validated['nameroom']), // Membuat slug-otomatis-seperti-ini
                    'capacity' => $validated['capacity'],
                    'detail'   => $validated['detail'],
                    'images'   => $imagePaths,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Room successfully created.',
                    'data'    => [
                        'id'        => $room->id,
                        'nameroom'  => $room->nameroom,
                        'slug'      => $room->slug, // Tampilkan slug di response
                        'capacity'  => $room->capacity,
                        'detail'    => $room->detail,
                        'images'    => array_map(fn($path) => asset('storage/' . $path), $imagePaths),
                        'created_at'=> $room->created_at->format('Y-m-d H:i:s')
                    ]
                ], 201);
            });

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create room.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

public function update(Request $request, $slug) // 1. Parameter dirubah menjadi $slug
{
    try {
        // 2. Cari data berdasarkan slug
        $room = Room::where('slug', $slug)->first();

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => "Room with slug '$slug' not found."
            ], 404);
        }

        // 3. Validasi Input
        $validator = Validator::make($request->all(), [
            'nameroom' => 'required|string|max:255',
            'capacity' => 'required|integer',
            'detail'   => 'required',
            'images'   => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // 4. Persiapkan data update (termasuk slug baru dari nameroom baru)
        $dataToUpdate = [
            'nameroom' => $request->nameroom,
            'slug'     => Str::slug($request->nameroom), // Update slug otomatis
            'capacity' => $request->capacity,
            'detail'   => $request->detail,
        ];

        // 5. Handle Gambar jika ada upload baru
        if ($request->hasFile('images')) {
            // Hapus gambar lama dari storage
            if ($room->images && is_array($room->images)) {
                foreach ($room->images as $oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Upload gambar baru
            $newImages = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('rooms', 'public');
                $newImages[] = $path;
            }
            
            $dataToUpdate['images'] = $newImages;
        }

        // 6. Eksekusi Update
        $room->update($dataToUpdate);

        // Tambahkan URL storage untuk response data agar rapi
        $room['images'] = array_map(fn($path) => asset('storage/' . $path), $room->images ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Room updated successfully.',
            'data'    => $room 
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error'   => $e->getMessage()
        ], 500);
    }
}
    // 2. DELETE RUANGAN BERDASARKAN NAMA
    public function destroy($slug)
    {
        try {
            $room = Room::where('slug', $slug)->first();

            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => "Room '$slug' not found."
                ], 404);
            }

            // Hapus file fisik gambar di storage
            if ($room->images) {
                foreach ($room->images as $image) {
                    $path = str_replace(url('storage/'), '', $image);
                    Storage::disk('public')->delete($path);
                }
            }

            // Hapus data dari database
            $room->delete();

            return response()->json([
                'success' => true,
                'message' => "Room '$slug' and its images deleted successfully."
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete room.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}