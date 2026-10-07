<?php

// app/Http/Controllers/Api/AuthController.php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'nullable|email|unique:users|required_without:phone_number',
            'phone_number' => 'nullable|string|unique:users|required_without:email',
            'password'     => 'required|string|min:8|confirmed',
        ], [
            'email.required_without'        => 'Alamat email atau nomor WhatsApp harus diisi salah satu.',
            'phone_number.required_without' => 'Alamat email atau nomor WhatsApp harus diisi salah satu.',
            'email.unique'                  => 'Email ini sudah terdaftar.',
            'phone_number.unique'           => 'Nomor WhatsApp ini sudah terdaftar.',
            'password.confirmed'            => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'password'     => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran berhasil',
            'data'    => [
                'user'  => $user,
                'token' => $user->createToken('auth_token')->plainTextToken,
            ]
        ], 201);
    }

    public function loginEmail(Request $request)
    {
        $credentials = $request->validate([
            'identity' => 'required|string',
            'password' => 'required|string',
        ]);

        $fieldType = filter_var($credentials['identity'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user      = User::where($fieldType, $credentials['identity'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kombinasi email/username atau kata sandi tidak cocok.'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Masuk berhasil',
            'data'    => [
                'user'  => $user,
                'token' => $user->createToken('auth_token')->plainTextToken,
            ]
        ]);
    }

    public function requestOtpWa(Request $request)
    {
        $request->validate(['phone_number' => 'required|string']);

        $user = User::where('phone_number', $request->phone_number)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Nomor WhatsApp belum terdaftar.'], 404);
        }

        $otpCode = rand(100000, 999999);

        Otp::updateOrCreate(
            ['identifier' => $request->phone_number, 'type' => 'login_whatsapp'],
            [
                'otp_code'   => Hash::make($otpCode),
                'attempts'   => 0,
                'expires_at' => now()->addMinutes(5),
            ]
        );

        // TODO: WhatsAppGateway::send($request->phone_number, "Kode OTP: $otpCode");

        return response()->json(['success' => true, 'message' => 'Kode OTP berhasil dikirimkan via WhatsApp.']);
    }

    public function verifyOtpWa(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'otp_code'     => 'required|string|digits:6',
        ]);

        $otp = Otp::where('identifier', $request->phone_number)
            ->where('type', 'login_whatsapp')
            ->first();

        if (!$otp || now()->isAfter($otp->expires_at)) {
            return response()->json(['success' => false, 'message' => 'Kode OTP salah atau sudah kadaluarsa.'], 400);
        }

        if ($otp->attempts >= 3) {
            return response()->json(['success' => false, 'message' => 'Akses dibekukan sementara karena terlalu banyak percobaan.'], 429);
        }

        if (!Hash::check($request->otp_code, $otp->otp_code)) {
            $otp->increment('attempts');
            return response()->json(['success' => false, 'message' => 'Kode OTP tidak sesuai. Sisa percobaan: ' . (3 - $otp->attempts)], 400);
        }

        $otp->delete();
        $user = User::where('phone_number', $request->phone_number)->first();

        return response()->json([
            'success' => true,
            'message' => 'Verifikasi berhasil',
            'data'    => [
                'user'  => $user,
                'token' => $user->createToken('auth_token')->plainTextToken,
            ]
        ]);
    }

    public function requestForgotPassword(Request $request)
    {
        $request->validate([
            'method'     => 'required|in:email,whatsapp',
            'identifier' => 'required|string',
        ]);

        $field = $request->method === 'email' ? 'email' : 'phone_number';
        if (!User::where($field, $request->identifier)->exists()) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        $otpCode = rand(100000, 999999);

        Otp::updateOrCreate(
            ['identifier' => $request->identifier, 'type' => 'forgot_password'],
            [
                'otp_code'   => Hash::make($otpCode),
                'attempts'   => 0,
                'expires_at' => now()->addMinutes(10),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Instruksi reset kata sandi telah dikirimkan.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'otp_code'   => 'required|string|digits:6',
            'password'   => 'required|string|min:8|confirmed',
        ]);

        $otp = Otp::where('identifier', $request->identifier)
            ->where('type', 'forgot_password')
            ->first();

        if (!$otp || now()->isAfter($otp->expires_at) || !Hash::check($request->otp_code, $otp->otp_code)) {
            return response()->json(['success' => false, 'message' => 'Kode verifikasi tidak valid atau kadaluarsa.'], 400);
        }

        $field = filter_var($request->identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';
        User::where($field, $request->identifier)->update([
            'password' => Hash::make($request->password)
        ]);

        $otp->delete();

        return response()->json(['success' => true, 'message' => 'Kata sandi berhasil diperbarui. Silakan masuk kembali.']);
    }
}
