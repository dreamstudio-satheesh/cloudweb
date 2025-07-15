<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Cloud Hosting') }} - Create Account</title>
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
        <!-- Left side - Visual/Branding -->
        <div class="hidden lg:block relative flex-1">
            <div class="absolute inset-0 bg-gradient-to-br from-green-500 via-blue-500 to-purple-600">
                <div class="absolute inset-0 bg-black opacity-20"></div>
                <div class="relative h-full flex items-center justify-center px-20">
                    <div class="text-center text-white">
                        <div class="mb-8">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-white/10 backdrop-blur-sm rounded-full mb-6">
                                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        </div>
                        <h1 class="text-4xl font-bold mb-6">
                            Start Your Cloud Journey
                        </h1>
                        <p class="text-xl text-white/90 mb-8 leading-relaxed">
                            Join thousands of developers and businesses running their infrastructure on our platform.
                        </p>
                        <div class="space-y-4 text-left">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-white/90">Free €20 credit for new accounts</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-white/90">No credit card required to start</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-white/90">Deploy your first server in 30 seconds</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right side - Form -->
        <div class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <div class="flex justify-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                        </div>
                    </div>
                    <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
                        Create your account
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Start deploying cloud servers in minutes
                    </p>
                </div>

                <div class="mt-8">
                    <form class="space-y-6" action="{{ route('register') }}" method="POST">
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
                                            Please fix the following errors
                                        </h3>
                                        <div class="mt-1 text-sm text-red-700">
                                            <ul class="list-disc pl-5 space-y-1">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Full name</label>
                            <div class="mt-1">
                                <input id="name" name="name" type="text" autocomplete="name" required 
                                       value="{{ old('name') }}"
                                       class="block w-full appearance-none rounded-lg border border-gray-300 px-3 py-3 placeholder-gray-400 shadow-sm focus:border-primary focus:outline-none focus:ring-primary sm:text-sm transition-colors duration-200" 
                                       placeholder="Enter your full name">
                            </div>
                        </div>

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
                                <input id="password" name="password" type="password" autocomplete="new-password" required 
                                       class="block w-full appearance-none rounded-lg border border-gray-300 px-3 py-3 placeholder-gray-400 shadow-sm focus:border-primary focus:outline-none focus:ring-primary sm:text-sm transition-colors duration-200" 
                                       placeholder="Create a password">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters long</p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password</label>
                            <div class="mt-1">
                                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required 
                                       class="block w-full appearance-none rounded-lg border border-gray-300 px-3 py-3 placeholder-gray-400 shadow-sm focus:border-primary focus:outline-none focus:ring-primary sm:text-sm transition-colors duration-200" 
                                       placeholder="Confirm your password">
                            </div>
                        </div>

                        <div class="flex items-start">
                            <input id="terms" name="terms" type="checkbox" required
                                   class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                            <label for="terms" class="ml-2 block text-sm text-gray-700">
                                I agree to the <a href="#" class="text-primary hover:text-secondary">Terms of Service</a> 
                                and <a href="#" class="text-primary hover:text-secondary">Privacy Policy</a>
                            </label>
                        </div>

                        <div>
                            <button type="submit" 
                                    class="group relative flex w-full justify-center rounded-lg bg-gradient-to-r from-green-500 to-blue-500 py-3 px-4 text-sm font-semibold text-white shadow-sm hover:from-blue-500 hover:to-green-500 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-all duration-200 transform hover:scale-[1.02]">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-white/70 group-hover:text-white" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z" />
                                    </svg>
                                </span>
                                Create your account
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="text-sm text-gray-600">
                                Already have an account?
                                <a href="{{ route('login') }}" class="font-semibold text-primary hover:text-secondary transition-colors duration-200">
                                    Sign in here
                                </a>
                            </p>
                        </div>
                    </form>
                </div>

                <!-- Benefits -->
                <div class="mt-8 border-t border-gray-200 pt-6">
                    <div class="text-center text-xs text-gray-500">
                        <p>✓ No setup fees &nbsp; ✓ Cancel anytime &nbsp; ✓ 24/7 support</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>