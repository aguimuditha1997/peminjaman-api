<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Http\Requests\BookingStoreRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Exception;

class BookingController extends Controller
{
    public function index()
    {
        try {
            $bookings = Booking::with('room')->get();

            return response()->json([
                'success' => true,
                'message' => 'Bookings retrieved successfully.',
                'data'    => $bookings
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve bookings.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function store(BookingStoreRequest $request)
    {
        try {
            $validated = $request->validated();

            $start = Carbon::parse($validated['start_time']);
            $end = Carbon::parse($validated['end_time']);
            $roomId = $validated['room_id']; 

            // 2. LOGIKA CEK BENTROK (OVERLAP)
            // Mengecek apakah ada booking yang sudah APPROVE di waktu yang sama
            $isBooked = Booking::where('room_id', $roomId)
                ->where('status_sdm', 'approve')
                ->where('status_dpt', 'approve')
                ->where(function ($query) use ($start, $end) {
                    // Rumus Overlap: (Mulai_Baru < Selesai_Lama) DAN (Selesai_Baru > Mulai_Lama)
                    $query->where('start_time', '<', $end)
                          ->where('end_time', '>', $start);
                })
                ->exists();

            if ($isBooked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maaf, ruangan sudah disetujui untuk peminjam lain di rentang waktu tersebut.'
                ], 422);
            }

            // 3. Logika Otomatis: Cek Weekend
            $isWeekend = false;
            $period = CarbonPeriod::create($start->toDateString(), $end->toDateString());
            foreach ($period as $date) {
                if ($date->isWeekend()) {
                    $isWeekend = true;
                    break;
                }
            }

            // 4. Set Type Week dan Status Awal
            if ($isWeekend) {
                $validated['type_week'] = 'weekend';
                $validated['status_sdm'] = 'pending';
                $validated['status_dpt'] = 'pending';
            } else {
                $validated['type_week'] = 'weekday';
                $validated['status_sdm'] = 'approve'; // Otomatis approve SDM jika weekday
                $validated['status_dpt'] = 'pending';
            }

            // 5. Generate KODE UNIK Otomatis
            do {
                $code = 'BK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
            } while (Booking::where('code', $code)->exists());

            $validated['code'] = $code;

            // 6. Simpan ke Database
            $booking = Booking::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Booking successfully created.',
                'data'    => $booking
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $booking = Booking::with('room')->find($id);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking details retrieved successfully.',
                'data'    => $booking
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve booking details.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $code)
    {
        try {
            // 1. Cari booking berdasarkan kode
            $booking = Booking::where('code', $code)->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => "Booking dengan kode $code tidak ditemukan."
                ], 404);
            }

            $user = $request->user();
            $dataToUpdate = [];

            // 2. Definisi Aturan Validasi Umum (Start, End, Room, Week)
            $commonRules = [
                'start_date' => 'sometimes|date',
                'end_date'   => 'sometimes|date|after_or_equal:start_date',
                'nameroom'   => 'sometimes|string',
                'type_week'  => 'sometimes|string|in:weekday,weekend',
            ];

            // 3. Logika Berdasarkan Role
            if ($user->role === 'sdm') {
                // Validasi: Gabungkan aturan umum + status_sdm
                $validator = Validator::make($request->all(), array_merge($commonRules, [
                    'status_sdm' => 'sometimes|in:pending,approve,rejected',
                ]));

                if ($validator->fails()) {
                    return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
                }

                // Proteksi: SDM tidak boleh mengubah status_dpt
                if ($request->has('status_dpt')) {
                    return response()->json(['message' => 'Role SDM tidak diizinkan mengubah status DPT.'], 403);
                }

                $dataToUpdate = $request->only(['start_date', 'end_date', 'nameroom', 'type_week', 'status_sdm']);

            } elseif ($user->role === 'dpt') {
                // Validasi: Gabungkan aturan umum + status_dpt
                $validator = Validator::make($request->all(), array_merge($commonRules, [
                    'status_dpt' => 'sometimes|in:pending,approve,rejected',
                ]));

                if ($validator->fails()) {
                    return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
                }

                // Proteksi: DPT tidak boleh mengubah status_sdm
                if ($request->has('status_sdm')) {
                    return response()->json(['message' => 'Role DPT tidak diizinkan mengubah status SDM.'], 403);
                }

                // DPT sekarang diizinkan merubah jadwal, ruangan, type_week, dan status_dpt
                $dataToUpdate = $request->only(['start_date', 'end_date', 'nameroom', 'type_week', 'status_dpt']);

            } else {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }

            // 4. Cek jika tidak ada data dikirim
            if (empty($dataToUpdate)) {
                return response()->json(['success' => false, 'message' => 'Tidak ada data untuk diperbarui.'], 400);
            }

            // 5. Eksekusi Update
            $booking->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => "Booking $code berhasil diperbarui oleh " . strtoupper($user->role),
                'data' => $booking
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
