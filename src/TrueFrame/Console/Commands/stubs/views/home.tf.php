@extends('layouts.app')

@section('content')
  <h1 class="text-2xl font-bold mb-4">{{ $title }}</h1>
  <p class="text-gray-600">Build faster. Ship smarter. AI-powered PHP development.</p>

  <div class="mt-6 grid gap-4">
    <div class="p-4 bg-white rounded-lg shadow-sm border">
      <h2 class="text-lg font-semibold mb-2">⚡ AI-First Scaffolding</h2>
      <p class="text-gray-600 text-sm mb-2">Generate full CRUD, APIs, and auth in one command:</p>
      <code class="block bg-gray-100 p-2 rounded text-sm text-gray-800">php trueframe ai:crud Product title:string price:float</code>
    </div>

    <div class="p-4 bg-white rounded-lg shadow-sm border">
      <h2 class="text-lg font-semibold mb-2">🛠️ Built-in Essentials</h2>
      <ul class="list-disc list-inside text-gray-700 text-sm space-y-1">
        <li>Router with groups, middleware, and route model binding</li>
        <li>DI Container + Service Providers</li>
        <li>ActiveRecord ORM with migrations</li>
        <li>TrueBlade template engine</li>
        <li>Session, CSRF, Auth middleware</li>
        <li>FormRequest validation</li>
      </ul>
    </div>

    <div class="p-4 bg-white rounded-lg shadow-sm border">
      <h2 class="text-lg font-semibold mb-2">🚀 Quick Start</h2>
      <div class="space-y-1 text-sm">
        <code class="block bg-gray-100 p-2 rounded text-gray-800">php trueframe ai:crud Post title:string body:text</code>
        <code class="block bg-gray-100 p-2 rounded text-gray-800">php trueframe migrate</code>
        <code class="block bg-gray-100 p-2 rounded text-gray-800">php trueframe serve</code>
      </div>
    </div>
  </div>
@endsection