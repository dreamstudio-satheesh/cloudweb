<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Redirect based on role
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        
        // For clients, redirect to servers list
        return redirect()->route('servers.index');
    }
}