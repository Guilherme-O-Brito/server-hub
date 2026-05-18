@extends('layouts.app')

@section('content')
<div id="admin">
    <admin-page :users='@json($users)' :servers='@json($servers)' />
</div>
@vite('resources/js/app.js')
@endsection