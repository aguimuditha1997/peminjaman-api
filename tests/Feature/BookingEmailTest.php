<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Mail\BookingStatusMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_email_is_sent_when_both_statuses_are_approved()
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        $room = Room::create([
            'nameroom' => 'Meeting Room 1',
            'slug' => 'meeting-room-1',
            'capacity' => 10,
            'detail' => 'Standard meeting room',
            'images' => []
        ]);

        $booking = Booking::create([
            'name' => 'John Doe',
            'organization' => 'Company A',
            'email' => 'john@example.com',
            'code' => 'BK-0001',
            'status_dpt' => 'pending',
            'status_sdm' => 'pending',
            'type_week' => 'weekday',
            'no_whatsapp' => '08123456789',
            'room_id' => $room->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_time' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
            'note' => 'Test note',
            'purpose' => 'Meeting'
        ]);

        $response = $this->actingAs($admin)
            ->putJson("/api/bookings/{$booking->code}", [
                'status' => 'approve'
            ]);

        $response->assertStatus(200);

        Mail::assertSent(BookingStatusMail::class, function ($mail) use ($booking) {
            return $mail->hasTo('john@example.com') && $mail->booking->code === $booking->code;
        });
    }

    public function test_email_is_sent_when_any_status_is_rejected()
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        $room = Room::create([
            'nameroom' => 'Meeting Room 1',
            'slug' => 'meeting-room-1',
            'capacity' => 10,
            'detail' => 'Standard meeting room',
            'images' => []
        ]);

        $booking = Booking::create([
            'name' => 'John Doe',
            'organization' => 'Company A',
            'email' => 'john@example.com',
            'code' => 'BK-0002',
            'status_dpt' => 'pending',
            'status_sdm' => 'pending',
            'type_week' => 'weekday',
            'no_whatsapp' => '08123456789',
            'room_id' => $room->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_time' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
            'note' => 'Test note',
            'purpose' => 'Meeting'
        ]);

        $response = $this->actingAs($admin)
            ->putJson("/api/bookings/{$booking->code}", [
                'status' => 'rejected'
            ]);

        $response->assertStatus(200);

        Mail::assertSent(BookingStatusMail::class, function ($mail) use ($booking) {
            return $mail->hasTo('john@example.com');
        });
    }

    public function test_email_is_not_sent_when_not_terminal_state()
    {
        $sdm_user = User::create([
            'name' => 'SDM User',
            'email' => 'sdm@example.com',
            'password' => bcrypt('password'),
            'role' => 'sdm'
        ]);

        $room = Room::create([
            'nameroom' => 'Meeting Room 1',
            'slug' => 'meeting-room-1',
            'capacity' => 10,
            'detail' => 'Standard meeting room',
            'images' => []
        ]);

        $booking = Booking::create([
            'name' => 'John Doe',
            'organization' => 'Company A',
            'email' => 'john@example.com',
            'code' => 'BK-0003',
            'status_dpt' => 'pending',
            'status_sdm' => 'pending',
            'type_week' => 'weekday',
            'no_whatsapp' => '08123456789',
            'room_id' => $room->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_time' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
            'note' => 'Test note',
            'purpose' => 'Meeting'
        ]);

        // SDM approves, but DPT is still pending
        $response = $this->actingAs($sdm_user)
            ->putJson("/api/bookings/{$booking->code}", [
                'status' => 'approve'
            ]);

        $response->assertStatus(200);

        // In the current logic, if SDM approves, status_sdm becomes 'approve'.
        // If status_dpt is still 'pending', it's not terminal unless we consider any status change as terminal for email?
        // The implementation logic is:
        // $isApproved = ($booking->status_sdm === 'approve' && $booking->status_dpt === 'approve');
        // $isRejected = ($booking->status_sdm === 'rejected' || $booking->status_dpt === 'rejected');

        // So here it should NOT send email.
        Mail::assertNothingSent();
    }
}
