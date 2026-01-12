<?php

namespace App\Http\Controllers;

use App\Mail\BookingStatusMail;
use Illuminate\Support\Facades\Mail;
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
            // 1. Cari booking berdasarkan code
            $booking = Booking::where('code', $code)->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => "Booking dengan kode $code tidak ditemukan."
                ], 404);
            }

            $user = $request->user();

            // 2. Validasi Input
            $validator = Validator::make($request->all(), [
                'room_id'    => 'sometimes|exists:rooms,id',
                'start_time' => 'sometimes|date',
                'end_time'   => 'sometimes|date|after_or_equal:start_time',
                'status'     => 'sometimes|in:approve,rejected,reject,pending',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            // 3. Update Detail
            if ($request->has('room_id')) {
                $booking->room_id = $request->room_id;
            }
            if ($request->has('start_time')) {
                $booking->start_time = str_replace('T', ' ', $request->start_time);
            }
            if ($request->has('end_time')) {
                $booking->end_time = str_replace('T', ' ', $request->end_time);
            }

            // 4. Update Status Berdasarkan Role
            $statusValue = $request->status;
            if ($statusValue === 'reject') {
                $statusValue = 'rejected';
            }

            if ($user->role === 'sdm') {
                if ($request->has('status')) {
                    $booking->status_sdm = $statusValue;
                }
            } elseif ($user->role === 'dpt') {
                if ($request->has('status')) {
                    $booking->status_dpt = $statusValue;
                }
            } elseif ($user->role === 'admin') {
                if ($request->has('status')) {
                    $booking->status_sdm = $statusValue;
                    $booking->status_dpt = $statusValue;
                }
            } else {
                return response()->json(['success' => false, 'message' => "Role $user->role tidak memiliki akses untuk update status."], 403);
            }

            // 5. Simpan Perubahan
            $booking->save();

            // 6. Logic Pengiriman Email
            if ($booking->email) {
                $isRejected = ($booking->status_sdm === 'rejected' || $booking->status_dpt === 'rejected');
                $isFinalApproved = ($booking->status_sdm === 'approve' && $booking->status_dpt === 'approve');

                if ($isRejected || $isFinalApproved) {
                    try {
                        Mail::to($booking->email)->send(new BookingStatusMail($booking));
                    } catch (\Exception $e) {
                        \log::error("Gagal mengirim email ke {$booking->email}: " . $e->getMessage());
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Booking $code berhasil diperbarui oleh " . strtoupper($user->role) . " dan email notifikasi diproses.",
                'data' => $booking->fresh(['room'])
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

    

}