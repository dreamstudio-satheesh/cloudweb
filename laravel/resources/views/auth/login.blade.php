<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Cloud Hosting') }} - Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5',
                        secondary: '#6366F1'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Left side - Form -->
        <div class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <div class="flex justify-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-primary to-secondary rounded-lg flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path>
                            </svg>
                        </div>
                    </div>
                    <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
                        Welcome back
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Sign in to your cloud hosting account
                    </p>
                </div>

                <div class="mt-8">
                    <form class="space-y-6" action="{{ route('login') }}" method="POST">
                        @csrf
                        
                        @if ($errors->any())
                            <div class="rounded-lg bg-red-50 p-4 border-l-4 border-red-400">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">
                                            Authentication failed
                                        </h3>
                                        <div class="mt-1 text-sm text-red-700">
                                            @foreach ($errors->all() as $error)
                                                <p>{{ $error }}</p>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                            <div class="mt-1">
                                <input id="email" name="email" type="email" autocomplete="email" required 
                                       value="{{ old('email') }}"
                                       class="block w-full appearance-none rounded-lg border border-gray-300 px-3 py-3 placeholder-gray-400 shadow-sm focus:border-primary focus:outline-none focus:ring-primary sm:text-sm transition-colors duration-200" 
                                       placeholder="Enter your email">
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <div class="mt-1">
                                <input id="password" name="password" type="password" autocomplete="current-password" required 
                                       class="block w-full appearance-none rounded-lg border border-gray-300 px-3 py-3 placeholder-gray-400 shadow-sm focus:border-primary focus:outline-none focus:ring-primary sm:text-sm transition-colors duration-200" 
                                       placeholder="Enter your password">
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input id="remember" name="remember" type="checkbox" 
                                       class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                                <label for="remember" class="ml-2 block text-sm text-gray-700">
                                    Remember me
                                </label>
                            </div>

                            <div class="text-sm">
                                <a href="#" class="font-medium text-primary hover:text-secondary transition-colors duration-200">
                                    Forgot your password?
                                </a>
                            </div>
                        </div>

                        <div>
                            <button type="submit" 
                                    class="group relative flex w-full justify-center rounded-lg bg-gradient-to-r from-primary to-secondary py-3 px-4 text-sm font-semibold text-white shadow-sm hover:from-secondary hover:to-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-all duration-200 transform hover:scale-[1.02]">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-white/70 group-hover:text-white" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                Sign in to your account
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="text-sm text-gray-600">
                                Don't have an account?
                                <a href="{{ route('register') }}" class="font-semibold text-primary hover:text-secondary transition-colors duration-200">
                                    Start your free trial
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right side - Visual/Branding -->
        <div class="hidden lg:block relative flex-1">
            <div class="absolute inset-0 bg-gradient-to-br from-primary via-secondary to-purple-600">
                <div class="absolute inset-0 bg-black opacity-20"></div>
                <div class="relative h-full flex items-center justify-center px-20">
                    <div class="text-center text-white">
                        <div class="mb-8">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-white/10 backdrop-blur-sm rounded-full mb-6">
                                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                                </svg>
                            </div>
                        </div>
                        <h1 class="text-4xl font-bold mb-6">
                            Cloud Hosting Platform
                        </h1>
                        <p class="text-xl text-white/90 mb-8 leading-relaxed">
                            Deploy and manage your cloud servers with ease. Powered by Hetzner Cloud infrastructure.
                        </p>
                        <div class="space-y-4 text-left">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-white/90">High-performance cloud servers</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-white/90">99.9% uptime SLA guarantee</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-white/90">Simple, transparent pricing</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>