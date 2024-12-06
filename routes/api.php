<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TaskController;

Auth::routes();

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/create', [TaskController::class, 'create']);
Route::get('/list', [TaskController::class, 'list']);
Route::delete('/delete/{id}', [TaskController::class, 'destroy']);

Route::patch('/tasks/{task}/assign', [TaskController::class, 'assignUsers']);
Route::patch('/tasks/{task}/unassign', [TaskController::class, 'unassignUsers']);