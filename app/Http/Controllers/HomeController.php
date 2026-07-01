<?php

namespace App\Http\Controllers;

use App\Services\ChefEquipeSession;

class HomeController extends Controller
{
    public function index(ChefEquipeSession $chefSession)
    {
        if ($chefSession->check()) {
            return redirect()->route('dashboard');
        }

        return view('login');
    }
}
