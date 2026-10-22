<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard.
     */
    public function index(): View
    {
        $user = Auth::user();
        $seededUsers = config('auth.auth_mode') === 'dev'
            ? User::orderBy('id')->get()
            : collect();

        return view('dashboard', [
            'user' => $user,
            'seededUsers' => $seededUsers,
        ]);
    }
}
