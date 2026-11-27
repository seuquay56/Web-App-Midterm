@extends('layouts.app')

@section('title', 'Sign in')

@section('navbar')
    <a href="{{ route('register') }}"
       class="btn btn-gc-outline rounded-pill px-3 px-md-4 py-2 fw-semibold">
        Sign up
    </a>
@endsection

@section('content')

@if (auth()->check()) 
    route('home')
@endif

<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center px-3 py-5">

    <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">

        {{-- Login Card --}}
        <div class="card bg-dark border border-secondary rounded-4 shadow-none"
             data-bs-theme="dark">

            <div class="card-body p-4 p-md-5">

                {{-- Heading --}}
                <div class="text-center mb-4">

                    <h2 class="fw-bold text-light mb-2">
                        Welcome Back
                    </h2>

                    <p class="text-secondary mb-0">
                        Sign in to continue to your notes.
                    </p>

                </div>

                {{-- Login Form --}}
                <form action="/login" method="POST">

                    @csrf

                    {{-- Email --}}
                    <div class="mb-3">

                        <label for="email"
                               class="form-label text-light fw-semibold small">
                            Email Address
                        </label>

                        <div class="input-group">

                            <span class="input-group-text bg-black border-secondary text-secondary">
                                <svg width="18" height="18" viewBox="0 0 24 24"
                                     fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="3" y="5" width="18" height="14" rx="2"
                                          stroke="currentColor" stroke-width="1.7"/>
                                    <path d="m4 7 8 6 8-6"
                                          stroke="currentColor" stroke-width="1.7"
                                          stroke-linecap="round"/>
                                </svg>
                            </span>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control bg-black text-light border-secondary py-2"
                                placeholder="Enter your email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                            >

                        </div>

                        @error('email')
                            <div class="text-danger small mt-2">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    {{-- Password --}}
                    <div class="mb-4">

                        <label for="password"
                               class="form-label text-light fw-semibold small">
                            Password
                        </label>

                        <div class="input-group">

                            <span class="input-group-text bg-black border-secondary text-secondary">
                                <svg width="18" height="18" viewBox="0 0 24 24"
                                     fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="4" y="10" width="16" height="11" rx="2"
                                          stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3"
                                          stroke="currentColor" stroke-width="1.7"
                                          stroke-linecap="round"/>
                                    <circle cx="12" cy="15.5" r="1"
                                            fill="currentColor"/>
                                </svg>
                            </span>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control bg-black text-light border-secondary py-2"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                            >

                        </div>

                        @error('password')
                            <div class="text-danger small mt-2">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    {{-- Forgot Password --}}
                    <div class="text-end mb-4">

                        <a href="{{ route('password.request') }}"
                           class="text-link text-secondary small text-decoration-none">
                            Forgot password?
                        </a>

                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            class="btn btn-gc-primary w-100 rounded-3 py-2 fw-bold">
                        Sign In
                    </button>

                </form>

                {{-- Register --}}
                <div class="text-center mt-4">

                    <p class="text-link small mb-0">
                        Don't have an account?

                        <a href="{{ route('register') }}"
                           class="text-link-decor fw-semibold ">
                            Create an account
                        </a>
                    </p>

                </div>

            </div>

        </div>
    </div>
</div>

@endsection
