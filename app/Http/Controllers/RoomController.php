<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoomStoreRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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
            // 1. Ambil data yang sudah tervalidasi
            $validated = $request->validated();

            // 2. Gunakan DB Transaction untuk memastikan integritas data
            return DB::transaction(function () use ($request, $validated) {
                
                // 3. Logika Upload Multiple Images
                $imagePaths = [];
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $image) {
                        // Simpan file ke storage/app/public/rooms
                        $path = $image->store('rooms', 'public');
                        $imagePaths[] = $path;
                    }
                }

                // 4. Simpan ke database
                $room = Room::create([
                    'nameroom' => $validated['nameroom'],
                    'capacity' => $validated['capacity'],
                    'detail'   => $validated['detail'],
                    'images'   => $imagePaths, // Pastikan $casts array di Model Room
                ]);

                // 5. Return JSON Success Response
                return response()->json([
                    'success' => true,
                    'message' => 'Room successfully created.',
                    'data'    => [
                        'id'        => $room->id,
                        'nameroom'  => $room->nameroom,
                        'capacity'  => $room->capacity,
                        'detail'    => $room->detail,
                        'images'    => array_map(fn($path) => asset('storage/' . $path), $imagePaths),
                        'created_at'=> $room->created_at->format('Y-m-d H:i:s')
                    ]
                ], 201);
            });

        } catch (\Exception $e) {
            // Return JSON Error Response jika terjadi kegagalan
            return response()->json([
                'success' => false,
                'message' => 'Failed to create room.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
