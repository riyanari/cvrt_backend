<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
{
    public function login(Request $request)
    {
        $request->validate([
            // sekarang boleh username (ridhoac, wakhid, keppic, dst)
            'email' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $login = trim((string) $request->email);

        $user = User::where('email', $login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->unauthorized('Username atau password salah');
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->ok([
            'token' => $token,
            'user' => $user,
        ], 'Login sukses');
    }

    public function me(Request $request)
    {
        return $this->ok($request->user(), 'OK');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return $this->ok(null, 'Logout sukses');
    }
}
