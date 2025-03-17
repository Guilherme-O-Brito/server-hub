@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <h6 class="font-bold mb-2">Barrinha</h6>
    <div>
        <button
            class="flex items-center text-sm bg-blue-500 hover:bg-blue-600 px-4 py-1 rounded-lg text-white shadow-lg cursor-pointer"
        >
            Butão
            <svg class="w-6" data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 5.25 7.5 7.5 7.5-7.5m-15 6 7.5 7.5 7.5-7.5"></path>
            </svg>
        </button>
        <div class="absolute inline bg-white border border-gray-300 py-1 shadow-md rounded-sm">
            <a 
                href="{{ url('/login') }}"
                class="w-full text-sm px-3 bg-white hover:bg-gray-100"
            >Login</a>
            <a 
                href=""
                class="w-full text-sm px-3 bg-white hover:bg-gray-100"
            >Link 2</a>
            <a 
                href=""
                class="w-full text-sm px-3 bg-white hover:bg-gray-100"
            >Link 3</a>
        </div>
    </div>
</div>
<br>
<div class=" relative space-y-4">
    <div class="flex justify-center">
        <img src="{{ asset('imgs/Logo server-hub2.svg') }}" alt="Logo" class="w-100">
    </div>
    <div class="pt-8">
        <p class="text-2xl font-bold text-center">Para acessar efetue o login.</p>
    </div>
    <div>
        <h2 class="text-5xl text-emerald-500 font-bold text-center">Bem Vindo ao Server-hub!</h2>
    </div>
    <div>
        <p class="text-2xl font-bold text-amber-500 text-center">Para acessar efetue o login.</p>
    </div>
    <div class="flex flex-col bg-green-300 mx-150 rounded-2xl shadow-lg overflow-hidden">
        <div class="bg-green-600 p-4">
            <h1 class="text-2xl text-white ml-5">Faça seu Login</h1>
        </div>
        <br>
        <p class="text-base text-white ml-5">Login</p>
        <input 
            type="text"
            class="bg-white focus:bg-green-100 text-lg p-3 m-5 focus:text-blue-700 rounded-2xl focus:shadow-lg focus:outline-none"
        >
        <br>
        <p class="text-base text-white ml-5">Senha</p>
        <input 
            type="text"
            class="bg-white focus:bg-green-100 text-lg p-3 m-5 focus:text-blue-700 rounded-2xl focus:shadow-lg focus:outline-none"
        >
        <div class="flex justify-center bg-green-700 p-2">
            <button 
                type="button" 
                class="text-white bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:bg-gradient-to-br font-medium rounded-lg px-5 py-2.5 text-center text-5xl me-2 mb-2 shadow cursor-pointer"
            >
                Login
            </button>
        </div>
    </div>
</div>
@endsection