<!-- resources/views/employees/email-change-notification.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Perubahan Email Akun Karyawan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }
        .header {
            background-color: #f7f7f7;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .info-box {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .important {
            color: #d9534f;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            text-align: center;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Perubahan Email Akun Karyawan</h2>
        </div>
        
        <p>Halo {{ $user->name }},</p>
        
        <p>Alamat email untuk akun karyawan Anda telah diubah oleh owner.</p>
        
        <div class="info-box">
            <p><strong>Email Lama:</strong> {{ $oldEmail }}</p>
            <p><strong>Email Baru:</strong> {{ $newEmail }}</p>
        </div>
        
        <p>Jika Anda tidak melakukan perubahan ini atau merasa ada kesalahan, silakan segera hubungi owner.</p>
        
        <p>Untuk masuk ke sistem, silakan kunjungi: <a href="{{ url('/login') }}">{{ url('/login') }}</a></p>
        
        <div class="footer">
            <p>Email ini dikirim secara otomatis. Mohon tidak membalas email ini.</p>
            <p>&copy; {{ date('Y') }} Toko Kacamata Sidi Pingai Bukittinggi</p>
        </div>
    </div>
</body>
</html>