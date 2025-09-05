<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,manager,viewer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        AuditLog::create([
            'action' => 'User registered',
            'details' => "User {$user->name} registered with role {$user->role}",
            'user_id' => $user->id
        ]);

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    if (!$token = JWTAuth::attempt($credentials)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    $user = auth()->user();

    // If 2FA is enabled → do NOT return final token yet
    if ($user->two_factor_enabled) {
        $tempToken = Str::random(60); // or store in DB / cache
        cache()->put("2fa_temp_{$tempToken}", $user->id, 300); // 5 min

        return response()->json([
            'requires_2fa' => true,
            'temp_token'   => $tempToken,
        ]);
    }

    // If no 2FA, return normal token
    return response()->json([
        'user' => $user,
        'access_token' => $token,
    ]);
}



public function verify2FA(Request $request)
{
    $request->validate([
        'code' => 'required',
        'temp_token' => 'required'
    ]);

    $userId = cache()->get("2fa_temp_{$request->temp_token}");
    if (!$userId) {
        return response()->json(['error' => 'Invalid or expired temp token'], 401);
    }

    $user = User::find($userId);
    if (!$user || !$user->two_factor_secret) {
        return response()->json(['error' => '2FA not enabled'], 401);
    }

    $google2fa = new Google2FA();

    $valid = $google2fa->verifyKey($user->two_factor_secret, $request->code);

    if (!$valid) {
        return response()->json(['error' => 'Invalid 2FA code'], 401);
    }

    // If valid → issue real JWT
    $token = JWTAuth::fromUser($user);

    // cleanup temp token
    cache()->forget("2fa_temp_{$request->temp_token}");

    return response()->json([
        'user' => $user,
        'access_token' => $token,
    ]);
}


    public function enable2FA(Request $request)
    {
        $user = auth()->user();
        $secret = $user->enableTwoFactorAuth();

        AuditLog::create([
            'action' => '2FA enabled',
            'details' => "User {$user->name} enabled 2FA",
            'user_id' => $user->id
        ]);

        return response()->json([
            'message' => '2FA enabled successfully',
            'secret' => $secret
        ]);
    }

    public function disable2FA(Request $request)
    {
        $user = auth()->user();
        $user->disableTwoFactorAuth();

        AuditLog::create([
            'action' => '2FA disabled',
            'details' => "User {$user->name} disabled 2FA",
            'user_id' => $user->id
        ]);

        return response()->json(['message' => '2FA disabled successfully']);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }

    public function logout()
    {
        $user = auth()->user();
        
        AuditLog::create([
            'action' => 'User logged out',
            'details' => "User {$user->name} logged out",
            'user_id' => $user->id
        ]);

        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function userProfile()
    {
        return response()->json(auth()->user());
    }
}