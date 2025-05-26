<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Service Provider Login</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'instrument-sans', sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 0.25rem;
        }
        .btn-primary {
            background-color: #FFD700;
            color: #000;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 0.25rem;
            width: 100%;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-link {
            color: #FFD700;
            text-decoration: none;
        }
        .alert {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 0.25rem;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="login-container">
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="mx-auto mb-2" style="max-width: 80px; max-height: 80px; border-radius: 50%;">
            <h2 class="text-2xl font-bold">Welcome Back 👋</h2>
        </div>
        
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        
        <form method="POST" action="{{ route('service-provider.login.submit') }}">
            @csrf
            
            <div class="form-group">
                <label for="phone_number" class="block mb-1">Phone Number</label>
                <div class="flex">
                    <select class="form-control" style="width: 80px; margin-right: 8px;">
                        <option value="+966">+966</option>
                    </select>
                    <input id="phone_number" type="text" class="form-control @error('phone_number') border-red-500 @enderror" name="phone_number" value="{{ old('phone_number') }}" required autocomplete="phone_number" autofocus placeholder="5x xxx xxxx">
                </div>
                @error('phone_number')
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="password" class="block mb-1">Password</label>
                <input id="password" type="password" class="form-control @error('password') border-red-500 @enderror" name="password" required autocomplete="current-password">
                @error('password')
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-primary">
                    Login
                </button>
            </div>
            
            <div class="text-center">
                <p>Don't have an account? <a href="{{ route('service-provider.register') }}" class="btn-link">Sign Up</a></p>
            </div>
        </form>
    </div>
</body>
</html>
