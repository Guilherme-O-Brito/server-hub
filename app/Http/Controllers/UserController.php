<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    
    public function index(Request $request)
    {   

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1']
        ]);

        $search = trim($validated['search'] ?? '');

        $users = User::query()
            ->select(['id', 'name'])
            ->when(
                $search !== '',
                fn ($query) => $query->where(
                    'name',
                    'like',
                    '%'.$search.'%'
                )
            )->orderBy('name')->orderBy('id')->paginate(5)->withQueryString();

        return response()->json($users);
    }

    // admin index sends all the user data that isnt protected
    public function adminIndex(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1']
        ]);

        $search = trim($validated['search'] ?? '');

        $users = User::query()
            ->select()
            ->when(
                $search !== '',
                fn ($query) => $query->where(
                    'name',
                    'like',
                    '%'.$search.'%'
                )
            )->orderBy('name')->orderBy('id')->paginate(20)->withQueryString();

        return response()->json($users);
    }

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
     public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()],
            'is_admin' => ['required', 'boolean'] 
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if ($validated['password'] != null) {
            $user->password = Hash::make($validated['password']);
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();
        }
        $user->is_admin = $validated['is_admin'];

        $user->save();

        return response()->json(['message' => 'User successfully modified']);

    }

    public function delete(Request $request, User $user) 
    {
        // admin cant delete itself
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'Are you dumb?'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User successfully deleted']);
    }

}
