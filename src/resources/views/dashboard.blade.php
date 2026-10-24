@extends('layouts.app')

@section('title', 'Dashboard — Civil Indent Portal')

@push('styles')
<style>
    .dashboard-header {
        margin-bottom: 24px;
    }

    .dashboard-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-top: 16px;
    }

    .profile-item {
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 12px 16px;
    }

    .profile-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }

    .profile-value {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-color);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-active {
        background: #dcfce7;
        color: #15803d;
    }

    .table-container {
        margin-top: 24px;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    th {
        text-align: left;
        padding: 12px 14px;
        background: #f1f5f9;
        color: var(--text-muted);
        font-weight: 600;
        border-bottom: 1px solid var(--border-color);
    }

    td {
        padding: 12px 14px;
        border-bottom: 1px solid var(--border-color);
    }

    tr:hover td {
        background: #f8fafc;
    }
</style>
@endpush

@section('content')
<div class="dashboard-header">
    <h1 class="dashboard-title">Welcome back, {{ $user->name }}!</h1>
    <p style="color: var(--text-muted);">You are logged in to the Civil Indent Portal.</p>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 1.125rem; font-weight: 600;">Your Account Profile</h2>
        <span class="status-badge status-active">
            ● Active Account
        </span>
    </div>

    <div class="profile-grid">
        <div class="profile-item">
            <div class="profile-label">CPF Number</div>
            <div class="profile-value">{{ $user->cpf }}</div>
        </div>

        <div class="profile-item">
            <div class="profile-label">Designation</div>
            <div class="profile-value">{{ $user->designation }}</div>
        </div>

        <div class="profile-item">
            <div class="profile-label">Location / Site</div>
            <div class="profile-value">{{ $user->location }}</div>
        </div>

        <div class="profile-item">
            <div class="profile-label">Email Address</div>
            <div class="profile-value">{{ $user->email ?? 'Not assigned' }}</div>
        </div>
    </div>
</div>

@if($user->isSuperAdmin())
    <div class="card" style="margin-top: 24px; background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%); border: 1px solid #bfdbfe;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #1e3a8a;">⚙️ Super Admin Control Center</h3>
                <p style="color: #475569; font-size: 0.875rem; margin-top: 4px;">
                    You have system administrator privileges to configure Master Data (Areas, Installations) and verify Role Scopes (§3).
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="{{ route('admin.areas.index') }}" class="btn btn-primary" style="font-size: 0.875rem;">
                    🗺️ Manage Master Data
                </a>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-outline" style="background: white; font-size: 0.875rem;">
                    🛡️ View Roles (§3)
                </a>
            </div>
        </div>
    </div>
@endif

@if(config('auth.auth_mode') === 'dev' && $seededUsers->isNotEmpty())
    <div class="card" style="margin-top: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 600; color: #b91c1c;">
                    🛠️ Dev Mode: Seeded Accounts ({{ $seededUsers->count() }})
                </h3>
                <p style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 2px;">
                    In dev mode, any of these active CPF numbers can log in using password <code>{{ config('auth.dev_password', 'secret123') }}</code>.
                </p>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>CPF</th>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Location</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($seededUsers as $u)
                        <tr style="{{ $u->id === $user->id ? 'background: #eff6ff;' : '' }}">
                            <td><strong><code>{{ $u->cpf }}</code></strong></td>
                            <td>{{ $u->name }}</td>
                            <td><span style="font-weight: 600;">{{ $u->designation }}</span></td>
                            <td>{{ $u->location }}</td>
                            <td style="color: var(--text-muted);">{{ $u->email ?? '-' }}</td>
                            <td>
                                <span class="status-badge {{ $u->active ? 'status-active' : 'status-danger' }}">
                                    {{ $u->active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
