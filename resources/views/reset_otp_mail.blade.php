<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>
<body style="margin: 0; padding: 40px 0; background-color: #f4f4f5; font-family: Arial, sans-serif;">
    <div style="max-width: 500px; margin: auto; background-color: #ffffff; padding: 40px; border-radius: 10px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">

        {{-- Logo --}}
        <img src="{{asset('images/logo_easygo.png')}}" alt="Logo" style="max-width: 100px; margin-bottom: 20px;">

        {{-- Header --}}
        <h2 style="margin: 0 0 10px; font-size: 24px; color: #111827;">
            Reset Password Request
        </h2>

        {{-- Sub Text --}}
        <p style="color: #4b5563; font-size: 16px; margin-bottom: 30px;">
            Halo {{ $user->name }},<br>
            Kami menerima permintaan untuk mereset password akunmu.<br>
            Gunakan kode OTP berikut untuk melanjutkan.
        </p>

        {{-- OTP Code Display --}}
        <div style="text-align: center; margin: 30px 0;">
            @foreach(str_split($otp) as $digit)
                <span style="display: inline-block; font-size: 28px; font-weight: bold; width: 50px; height: 50px; line-height: 50px; border-radius: 10px; background-color: #f3f4f6; color: #111827; margin: 0 6px;">
                    {{ $digit }}
                </span>
            @endforeach
        </div>


        {{-- Info --}}
        <p style="color: #6b7280; font-size: 14px;">
            Kode ini berlaku selama 5 menit dan hanya dapat digunakan untuk mereset password.
        </p>

        {{-- Footer --}}
        <p style="color: #9ca3af; font-size: 12px; margin-top: 30px;">
            Jika kamu tidak meminta reset password, abaikan saja email ini.
        </p>

    </div>
</body>
</html>
