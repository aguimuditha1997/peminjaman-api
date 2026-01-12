<!DOCTYPE html>
<html>

<head>
    <title>Update Status Peminjaman Ruangan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            width: 80%;
            margin: 20px auto;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
        }

        .header {
            background-color: #f4f4f4;
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        .content {
            margin-top: 20px;
        }

        .footer {
            margin-top: 30px;
            font-size: 0.8em;
            color: #777;
            text-align: center;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .approve {
            background-color: #d4edda;
            color: #155724;
        }

        .rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .pending {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Notifikasi Status Peminjaman Ruangan</h2>
        </div>
        <div class="content">
            <p>Halo, <strong>{{ $booking->name }}</strong></p>
            <p>Status peminjaman ruangan Anda dengan kode <strong>{{ $booking->code }}</strong> telah diperbarui.</p>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Ruangan:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $booking->room->nameroom ?? 'N/A' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Waktu Mulai:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                        {{ \Carbon\Carbon::parse($booking->start_time)->format('d M Y H:i') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Waktu Selesai:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                        {{ \Carbon\Carbon::parse($booking->end_time)->format('d M Y H:i') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Status DPT:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                        <span class="status-badge {{ $booking->status_dpt }}">{{ $booking->status_dpt }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Status SDM:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                        <span class="status-badge {{ $booking->status_sdm }}">{{ $booking->status_sdm }}</span>
                    </td>
                </tr>
            </table>

            @if($booking->status_sdm === 'rejected' || $booking->status_dpt === 'rejected')
                <p style="margin-top: 20px; color: #721c24;">Mohon maaf, peminjaman Anda telah ditolak.</p>
            @elseif($booking->status_sdm === 'approve' && $booking->status_dpt === 'approve')
                <p style="margin-top: 20px; color: #155724;">Selamat! Peminjaman Anda telah disetujui oleh semua pihak.</p>
            @else
                <p style="margin-top: 20px;">Peminjaman Anda masih dalam proses peninjauan.</p>
            @endif
        </div>
        <div class="footer">
            <p>Ini adalah email otomatis, mohon tidak membalas.</p>
        </div>
    </div>
</body>

</html>