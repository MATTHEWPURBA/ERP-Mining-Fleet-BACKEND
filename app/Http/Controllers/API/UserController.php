<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('location', 'supervisor')
            ->when($request->search, function($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->role, function($query, $role) {
                $query->where('role', $role);
            })
            ->when($request->location_id, function($query, $locationId) {
                $query->where('location_id', $locationId);
            })
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return response()->json($users);
    }

    public function store(UserRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        
        $user = User::create($validated);
        
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('location', 'supervisor')
        ], 201);
    }

    public function show(User $user)
    {
        return response()->json($user->load('location', 'supervisor'));
    }

    public function update(UserRequest $request, User $user)
    {
        $validated = $request->validated();
        
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        
        $user->update($validated);
        
        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh(['location', 'supervisor'])
        ]);
    }

    public function destroy(User $user)
    {
        try {
            $user->delete();
            return response()->json(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Cannot delete user. It is referenced by other records.'], 400);
        }
    }
}


// app/Http/Controllers/API/UserController.php