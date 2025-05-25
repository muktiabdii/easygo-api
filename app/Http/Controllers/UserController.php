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

    // Register method
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'number' => 'required|string|max:15',
            'country' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-zA-Z])(?=.*[0-9]).+$/',
        ], [
            'password.regex' => 'Password harus mengandung minimal satu huruf dan satu angka.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'number' => $request->number,
            'country' => $request->country,
            'province' => $request->province,
            'city' => $request->city,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'user', // Set default role
        ]);

        return response()->json([
            'message' => 'Akun berhasil terdaftar'
        ], 201);
    }

    // Login method
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if (auth()->attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = auth()->user();
            if ($user->role !== 'user') {
                return response()->json([
                    'message' => 'Akun admin tidak dapat login di halaman ini.'
                ], 403);
            }
            
            $token = $user->createToken('auth_token')->plainTextToken;

            $cookie = cookie(
                'auth_token',
                $token,
                60 * 24,
                null,
                null,
                true,
                true,
                false,
                'strict'
            );

            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
            ], 200)->withCookie($cookie);
        }

        return response()->json([
            'message' => 'Email atau password salah'
        ], 401);
    }

    // Admin login method
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if (auth()->attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = auth()->user();
            if ($user->role !== 'admin') {
                return response()->json([
                    'message' => 'Akses ditolak. Hanya admin yang diizinkan.'
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $cookie = cookie(
                'auth_token',
                $token,
                60 * 24,
                null,
                null,
                true,
                true,
                false,
                'strict'
            );

            return response()->json([
                'message' => 'Login admin berhasil',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
            ], 200)->withCookie($cookie);
        }

        return response()->json([
            'message' => 'Email atau password salah'
        ], 401);
    }

    // Logout method
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        $cookie = Cookie::forget('auth_token');

        return response()->json([
            'message' => 'Logged out successfully'
        ])->withCookie($cookie);
    }

    // Validate token method
    public function validateToken(Request $request)
    {
        return response()->json([
            'valid' => true,
            'user' => $request->user()
        ]);
    }

    // Send reset link email method
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $otp = rand(100000, 999999);
        $user = User::where('email', $request->email)->first();
        $user->reset_otp = $otp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();

        Mail::to($user->email)->send(new ResetOtpMail($user, $otp));

        return response()->json([
            'message' => 'OTP reset password telah dikirim ke email kamu.'
        ]);
    }

    // Validate OTP method
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

    // Reset password method
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

    // Update user method
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'number' => 'required|string|max:15',
            'country' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
        ], [
            'name.required' => 'Nama pengguna tidak boleh kosong.',
            'number.required' => 'Nomor telepon tidak boleh kosong.',
        ]);

        try {
            $user = $request->user();
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

            $user->name = $request->name;
            $user->number = $request->number;
            $user->country = $request->country;
            $user->province = $request->province;
            $user->city = $request->city;
            $user->email = $request->email;
            $user->save();

            return response()->json([
                'message' => 'Profil berhasil diperbarui.',
                'success' => true,
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui profil. ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    // Update profile image method
    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|file|image|mimes:jpeg,png,jpg|max:5120',
        ], [
            'profile_image.required' => 'Gambar profil harus diunggah.',
            'profile_image.image' => 'File harus berupa gambar.',
            'profile_image.mimes' => 'Format gambar tidak valid. Gunakan jpeg, png, atau jpg.',
            'profile_image.max' => 'Ukuran gambar tidak boleh lebih dari 5MB.',
        ]);

        try {
            $user = $request->user();
            $profileImage = $request->file('profile_image');
            $dropboxUrl = $this->dropboxService->uploadFile($profileImage, '/profile-images');

            if (!$dropboxUrl) {
                return response()->json([
                    'message' => 'Gagal mengunggah gambar profil ke server.',
                    'success' => false
                ], 500);
            }

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
}