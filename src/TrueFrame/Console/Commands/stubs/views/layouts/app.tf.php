<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>TrueFrame</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="/build/app.css" rel="stylesheet" />
</head>
<body class="bg-gray-50 text-gray-900">
  <nav class="bg-white border-b">
    <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
      <a href="/" class="font-bold text-lg">⚡ TrueFrame</a>
      <div class="text-sm text-gray-500">AI-Powered PHP Framework</div>
    </div>
  </nav>
  <main class="max-w-4xl mx-auto p-6">
    @yield('content')
  </main>
</body>
</html>