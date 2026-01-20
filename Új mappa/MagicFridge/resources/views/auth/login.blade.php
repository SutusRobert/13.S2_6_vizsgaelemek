@extends('layouts.app')

@section('content')

@if ($errors->any())
    <div class="mb-4 rounded bg-red-100 text-red-700 p-3">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('login') }}" class="max-w-md mx-auto bg-white p-6 rounded shadow">
    @csrf

    <!-- Email -->
    <div class="mb-4">
        <label for="email" class="block font-medium text-gray-700">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
            class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-500">
    </div>

    <!-- Password -->
    <div class="mb-4">
        <label for="password" class="block font-medium text-gray-700">Jelszó</label>
        <input id="password" type="password" name="password" required
            class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-500">
    </div>

    <!-- Forgot Password -->
    <div class="flex justify-end mb-4">
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:underline">
                Elfelejtett jelszó?
            </a>
        @endif
    </div>

    <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700">
        Bejelentkezés
    </button>
</form>

@endsection