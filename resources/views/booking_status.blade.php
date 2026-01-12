<!DOCTYPE html>
<html>
<head>
    <title>Update Status Peminjaman</title>
</head>
<body>
    <h2>Halo, {{ $booking->user_name }}</h2>
    <p>Pemberitahuan mengenai pengajuan peminjaman ruangan Anda:</p>
    
    <table border="0">
        <tr><td><strong>Kode Booking</strong></td><td>: {{ $booking->code }}</td></tr>
        <tr><td><strong>Ruangan</strong></td><td>: {{ $booking->room->name ?? $booking->room_id }}</td></tr>
        <tr><td><strong>Waktu</strong></td><td>: {{ $booking->start_time }} s/d {{ $booking->end_time }}</td></tr>
        <tr><td><strong>Status SDM</strong></td><td>: {{ strtoupper($booking->status_sdm) }}</td></tr>
        <tr><td><strong>Status DPT</strong></td><td>: {{ strtoupper($booking->status_dpt) }}</td></tr>
    </table>

    <p>
        @if($booking->status_sdm === 'approve' && $booking->status_dpt === 'approve')
            <span style="color: green;"><strong>Selamat! Pengajuan Anda telah disetujui sepenuhnya.</strong></span>
        @elseif($booking->status_sdm === 'rejected' || $booking->status_dpt === 'rejected')
            <span style="color: red;"><strong>Mohon maaf, pengajuan Anda ditolak.</strong></span>
        @else
            <span>Pengajuan Anda masih dalam proses verifikasi.</span>
        @endif
    </p>

    <p>Terima kasih,<br>UPT Primakara University</p>
</body>
</html>