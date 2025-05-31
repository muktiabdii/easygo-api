<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\ResetOtpMail;
use App\Services\DropboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cookie;
use Laravel\Sanctum\PersonalAccessToken;

class UserController extends Controller
{
    protected $dropboxService;

    public function __construct(DropboxService $dropboxService)
    {
        $this->dropboxService = $dropboxService;
    }

    // method register
    public function register(Request $request)
    {
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

    // method login with secure cookies
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

            // Store token in secure HTTP-only cookie
            $cookie = cookie(
                'auth_token',
                $token,
                60 * 24, // 1 day
                null,
                null, // path, domain
                true,       // secure (HTTPS only)
                true,       // httpOnly
                false,      // raw
                'strict'
            );  // same site policy

            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token, // Still include token in response for initial setup
            ], 200)->withCookie($cookie);
        }

        return response()->json([
            'message' => 'Email atau password salah'
        ], 401);
    }

    // method logout
    public function logout(Request $request)
    {
        // Revoke the token from the database
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        // Clear the auth cookie
        $cookie = Cookie::forget('auth_token');

        return response()->json([
            'message' => 'Logged out successfully'
        ])->withCookie($cookie);
    }

    // Token validation endpoint
    public function validateToken(Request $request)
    {
        // User is already authenticated via middleware if they reach here
        return response()->json([
            'valid' => true,
            'user' => $request->user()
        ]);
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
    public function resetPassword(Request $request)
    {
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

    public function update(Request $request)
    {
        // Validate the request data
        $request->validate([
            'name' => 'required|string|max:255',
            'number' => 'required|string|max:15',
            'country' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
        ], [
            // error messages
            'name.required' => 'Nama pengguna tidak boleh kosong.',
            'number.required' => 'Nomor telepon tidak boleh kosong.',
        ]);

        try {
            // Get the authenticated user
            $user = $request->user();

            // Check if the email is being changed and if it's already taken
            if ($user->email !== $request->email) {

                $existingUser = User::where('email', $request->email)
                    ->where('id', '!=', $user->id)
                    ->first();

                if ($existingUser) {
                    return response()->json([
                        'message' => 'Email sudah digunakan oleh pengguna lain.',
                        'success' => false
                    ], 422);
                }
            }

            // Update user information
            $user->name = $request->name;
            $user->number = $request->number;
            $user->country = $request->country;
            $user->province = $request->province;
            $user->city = $request->city;
            $user->email = $request->email;
            $user->save();

            // Return success response
            return response()->json([
                'message' => 'Profil berhasil diperbarui.',
                'success' => true,
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            // Return error response
            return response()->json([
                'message' => 'Gagal memperbarui profil. ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function updateProfileImage(Request $request)
    {
        // Validate image
        $request->validate([
            'profile_image' => 'required|file|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
        ], [
            'profile_image.required' => 'Gambar profil harus diunggah.',
            'profile_image.image' => 'File harus berupa gambar.',
            'profile_image.mimes' => 'Format gambar tidak valid. Gunakan jpeg, png, atau jpg.',
            'profile_image.max' => 'Ukuran gambar tidak boleh lebih dari 5MB.',
        ]);

        try {
            // Get the authenticated user
            $user = $request->user();

            // Upload to Dropbox
            $profileImage = $request->file('profile_image');
            $dropboxUrl = $this->dropboxService->uploadFile($profileImage, '/profile-images');

            if (!$dropboxUrl) {
                return response()->json([
                    'message' => 'Gagal mengunggah gambar profil ke server.',
                    'success' => false
                ], 500);
            }

            // Update user profile image URL
            $user->profile_image = $dropboxUrl;
            $user->save();

            return response()->json([
                'message' => 'Gambar profil berhasil diperbarui.',
                'success' => true,
                'profile_image' => $dropboxUrl,
                'user' => $user
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui gambar profil. ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getAuthenticatedUserId(Request $request)
    {
        // Ambil token dari Bearer Authorization header
        $token = $request->bearerToken();

        // Jika tidak ada Bearer Token, coba ambil dari cookie 'auth_token'
        if (!$token) {
            $token = $request->cookie('auth_token');
        }

        if (!$token) {
            return response()->json(['message' => 'Token tidak ditemukan'], 401);
        }

        // Temukan token di database dan hubungkan ke user
        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json(['message' => 'Token tidak valid'], 401);
        }

        // Ambil user dari token
        $user = $accessToken->tokenable;

        return response()->json([
            'user_id' => $user->id,
            'message' => 'User berhasil diidentifikasi'
        ], 200);
    }
}