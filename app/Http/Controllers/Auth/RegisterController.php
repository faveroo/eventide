<?php

namespace App\Http\Controllers\Auth;

use App\Data\Auth\RegisterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create(RegisterData::from($request->validated())->toArray());
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/');
    }

    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }
}
