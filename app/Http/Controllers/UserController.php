<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    //admin only    
    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()],
            'is_admin' => ['required', 'boolean']  
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin']
        ]);
        
        return response()->json(['message' => 'User created successfully'], 201);

    }

    // admin only
     public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'password' => ['nullable', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()],
            'is_admin' => ['required', 'boolean'] 
        ]);

        $user = User::findOrFail($id);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if ($validated['password'] != null) {
            $user->password = Hash::make($validated['password']);
        }
        $user->is_admin = $validated['is_admin'];

        $user->save();

        return response()->json(['message' => 'Alteração registrada com sucesso!'], 204);

    }

}
