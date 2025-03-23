<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TerrariaDashBoardController extends Controller
{
    public function index()
    {
        return view('dashboard.terraria.index', ['game' => 'terraria', 'page' => 'index']);
    }

    public function arquivos()
    {
        return view('dashboard.terraria.files', ['game' => 'terraria', 'page' => 'arquivos']);
    }

    public function config()
    {
        return view('dashboard.terraria.configs', ['game' => 'terraria', 'page' => 'config']);
    }
}
