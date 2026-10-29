@extends('layouts.app')

@section('title', 'Sign In — Civil Indent Portal')

@push('styles')
    <style>
        .login-box {
            max-width: 400px;
            margin: 40px auto;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            background: linear-gradient(135deg, var(--primary) 0%, #102438 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(22, 50, 79, 0.25);
        }

        .login-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px 28px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 0.825rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: #1e293b;
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            font-size: 0.875rem;
            font-family: inherit;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            color: var(--text-color);
            transition: border-color 0.15s, box-shadow 0.15s;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .form-input.is-invalid {
            border-color: var(--danger);
            background: #fffbfb;
        }

        .invalid-feedback {
            color: var(--danger);
            font-size: 0.75rem;
            margin-top: 4px;
        }

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .btn-login {
            width: 100%;
            padding: 11px;
            font-weight: 600;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
        }
    </style>
@endpush

@section('content')
    <div class="login-box">
        <div class="login-brand">
            <div class="brand-logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 4px;">Civil Indent Portal</h1>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Sign in to your account</p>
        </div>

        <div class="login-card">
            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('login.store') }}" method="POST" id="login-form">
                @csrf

                <div class="form-group">
                    <label for="cpf" class="form-label">CPF (Unique String ID)</label>
                    <input type="text" id="cpf" name="cpf" value="{{ old('cpf') }}"
                        class="form-input @error('cpf') is-invalid @enderror"
                        placeholder="e.g. ADMIN001" required autofocus autocomplete="username">
                    @error('cpf')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password"
                        class="form-input @error('password') is-invalid @enderror"
                        placeholder="Enter your password" required autocomplete="current-password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="login-options">
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} style="accent-color: var(--primary);">
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    Sign In &rarr;
                </button>
            </form>
        </div>

        <p style="text-align: center; color: var(--text-muted); font-size: 0.775rem; margin-top: 20px;">
            Civil Engineering Infrastructure &bull; Secure Intranet Portal
        </p>
    </div>
@endsection