<!-- resources/views/employees/new-employee-credentials.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Informasi Akun Karyawan</title>
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
        .credential-box {
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
            <h2>Informasi Akun Karyawan</h2>
        </div>
        
        <p>Halo {{ $user->name }},</p>
        
        <p>Selamat bergabung! Akun karyawan Anda telah dibuat. Berikut adalah informasi login Anda:</p>
        
        <div class="credential-box">
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Password:</strong> {{ $password }}</p>
        </div>
        
        <p>Untuk masuk ke sistem, silakan kunjungi: <a href="{{ url('/login') }}">{{ url('/login') }}</a></p>
        
        <p>Jika Anda mengalami kesulitan dalam mengakses akun, silakan hubungi administrator.</p>
        
        <div class="footer">
            <p>Email ini dikirim secara otomatis. Mohon tidak membalas email ini.</p>
            <p>&copy; {{ date('Y') }} PT. Nama Perusahaan</p>
        </div>
    </div>
</body>
</html>