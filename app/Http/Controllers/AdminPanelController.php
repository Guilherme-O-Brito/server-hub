<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminPanelController extends Controller
{
    public function index() 
    {
        
        $users = User::all(['id', 'name', 'email', 'is_admin', 'created_at', 'updated_at'])->makeHidden(['password', 'remember_token']);
        $servers = GameServer::with('owner')->get()->makeHidden(['config', 'user_id']);

        return view('admin.index', ['users' => $users, 'servers' => $servers]);
        
    }

    public function createUser(Request $request) 
    {
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

        return response()->json(['message' => 'Usuário criado com sucesso!']);

    }

    public function updateUser(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'password' => ['nullable', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()],
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

        return response()->json(['message' => 'Alteração registrada com sucesso!']);

    }

    public function deleteUser(Request $request) 
    {
        $user = User::findOrFail($request->id);

        // garante que o admin não esta deletando a si proprio
        if (auth()->id() === $user->id) {
            return response()->json(['error' => 'Você não pode deletar a si mesmo'], 403);
        }

        $user->delete();

        return response()->json(['succes' => true]);
    }

}
