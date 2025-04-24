<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h2>Halo {{ $user->name }},</h2>
    <p>Kami menerima permintaan untuk reset password kamu.</p>
    <p>Berikut adalah OTP-mu: <strong>{{ $otp }}</strong></p>
    <p>OTP ini berlaku selama 10 menit.</p>
    <br>
    <p>Terima kasih!</p>
</body>
</html>
