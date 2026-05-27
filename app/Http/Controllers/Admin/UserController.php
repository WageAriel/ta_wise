<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $users = User::select('id', 'username', 'email', 'role')
            ->orderBy('username', 'asc')
            ->get();

        return Inertia::render('Admin/User Management/Index', [
            'users' => $users
        ]);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting self
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        // Logic for adding user will go here
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id)
    {
        // Logic for updating user will go here
    }
}
