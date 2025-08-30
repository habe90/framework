@extends('layouts.app')

@section('content')
  <h1 class="text-2xl font-bold mb-4">{{ $title }}</h1>
  <p class="text-gray-600">Your Laravel-like micro-framework is ready.</p>
  <div class="mt-6 p-4 bg-white rounded-lg shadow-sm border">
    <h2 class="text-xl font-semibold mb-2">Features:</h2>
    <ul class="list-disc list-inside text-gray-700">
      <li>Router with GET/POST/PUT/PATCH/DELETE, groups, middleware</li>
      <li>Controllers, Request, Response objects</li>
      <li>Dependency Injection Container + Service Providers</li>
      <li>Minimal ActiveRecord ORM + QueryBuilder</li>
      <li>Migrations, Schema/Blueprint, Seeders</li>
      <li>TrueBlade templating engine with caching</li>
      <li>FormRequest validation</li>
      <li>CSRF & Session Middleware</li>
      <li>Configuration loader (.env)</li>
      <li>Monolog-based logging & Exception Handler</li>
      <li>CLI tool "trueframe" (Artisan-like)</li>
      <li>Tailwind UI scaffolding</li>
      <li>AI Scaffolder hook</li>
    </ul>
  </div>
@endsection