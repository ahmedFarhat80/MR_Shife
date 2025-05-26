<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Service Provider Registration</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'instrument-sans', sans-serif;
        }
        .register-container {
            max-width: 500px;
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
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #ddd;
            margin: 0 5px;
        }
        .step-dot.active {
            background-color: #FFD700;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="register-container">
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="mx-auto mb-2" style="max-width: 80px; max-height: 80px; border-radius: 50%;">
            <h2 class="text-2xl font-bold">Let's get started!</h2>
        </div>
        
        <div class="step-indicator">
            <div class="step-dot active" data-step="1"></div>
            <div class="step-dot" data-step="2"></div>
            <div class="step-dot" data-step="3"></div>
        </div>
        
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        
        <form method="POST" action="{{ route('service-provider.register.submit') }}" id="registerForm">
            @csrf
            
            <!-- Step 1: Basic Information -->
            <div class="step active" id="step1">
                <div class="form-group">
                    <label for="name" class="block mb-1">Full Name</label>
                    <input id="name" type="text" class="form-control @error('name') border-red-500 @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                    @error('name')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="phone_number" class="block mb-1">Phone Number</label>
                    <div class="flex">
                        <select class="form-control" style="width: 80px; margin-right: 8px;">
                            <option value="+966">+966</option>
                        </select>
                        <input id="phone_number" type="text" class="form-control @error('phone_number') border-red-500 @enderror" name="phone_number" value="{{ old('phone_number') }}" required autocomplete="phone_number" placeholder="5x xxx xxxx">
                    </div>
                    @error('phone_number')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="email" class="block mb-1">Email (Optional)</label>
                    <input id="email" type="email" class="form-control @error('email') border-red-500 @enderror" name="email" value="{{ old('email') }}" autocomplete="email">
                    @error('email')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-group">
                    <button type="button" class="btn-primary next-step" data-next="2">
                        Next
                    </button>
                </div>
            </div>
            
            <!-- Step 2: Business Information -->
            <div class="step" id="step2">
                <div class="form-group">
                    <label for="business_name" class="block mb-1">Business Name</label>
                    <input id="business_name" type="text" class="form-control @error('business_name') border-red-500 @enderror" name="business_name" value="{{ old('business_name') }}" required>
                    @error('business_name')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="business_type" class="block mb-1">Business Type</label>
                    <select id="business_type" class="form-control @error('business_type') border-red-500 @enderror" name="business_type" required>
                        <option value="">Select Business Type</option>
                        <option value="restaurant">Restaurant</option>
                        <option value="bakery">Bakery</option>
                        <option value="homemade">Homemade Food</option>
                        <option value="other">Other</option>
                    </select>
                    @error('business_type')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="business_address" class="block mb-1">Business Address</label>
                    <textarea id="business_address" class="form-control @error('business_address') border-red-500 @enderror" name="business_address" rows="3">{{ old('business_address') }}</textarea>
                    @error('business_address')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-group flex justify-between">
                    <button type="button" class="btn-primary prev-step" data-prev="1" style="width: 48%;">
                        Previous
                    </button>
                    <button type="button" class="btn-primary next-step" data-next="3" style="width: 48%;">
                        Next
                    </button>
                </div>
            </div>
            
            <!-- Step 3: Password -->
            <div class="step" id="step3">
                <div class="form-group">
                    <label for="password" class="block mb-1">Password</label>
                    <input id="password" type="password" class="form-control @error('password') border-red-500 @enderror" name="password" required autocomplete="new-password">
                    @error('password')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="password-confirm" class="block mb-1">Confirm Password</label>
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                </div>
                
                <div class="form-group flex justify-between">
                    <button type="button" class="btn-primary prev-step" data-prev="2" style="width: 48%;">
                        Previous
                    </button>
                    <button type="submit" class="btn-primary" style="width: 48%;">
                        Register
                    </button>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <p>Already have an account? <a href="{{ route('service-provider.login') }}" class="btn-link">Login</a></p>
            </div>
        </form>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const steps = document.querySelectorAll('.step');
            const stepDots = document.querySelectorAll('.step-dot');
            const nextButtons = document.querySelectorAll('.next-step');
            const prevButtons = document.querySelectorAll('.prev-step');
            
            nextButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const nextStep = this.getAttribute('data-next');
                    steps.forEach(step => step.classList.remove('active'));
                    document.getElementById('step' + nextStep).classList.add('active');
                    
                    stepDots.forEach(dot => dot.classList.remove('active'));
                    document.querySelector(`.step-dot[data-step="${nextStep}"]`).classList.add('active');
                });
            });
            
            prevButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const prevStep = this.getAttribute('data-prev');
                    steps.forEach(step => step.classList.remove('active'));
                    document.getElementById('step' + prevStep).classList.add('active');
                    
                    stepDots.forEach(dot => dot.classList.remove('active'));
                    document.querySelector(`.step-dot[data-step="${prevStep}"]`).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
