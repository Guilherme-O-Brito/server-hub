<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Models\User;
use Illuminate\Http\Request;

class AdminPanelController extends Controller
{
    public function index() {
        
        $users = User::all(['id', 'name', 'email', 'is_admin'])->makeHidden(['password', 'remember_token']);
        $servers = GameServer::with('owner')->get()->makeHidden(['config', 'user_id']);

        return view('admin.index', ['users' => $users, 'servers' => $servers]);
        
    }
}
