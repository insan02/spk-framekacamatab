<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4a76a8;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Pemberitahuan Perubahan Email</h2>
        </div>
        
        <div class="content">
            <p>Yth. <strong>{{ $user->name }}</strong>,</p>
            
            <p>Kami ingin memberitahukan bahwa alamat email untuk akun Anda telah diubah.</p>
            
            <div class="alert">
                <p><strong>Detail perubahan:</strong></p>
                <p>Email lama: <strong>{{ $oldEmail }}</strong></p>
                <p>Email baru: <strong>{{ $newEmail }}</strong></p>
            </div>
            
            <p>Jika Anda tidak melakukan perubahan ini, silakan abaikan saja.</p>
            
            <p>Terima kasih,<br>
            Toko Kacamata Sidi Pingai Bukittinggi</p>
        </div>
        
        <div class="footer">
            <p>Email ini dikirim secara otomatis, mohon untuk tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>