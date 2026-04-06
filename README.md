# ⚡ TrueFrame Framework

Core engine for the [TrueFrame PHP framework](https://github.com/habe90/trueframe) — the AI-powered PHP framework for rapid development.

This package provides all framework internals. It is installed automatically as a Composer dependency.

## Install via Skeleton (Recommended)

```bash
composer create-project trueframe/trueframe myapp
```

## Core Components

- **Container** — Dependency Injection with auto-resolution, singletons, and aliases
- **Router** — HTTP routing with groups, middleware, and route parameters
- **TrueBlade** — Template engine with `@extends`, `@section`, `@yield`, `@if`, `@foreach`, `{{ }}` syntax
- **ORM** — ActiveRecord with QueryBuilder, migrations, schema builder, and seeders
- **HTTP** — Request/Response objects, middleware pipeline, form request validation
- **Console** — CLI application with 17+ built-in commands including AI scaffolding
- **Session** — Session management with flash data and CSRF protection
- **Config** — Environment (.env) and configuration repository
- **Logging** — Monolog-based logging with exception handler

## Requirements

- PHP 8.2+
- ext-json, ext-mbstring, ext-pdo

## License

MIT
