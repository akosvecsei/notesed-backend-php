<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\User;

class TaskController extends Controller
{
    public function create(Request $request)
    {

        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Token not provided.'
            ], 400);
        }

        Log::info('Received Token:', ['token' => $token]);

        $tokenRecord = PersonalAccessToken::findToken($token);

        if (!$tokenRecord) {
            return response()->json([
                'message' => 'Invalid token.'
            ], 404);
        }

        $userId = $tokenRecord->tokenable_id;

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        $userId = $user->id;

        $validated = $request->validate([
            'title' => 'required|string',
            'content' => 'nullable|string',
            'dueDate' => 'nullable|date',
            'priority' => 'nullable|integer|min:1|max:5',
            'tags' => 'nullable|string'
        ]);

        try {
            $task = new Task();
            $task->title = $validated['title'];
            $task->content = $validated['content'] ?? '';
            $task->dueDate = $validated['dueDate'] ?? now();
            $task->priority = $validated['priority'] ?? 5;
            $task->tags = $validated['tags'] ?? '';
            $task->createdBy = $userId;

            $task->save();

            return response()->json([
                'message' => 'Task created successfully.',
                'task' => $task
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the task.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function list(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Token not provided.'
            ], 400);
        }

        $tokenRecord = PersonalAccessToken::findToken($token);

        if (!$tokenRecord) {
            return response()->json([
                'message' => 'Invalid token.'
            ], 404);
        }

        $userId = $tokenRecord->tokenable_id;

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        $tasks = Task::where('createdBy', $user->id)->get();

        if ($tasks->isEmpty()) {
            return response()->json([
                'message' => 'No tasks found for this user.'
            ], 404);
        }

        return response()->json([
            'message' => 'Tasks retrieved successfully.',
            'tasks' => $tasks
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Token not provided.'
            ], 400);
        }

        $tokenRecord = PersonalAccessToken::findToken($token);

        if (!$tokenRecord) {
            return response()->json([
                'message' => 'Invalid token.'
            ], 404);
        }

        $userId = $tokenRecord->tokenable_id;

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'message' => 'Task not found.'
            ], 404);
        }

        if ((int)$task->createdBy !== (int)$user->id) {
            return response()->json([
                'message' => 'Unauthorized action. You can only delete your own tasks.',
                'createdBy' => $task->createdBy,
                'userId' => $user->id
            ], 403);
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.'
        ], 200);
    }

    public function assignUsers(Request $request, Task $task)
    {
        Log::info('Assign Users API called');

        $token = $request->bearerToken();
        Log::info('Bearer token received: ' . $token);

        if (!$token) {
            Log::error('Token not provided');
            return response()->json([
                'message' => 'Token not provided.'
            ], 400);
        }

        $tokenRecord = PersonalAccessToken::findToken($token);
        Log::info('Token record found: ' . ($tokenRecord ? 'Yes' : 'No'));

        if (!$tokenRecord) {
            Log::error('Invalid token');
            return response()->json([
                'message' => 'Invalid token.'
            ], 404);
        }

        $userId = $tokenRecord->tokenable_id;
        Log::info('User ID: ' . $userId); 

        $user = User::find($userId);
        Log::info('User found: ' . ($user ? 'Yes' : 'No'));

        if (!$user) {
            Log::error('User not found');
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        if ((int) $task->createdBy !== (int) $user->id) {
            Log::error('Unauthorized action');
            return response()->json([
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $request->validate([
            'assigned_user_ids' => 'required|array',
            'assigned_user_ids.*' => 'exists:users,id',
        ]);
        Log::info('Assigned user IDs: ' . implode(', ', $request->assigned_user_ids));  // Hozzárendelt felhasználó ID-k

        $assignedUserIds = $request->assigned_user_ids;
        $assignedUsers = $task->assignedUsers ?? [];

        foreach ($assignedUserIds as $assignedUserId) {
            if (!in_array($assignedUserId, $assignedUsers)) {
                $assignedUsers[] = $assignedUserId;
            }
        }

        Log::info('Assigned users after update: ' . implode(', ', $assignedUsers));  // Frissített felhasználók naplózása

        $task->assignedUsers = $assignedUsers;
        $task->save();

        return response()->json([
            'message' => 'Users assigned to task successfully',
            'task' => $task
        ]);
    }


    public function unassignUsers(Request $request, Task $task)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Token not provided.'
            ], 400);
        }

        $tokenRecord = PersonalAccessToken::findToken($token);

        if (!$tokenRecord) {
            return response()->json([
                'message' => 'Invalid token.'
            ], 404);
        }

        $userId = $tokenRecord->tokenable_id;

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        if ((int)$task->createdBy !== (int)$user->id) {
            return response()->json([
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $request->validate([
            'unassigned_user_ids' => 'required|array',
            'unassigned_user_ids.*' => 'exists:users,id',
        ]);

        $assignedUserIds = $request->unassigned_user_ids;

        $assignedUsers = $task->assignedUsers ?? [];

        foreach ($assignedUserIds as $assignedUserId) {
            if (in_array($assignedUserId, $assignedUsers)) {
                $assignedUsers = array_diff($assignedUsers, [$assignedUserId]);
            }
        }

        $task->assignedUsers = array_values($assignedUsers);
        $task->save();

        return response()->json([
            'message' => 'Users unassigned from task successfully',
            'task' => $task
        ]);
    }

}
