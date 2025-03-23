<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AssettoCorsaDashBoardController extends Controller
{
    public function index()
    {
        return view('dashboard.assetto-corsa.index', ['game' => 'assettoCorsa', 'page' => 'index']);
    }

    public function arquivos()
    {
        return view('dashboard.assetto-corsa.files', ['game' => 'assettoCorsa', 'page' => 'arquivos']);
    }

    public function config()
    {
        return view('dashboard.assetto-corsa.configs', ['game' => 'assettoCorsa', 'page' => 'config']);
    }
}
