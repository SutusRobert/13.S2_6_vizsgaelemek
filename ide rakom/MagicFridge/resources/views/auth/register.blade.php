@extends('layouts.app')

@section('content')

<form method="POST" action="{{ route('register') }}" class="max-w-md mx-auto bg-white p-6 rounded shadow">
    @csrf

    <!-- Name -->
    <div class="mb-4">
        <label for="name" class="block font-medium text-gray-700">Név</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
            class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-500">
    </div>

    <!-- Email -->
    <div class="mb-4">
        <label for="email" class="block font-medium text-gray-700">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required
            class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-500">
    </div>

    <!-- Password -->
    <div class="mb-4">
        <label for="password" class="block font-medium text-gray-700">Jelszó</label>
        <input id="password" type="password" name="password" required
            class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-500">
    </div>

    <!-- Confirm Password -->
    <div class="mb-4">
        <label for="password_confirmation" class="block font-medium text-gray-700">Jelszó megerősítése</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required
            class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-500">
    </div>

    <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700">
        Regisztráció
    </button>
</form>

@endsection