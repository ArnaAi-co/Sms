@extends('layouts.auth')

@section('metadata')
    <meta name="description" content="{{ __($metadata->login_description) }}">
    <meta name="keywords" content="{{ __($metadata->login_keywords) }}">
    <meta name="author" content="{{ __($metadata->login_author) }}">
    <link rel="canonical" href="{{ $metadata->login_url }}">
    <title>{{ __($metadata->login_title) }}</title>
@endsection

@section('content')
    <div class="container-fluid h-100vh">
        <div class="row login-background justify-content-center">
            <div class="col-md-6 col-sm-12" id="login-responsive">
                <div class="row justify-content-center">
                    <div class="col-lg-7 mx-auto">
                        <div class="card-body pt-10">
                            <form method="POST" action="{{ route('login.otp') }}" id="login-form" onsubmit="process()">
                                @csrf
                                <input type="hidden" name="login_type" id="login_type" value="otp">

                                <h3 class="text-center login-title mb-8">{{ __('Welcome Back to') }} <span
                                        class="text-info"><a href="{{ url('/') }}">{{ config('app.name') }}</a></span>
                                </h3>

                                @if ($message = Session::get('success'))
                                    <div class="alert alert-login alert-success">
                                        <strong><i class="fa fa-check-circle"></i> {{ $message }}</strong>
                                    </div>
                                @endif

                                @if ($message = Session::get('error'))
                                    <div class="alert alert-login alert-danger">
                                        <strong><i class="fa fa-exclamation-triangle"></i> {{ $message }}</strong>
                                    </div>
                                @endif

                                <!-- OTP Login Tab -->
                                <div class="tab-content" id="loginTabsContent">
                                    <div class="tab-pane fade show active" id="otp-login" role="tabpanel" aria-labelledby="otp-tab">
                                        <div class="input-box mb-4">
                                            <label for="phone_number_otp" class="fs-12 font-weight-bold text-md-right">{{ __('Phone number') }}</label>
                                            <input id="phone_number_otp" type="text" class="form-control @error('phone_number') is-invalid @enderror" name="phone_number_otp" value="{{ old('phone_number') }}" autocomplete="off" placeholder="{{ __('Phone number') }}" required>
                                            @error('phone_number')
                                            <span class="invalid-feedback" role="alert">
                                                    {{ __($message) }}
                                                </span>
                                            @enderror
                                            <button type="button" class="btn btn-outline-primary w-100 mt-2" id="send-otp" onclick="sendOtp()">{{ __('Send Code') }}</button>
                                            <p id="otp-timer" class="fs-12 text-muted mt-2" style="display: none;">{{ __('Resend available in:') }} <span id="countdown">120</span> {{ __('seconds') }}</p>
                                        </div>

                                        <div class="input-box">
                                            <label for="otp" class="fs-12 font-weight-bold text-md-right">{{ __('SMS Code') }}</label>
                                            <input id="otp" type="text" class="form-control @error('otp') is-invalid @enderror" name="otp" autocomplete="off" placeholder="{{ __('Enter Received Code') }}" maxlength="6" required>
                                            @error('otp')
                                            <span class="invalid-feedback" role="alert">
                                                    {{ __($message) }}
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" name="recaptcha" id="recaptcha">

                                <div class="text-center">
                                    <div class="form-group mb-0">
                                        <button type="submit" class="btn btn-primary font-weight-bold login-main-button" id="sign-in">{{ __('Sign In') }}</button>
                                    </div>

                                    @if (config('settings.registration') == 'enabled')
                                        <p class="fs-10 text-muted pt-3 mb-0">{{ __('New to ') }} <a href="{{ url('/') }}" class="special-action-sign">{{ config('app.name') }}?</a></p>
                                        <a href="{{ route('register') }}" class="fs-12 font-weight-bold special-action-sign">{{ __('Sign Up') }}</a>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-sm-12 text-center background-special align-middle p-0" id="login-background">
                <div class="login-bg">
                    <img src="{{ theme_url('img/frontend/backgrounds/login.webp') }}" alt="">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @if (config('services.google.recaptcha.enable') == 'on')
        <!-- Google reCaptcha JS -->
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.google.recaptcha.site_key') }}"></script>
        <script>
            grecaptcha.ready(function () {
                grecaptcha.execute('{{ config('services.google.recaptcha.site_key') }}', {action: 'contact'}).then(function (token) {
                    if (token) {
                        document.getElementById('recaptcha').value = token;
                    }
                });
            });
        </script>
    @endif

    <script type="text/javascript">
        let loading = `<span class="loading">
            <span style="background-color: #fff;"></span>
            <span style="background-color: #fff;"></span>
            <span style="background-color: #fff;"></span>
        </span>`;

        function process() {
            $('#sign-in').prop('disabled', true);
            let btn = document.getElementById('sign-in');
            btn.innerHTML = loading;
            document.querySelector('#loader-line')?.classList?.remove('hidden');
            return;
        }

        function startOtpTimer() {
            let timeLeft = 120;
            const timerDisplay = document.getElementById('otp-timer');
            const countdown = document.getElementById('countdown');
            const sendOtpButton = document.getElementById('send-otp');
            timerDisplay.style.display = 'block';
            sendOtpButton.disabled = true;

            const timer = setInterval(() => {
                countdown.textContent = timeLeft;
                timeLeft--;
                if (timeLeft < 0) {
                    clearInterval(timer);
                    timerDisplay.style.display = 'none';
                    sendOtpButton.disabled = false;
                }
            }, 1000);
        }

        function sendOtp() {
            const phoneInput = document.getElementById('phone_number_otp').value;
            const sendOtpButton = document.getElementById('send-otp');
            if (!phoneInput.match(/^09\d{9}$/)) {
                alert('{{ __('Please enter a valid 11-digit phone number starting with 09') }}');
                return;
            }

            sendOtpButton.disabled = true;
            sendOtpButton.innerHTML = loading;

            $.ajax({
                url: '{{ route('send.otp') }}',
                method: 'POST',
                data: {
                    phone_number: phoneInput,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    alert('{{ __('OTP sent successfully!') }}');
                    startOtpTimer();
                },
                complete: function () {
                    const input = document.getElementById('phone_number_otp');
                    input.readOnly  = true;
                    input.classList.add('disabled-style');
                    sendOtpButton.innerHTML = '{{ __('Send Code') }}';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const phoneInputOtp = document.getElementById('phone_number_otp');
            const otpInput = document.getElementById('otp');
            const signIn = document.getElementById('sign-in');
            const loginForm = document.getElementById('login-form');
            const loginType = document.getElementById('login_type');

            function validatePhone(input) {
                input.value = input.value.replace('/\D/g/', '');
                signIn.disabled = true;
                if (input.value.length > 11) {
                    input.value = input.value.slice(0, 11);
                }
                if (input.value.length === 11 && /^09\d{9}$/.test(input.value)) {
                    signIn.disabled = false;
                }
            }

            function validateOtp(input) {
                input.value = input.value.replace('/\D/g/', '');
                if (input.value.length > 6) {
                    input.value = input.value.slice(0, 6);
                }
            }

            phoneInputOtp.addEventListener('input', function () {
                validatePhone(phoneInputOtp);
            });

            otpInput.addEventListener('input', function () {
                validateOtp(otpInput);
            });
        });
    </script>
    <style>
        .disabled-style {
            background-color: #f0f0f0;
            color: #999;
            border: 1px solid #ccc;
            cursor: not-allowed;
        }
    </style>
@endsection
