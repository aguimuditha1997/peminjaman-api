<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            // 1. Validasi Input
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // 2. Cari User
            $user = User::where('email', $request->email)->first();

            // 3. Verifikasi Password (Bcrypt otomatis dicek oleh Hash::check)
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau password salah.'
                ], 401);
            }

            // 4. Generate Token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            // 5. Response Sukses
            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'content' => [
                    'access_token' => $token,
                    'token_type'   => 'Bearer',
                    'user'         => [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email,
                        'role'  => $user->role,
                    ]
                ]
            ], 200);

        } catch (Exception $e) {
            // 6. Tangani jika terjadi error sistem/database
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.',
                'error'   => $e->getMessage() // Hapus line ini saat production untuk keamanan
            ], 500);
        }
    }
    public function me(Request $request)
{
    try {
        // Karena rute ini akan melewati middleware auth:sanctum, 
        // kita bisa langsung mengambil data user dari $request
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diambil.',
            'data'    => $user
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil data user.',
            'error'   => $e->getMessage()
        ], 500);
    }
}

// Logout (Hapus Token)
public function logout(Request $request)
{
    try {
        // Menghapus token yang sedang digunakan saat ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout, token telah dihapus.'
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal melakukan logout.',
            'error'   => $e->getMessage()
        ], 500);
    }
}
}
