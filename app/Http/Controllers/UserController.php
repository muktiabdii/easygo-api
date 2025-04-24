<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\ResetOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    // method register
    public function register(Request $request){

        // validasi request
        $request->validate([
            'name' => 'required|string|max:255',
            'number' => 'required|string|max:15',
            'country' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-zA-Z])(?=.*[0-9]).+$/',
        ], [
            
            // error messages
            'password.regex' => 'Password harus mengandung minimal satu huruf dan satu angka.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
        ]);

        // create user
        $user = User::create([
            'name' => $request->name,
            'number' => $request->number,
            'country' => $request->country,
            'province' => $request->province,
            'city' => $request->city,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // return response
        return response()->json([
            'message' => 'Akun berhasil terdaftar'
        ], 201);
    }

    // method login
    public function login(Request $request)
    {
        // validasi request
        $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        // check email and password
        if (auth()->attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = auth()->user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token,
            ], 200);
        }

        return response()->json([
            'message' => 'Email atau password salah'
        ], 401);
    }

    // method send reset link email
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $otp = rand(100000, 999999);

        // Ambil user yang sesuai
        $user = User::where('email', $request->email)->first();

        // Simpan OTP dan waktu kadaluarsa ke database
        $user->reset_otp = $otp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();

        // Kirim email OTP
        Mail::to($user->email)->send(new ResetOtpMail($user, $otp));

        return response()->json([
            'message' => 'OTP reset password telah dikirim ke email kamu.'
        ]);
    }


    // method validate otp
    public function validateOtp(Request $request) 
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required'
        ]);
    
        $user = User::where('email', $request->email)->first();
    
        if (!$user || $user->reset_otp !== $request->otp || now()->gt($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP tidak valid atau sudah kedaluwarsa'], 422);
        }
    
        return response()->json(['message' => 'OTP valid']);
    }

    // method reset password
    public function resetPassword(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);
    
        $user = User::where('email', $request->email)->first();
    
        if (!$user || $user->reset_otp !== $request->otp || now()->gt($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP tidak valid atau sudah kedaluwarsa'], 422);
        }
    
        $user->password = bcrypt($request->password);
        $user->reset_otp = null;
        $user->otp_expires_at = null;
        $user->save();
    
        return response()->json(['message' => 'Password berhasil direset']);
    }
      
}
