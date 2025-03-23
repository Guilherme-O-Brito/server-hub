<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MinecraftDashBoardController extends Controller
{
    public function index()
    {
        return view('dashboard.minecraft.index', ['game' => 'minecraft', 'page' => 'index']);
    }

    public function arquivos()
    {
        return view('dashboard.minecraft.files', ['game' => 'minecraft', 'page' => 'arquivos']);
    }

    public function config()
    {
        return view('dashboard.minecraft.configs', ['game' => 'minecraft', 'page' => 'config']);
    }
}
