@extends('layouts.app')

@section('content')
  <div class="max-w-md mx-auto bg-white p-8 border rounded-lg shadow-sm">
    <h1 class="text-2xl font-bold mb-6 text-center">Register</h1>

    @if(session()->getFlash('error'))
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">{{ session()->getFlash('error') }}</span>
      </div>
    @endif
    @if(session()->getFlash('success'))
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">{{ session()->getFlash('success') }}</span>
      </div>
    @endif

    <form method="POST" action="/register">
      @csrf

      <div class="mb-4">
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
        <input type="text" name="name" id="name" class="input" value="{{ old('name') }}" required autofocus>
        @if(errors('name'))<p class="text-red-500 text-xs mt-1">{{ errors('name')[0] }}</p>@endif
      </div>

      <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
        <input type="email" name="email" id="email" class="input" value="{{ old('email') }}" required>
        @if(errors('email'))<p class="text-red-500 text-xs mt-1">{{ errors('email')[0] }}</p>@endif
      </div>

      <div class="mb-4">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input type="password" name="password" id="password" class="input" required>
        @if(errors('password'))<p class="text-red-500 text-xs mt-1">{{ errors('password')[0] }}</p>@endif
      </div>

      <div class="mb-6">
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="input" required>
      </div>

      <button type="submit" class="btn w-full">Register</button>
    </form>

    <p class="text-center text-sm text-gray-600 mt-6">
      Already have an account? <a href="/login" class="text-gray-900 hover:underline">Login</a>
    </p>
  </div>
@endsection