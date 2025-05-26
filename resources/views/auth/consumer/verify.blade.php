<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Verify Phone</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'instrument-sans', sans-serif;
        }
        .verify-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
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
        .otp-input-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 0.25rem;
        }
        .resend-timer {
            text-align: center;
            margin-bottom: 1rem;
            color: #666;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="verify-container">
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="mx-auto mb-2" style="max-width: 80px; max-height: 80px; border-radius: 50%;">
            <h2 class="text-2xl font-bold">Verification</h2>
            <p class="text-gray-600">Enter OTP Code We Just Sent You On Your Phone Number</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('consumer.verify') }}" id="verifyForm">
            @csrf

            <input type="hidden" name="phone_number" value="{{ session('phone_number') }}">

            <div class="otp-input-container">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp1" autofocus>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp2">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp3">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp4">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp5">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp6">
            </div>

            <input type="hidden" name="code" id="otpValue">

            <div class="resend-timer">
                <p>Resending Message after <span id="timer">01:00</span></p>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-primary">
                    Verify
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const otpInputs = document.querySelectorAll('.otp-input');
            const otpValue = document.getElementById('otpValue');
            const form = document.getElementById('verifyForm');

            // Auto-focus next input
            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function() {
                    if (this.value.length === this.maxLength) {
                        if (index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                    }
                });

                // Handle backspace
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value === '' && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });
            });

            // Combine OTP values on form submit
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                let otp = '';
                otpInputs.forEach(input => {
                    otp += input.value;
                });
                otpValue.value = otp;

                if (otp.length === 6) {
                    this.submit();
                }
            });

            // Timer for resend
            let timeLeft = 60;
            const timerElement = document.getElementById('timer');

            function updateTimer() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;

                timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerElement.parentElement.innerHTML = '<a href="#" class="btn-link">Resend Code</a>';
                } else {
                    timeLeft--;
                }
            }

            const timerInterval = setInterval(updateTimer, 1000);
            updateTimer();
        });
    </script>
</body>
</html>
