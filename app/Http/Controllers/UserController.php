<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    
    // public function getUserFromToken(Request $request)
    // {
    //     $user = $request->user();

    //     $userId = $user->id;

    //     return response()->json([
    //         'message' => 'User found',
    //         'user_id' => $userId
    //     ], 200);
    // }

    // public function index(Request $request)
    // {
    //     return response()->json(User::all());
    // }
}