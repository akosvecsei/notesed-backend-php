<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        Log::info('User registration started', [
            'email' => $request->email,
            'firstName' => $request->firstName,
            'lastName' => $request->lastName
        ]);

        try {
            $request->validate([
                'email' => 'required|email|unique:users,email',
                'firstName' => 'required|min:3',
                'lastName' => 'required|min:3',
                'password' => 'required|min:6|confirmed',
            ]);

            Log::info('Validation successful. Proceeding with user registration.');

            $user = User::registerUser(
                $request->email,
                $request->firstName,
                $request->lastName,
                $request->password,
            );

            Log::info('User created successfully, generating API token.');

            $token = $user->createToken('notesed')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully.',
                'token' => $token,
                'user' => $user
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed during registration.', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);

            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error during user registration.', [
                'exception' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);

            return response()->json([
                'message' => 'An error occurred during registration.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {

        Log::info('Login request received', ['email' => $request->email]);
        
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        try {
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json(['message' => 'Invalid email or password.'], 400);
            }

            if (!Hash::check($validated['password'], $user->password)) {
                return response()->json(['message' => 'Invalid email or password.'], 400);
            }

            $token = $user->createToken('notesed')->plainTextToken;

            $user->lastLogin = now();
            $user->save();

            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Error during login: ', ['exception' => $e]);

            return response()->json(['message' => 'Server error.'], 500);
        }
    }
}
