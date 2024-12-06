<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Task;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'email', 'firstName', 'lastName', 'password', 'profilePicture', 'theme', 
        'language', 'region', 'notifications', 'lastLogin', 'status', 'resetCode', 
        'friends', 'visible'
    ];

    protected $casts = [
        'friends' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->password = Hash::make($user->password);
        });
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public static function registerUser($email, $firstName, $lastName, $password, $profilePicture = null)
    {
        if (self::where('email', $email)->exists()) {
            return response()->json(['message' => 'Email already in use.'], 400);
        }

        $user = new self();
        $user->email = $email;
        $user->firstName = $firstName;
        $user->lastName = $lastName;
        $user->password = $password;
        $user->save();

        return $user;
    }
}
