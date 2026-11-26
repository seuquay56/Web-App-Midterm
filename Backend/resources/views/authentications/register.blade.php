
@extends('layouts.app')

@section('title', 'Register')

@section('navbar')
    <a href="{{ route('login') }}"
       class="btn btn-gc-outline rounded-pill px-3 px-md-4 py-2 fw-semibold">
        Sign in
    </a>
@endsection

@section('content')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center px-3 py-5">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">

        <div class="card bg-dark border border-secondary rounded-4 shadow-none"
             data-bs-theme="dark">

            <div class="card-body p-4 p-md-5">

                {{-- Header --}}
                <div class="text-center mb-4">

                    <h2 class="fw-bold text-light mb-2">Create Account</h2>
                    <p class="text-secondary mb-0">
                        Sign up to start organizing your notes.
                    </p>
                </div>

                <form action="/register" method="POST">
                    @csrf

                    {{-- Name --}}
                    <div class="mb-3">
                        <label for="name"
                               class="form-label text-light fw-semibold small">
                            Full Name
                        </label>

                        <div class="input-group">
                            <span class="input-group-text bg-black border-secondary text-secondary">
                                <svg width="18" height="18" viewBox="0 0 24 24"
                                     fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="8" r="4"
                                            stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M4 20c0-4 3.5-6 8-6s8 2 8 6"
                                          stroke="currentColor" stroke-width="1.7"
                                          stroke-linecap="round"/>
                                </svg>
                            </span>

                            <input type="text"
                                   id="name"
                                   name="name"
                                   class="form-control bg-black text-light border-secondary py-2"
                                   placeholder="Enter your name"
                                   value="{{ old('name') }}"
                                   autocomplete="name">
                        </div>

                        @error('name')
                            <div class="text-danger small mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

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
                                          stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>

                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-control bg-black text-light border-secondary py-2"
                                   placeholder="Enter your email"
                                   value="{{ old('email') }}"
                                   autocomplete="email">
                        </div>

                        @error('email')
                            <div class="text-danger small mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="mb-3">
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
                                </svg>
                            </span>

                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control bg-black text-light border-secondary py-2"
                                   placeholder="Create a password"
                                   autocomplete="new-password">
                        </div>

                        @error('password')
                            <div class="text-danger small mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div class="mb-4">
                        <label for="password_confirmation"
                               class="form-label text-light fw-semibold small">
                            Confirm Password
                        </label>

                        <div class="input-group">
                            <span class="input-group-text bg-black border-secondary text-secondary">
                                <svg width="18" height="18" viewBox="0 0 24 24"
                                     fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 12.5 10 17l9-10"
                                          stroke="currentColor" stroke-width="1.8"
                                          stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>

                            <input type="password"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   class="form-control bg-black text-light border-secondary py-2"
                                   placeholder="Confirm your password"
                                   autocomplete="new-password">
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            class="btn btn-gc-primary w-100 rounded-3 py-2 fw-bold">
                        Create Account
                    </button>
                </form>

                {{-- Login Link --}}
                <div class="text-center mt-4">
                    <p class="text-secondary small mb-0">
                        Already have an account?
                        <a href="{{ route('login') }}"
                           class="text-link-decor fw-semibold">
                            Sign in
                        </a>
                    </p>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection
