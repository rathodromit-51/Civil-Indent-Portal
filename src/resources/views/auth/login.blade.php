@extends('layouts.app')

@section('title', 'Sign In — Civil Indent Portal')

@push('styles')
<style>
    .login-wrapper {
        max-width: 440px;
        margin: 40px auto;
    }

    .login-header {
        text-align: center;
        margin-bottom: 24px;
    }

    .login-header h1 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .login-header p {
        color: var(--text-muted);
        font-size: 0.875rem;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        font-size: 0.875rem;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        background-color: #fff;
        color: var(--text-color);
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .form-control.is-invalid {
        border-color: var(--danger);
    }

    .invalid-feedback {
        color: var(--danger);
        font-size: 0.75rem;
        margin-top: 4px;
    }

    .remember-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        font-size: 0.875rem;
    }
</style>
@endpush

@section('content')
<div class="login-wrapper">
    <div class="login-header">
        <h1>Civil Indent Portal</h1>
        <p>Sign in with your <strong>CPF (Unique String ID)</strong> to access your account</p>
    </div>

    <div class="card">
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
                <input
                    type="text"
                    id="cpf"
                    name="cpf"
                    value="{{ old('cpf') }}"
                    class="form-control @error('cpf') is-invalid @enderror"
                    placeholder="Enter your unique CPF (e.g. ADMIN001)"
                    required
                    autofocus
                    autocomplete="username"
                >
                @error('cpf')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Enter password"
                    required
                    autocomplete="current-password"
                >
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="remember-row">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                Sign In
            </button>
        </form>
    </div>
</div>
@endsection
