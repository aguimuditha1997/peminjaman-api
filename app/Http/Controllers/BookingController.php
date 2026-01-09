<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Http\Requests\BookingStoreRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

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

            // 1. Ambil waktu mulai dan selesai
            $start = Carbon::parse($validated['start_time']);
            $end = Carbon::parse($validated['end_time']);

            // 2. Logika Otomatis: Cek apakah rentang waktu menyentuh Weekend
            $isWeekend = false;

            // Kita buat perulangan per hari dari tanggal mulai sampai selesai
            $period = CarbonPeriod::create($start->toDateString(), $end->toDateString());

            foreach ($period as $date) {
                if ($date->isWeekend()) {
                    $isWeekend = true;
                    break; // Jika sudah ketemu 1 hari weekend, langsung stop loop
                }
            }

            // 3. Set type_week dan status otomatis
            if ($isWeekend) {
                $validated['type_week'] = 'weekend';
                $validated['status_sdm'] = 'pending'; // Weekend butuh persetujuan SDM
            } else {
                $validated['type_week'] = 'weekday';
                $validated['status_sdm'] = 'approve'; // Weekday mungkin otomatis approve (tergantung kebijakanmu)
            }

            // 4. Simpan ke database
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
}
