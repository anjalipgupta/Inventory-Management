<?php
// app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            
            // Only admins can view users
            if (!$currentUser->isAdmin()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $users = User::all()->makeHidden(['password', 'two_factor_secret']);
            
            return response()->json($users);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Authentication required'], 401);
        }
    }

    public function store(Request $request)
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            
            // Only admins can create users
            if (!$currentUser->isAdmin()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

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
                'action' => 'User created',
                'details' => "User {$user->name} created by {$currentUser->name}",
                'user_id' => $currentUser->id
            ]);

            return response()->json([
                'message' => 'User successfully created',
                'user' => $user->makeHidden(['password', 'two_factor_secret'])
            ], 201);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Authentication required'], 401);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            
            // Only admins can update users
            if (!$currentUser->isAdmin()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $user = User::find($id);
            
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
                'password' => 'sometimes|nullable|string|min:6|confirmed',
                'role' => 'sometimes|required|in:admin,manager,viewer',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $updateData = $request->only(['name', 'email', 'role']);
            
            if ($request->has('password') && !empty($request->password)) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            AuditLog::create([
                'action' => 'User updated',
                'details' => "User {$user->name} updated by {$currentUser->name}",
                'user_id' => $currentUser->id
            ]);

            return response()->json([
                'message' => 'User successfully updated',
                'user' => $user->makeHidden(['password', 'two_factor_secret'])
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Authentication required'], 401);
        }
    }

    public function destroy($id)
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            
            // Only admins can delete users
            if (!$currentUser->isAdmin()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $user = User::find($id);
            
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Prevent users from deleting themselves
            if ($user->id === $currentUser->id) {
                return response()->json(['error' => 'You cannot delete your own account'], 400);
            }

            $user->delete();

            AuditLog::create([
                'action' => 'User deleted',
                'details' => "User {$user->name} deleted by {$currentUser->name}",
                'user_id' => $currentUser->id
            ]);

            return response()->json(['message' => 'User successfully deleted']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Authentication required'], 401);
        }
    }
}