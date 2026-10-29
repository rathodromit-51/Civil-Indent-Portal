<?php

namespace App\Http\Controllers;

use App\Models\Indent;
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

        // Actionable KPI counts for logged-in user
        $workQueueCount = Indent::withMe($user->cpf)->count();
        $myDraftsCount = Indent::where('creator_cpf', $user->cpf)->drafts()->count();
        $myCreatedCount = Indent::where('creator_cpf', $user->cpf)->count();
        $flaggedCount = Indent::where(function ($q) use ($user) {
            $q->where('creator_cpf', $user->cpf)
              ->orWhere('current_holder_cpf', $user->cpf);
        })->flagged()->count();

        // Pending requisitions in Work Queue awaiting this user's action
        $pendingWorkQueue = Indent::withMe($user->cpf)
            ->with(['installation.area', 'creator', 'latestMovement'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        // Recent requisitions created by this user
        $recentMyIndents = Indent::where('creator_cpf', $user->cpf)
            ->with(['installation', 'latestMovement'])
            ->latest('created_at')
            ->take(5)
            ->get();

        // Seeded accounts for dev mode
        $seededUsers = config('auth.auth_mode') === 'dev'
            ? User::orderBy('id')->get()
            : collect();

        return view('dashboard', [
            'user' => $user,
            'workQueueCount' => $workQueueCount,
            'myDraftsCount' => $myDraftsCount,
            'myCreatedCount' => $myCreatedCount,
            'flaggedCount' => $flaggedCount,
            'pendingWorkQueue' => $pendingWorkQueue,
            'recentMyIndents' => $recentMyIndents,
            'seededUsers' => $seededUsers,
            'withMeCount' => $workQueueCount,
        ]);
    }
}
