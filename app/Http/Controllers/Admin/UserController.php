<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;


class UserController extends Controller
{
        // Route::get('/', [UserController::class, 'index']);
        // Route::get('/{user}', [UserController::class, 'show']);
        // Route::put('/{user}', [UserController::class, 'update']);
        // Route::put('/{user}/suspend', [UserController::class, 'suspend']);
        // Route::put('/{user}/activate', [UserController::class, 'activate']);
        // Route::delete('/{user}', [UserController::class, 'destroy']);

    public function index()
    {
        // Retrieve and return a list of users
        $users = User::all();
        return response()->json($users);
    }


    public function show($id)
    {
        // Retrieve and return a specific user by ID
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        // Update user details
        $user = User::findOrFail($id);
        $user->update($request->all());
        return response()->json($user);
    }

    public function suspend($id)
    {
        // Suspend the user account
        $user = User::findOrFail($id);
        $user->status = 'suspended';
        $user->save();
        return response()->json(['message' => 'User suspended successfully.']);
    }

    public function activate($id)
    {
        // Activate the user account
        $user = User::findOrFail($id);
        $user->status = 'active';
        $user->save();
        return response()->json(['message' => 'User activated successfully.']);
    }

    public function destroy($id)
    {
        // Delete the user account
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully.']);
    }


}
