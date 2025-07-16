<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminPanelController extends Controller
{
    public function index() {
        
        $users = User::all(['id', 'name', 'email', 'is_admin'])->makeHidden(['password', 'remember_token']);
        $servers = GameServer::with('owner')->get()->makeHidden(['config', 'user_id']);

        return view('admin.index', ['users' => $users, 'servers' => $servers]);
        
    }

    public function createUser(Request $request) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()],
            'is_admin' => ['required', 'boolean'] 
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin']
        ]);

        return response()->json(['message' => 'Usuário criado com sucesso!'], 201);

    }

}
