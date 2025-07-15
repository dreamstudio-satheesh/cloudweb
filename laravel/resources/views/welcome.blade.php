<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Cloud Hosting') }} - Deploy Servers in Seconds</title>
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
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-r from-primary to-secondary rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path>
                            </svg>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">{{ config('app.name', 'Cloud Hosting') }}</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('login') }}" class="text-gray-700 hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Sign in
                    </a>
                    <a href="{{ route('register') }}" class="bg-gradient-to-r from-primary to-secondary hover:from-secondary hover:to-primary text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 transform hover:scale-105">
                        Get Started
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-secondary/5"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
            <div class="text-center">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-gray-900">
                    Deploy Cloud Servers
                    <span class="block text-transparent bg-clip-text bg-gradient-to-r from-primary to-secondary">
                        In Seconds, Not Hours
                    </span>
                </h1>
                <p class="mt-6 max-w-2xl mx-auto text-xl text-gray-600">
                    Powerful cloud infrastructure at your fingertips. Deploy, manage, and scale your servers with our intuitive platform powered by Hetzner Cloud.
                </p>
                <div class="mt-10 flex justify-center space-x-4">
                    <a href="{{ route('register') }}" class="bg-gradient-to-r from-primary to-secondary hover:from-secondary hover:to-primary text-white px-8 py-3 rounded-lg text-lg font-semibold transition-all duration-200 transform hover:scale-105 shadow-lg">
                        Start Free Trial
                    </a>
                    <a href="#features" class="bg-white hover:bg-gray-50 text-gray-900 px-8 py-3 rounded-lg text-lg font-semibold transition-colors duration-200 border border-gray-300">
                        Learn More
                    </a>
                </div>
                <p class="mt-4 text-sm text-gray-500">
                    €20 free credit • No credit card required
                </p>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Everything You Need</h2>
                <p class="mt-4 text-xl text-gray-600">Powerful features to help you deploy and manage your infrastructure</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-gray-50 rounded-xl p-6 hover:shadow-lg transition-shadow duration-300">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-green-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Lightning Fast</h3>
                    <p class="text-gray-600">Deploy new servers in under 30 seconds. Powered by NVMe SSDs and latest-gen processors.</p>
                </div>

                <div class="bg-gray-50 rounded-xl p-6 hover:shadow-lg transition-shadow duration-300">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-blue-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Enterprise Security</h3>
                    <p class="text-gray-600">Built-in DDoS protection, firewalls, and private networks. Your data is always secure.</p>
                </div>

                <div class="bg-gray-50 rounded-xl p-6 hover:shadow-lg transition-shadow duration-300">
                    <div class="w-12 h-12 bg-gradient-to-r from-purple-400 to-purple-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Auto Scaling</h3>
                    <p class="text-gray-600">Scale your servers up or down based on demand. Pay only for what you use.</p>
                </div>

                <div class="bg-gray-50 rounded-xl p-6 hover:shadow-lg transition-shadow duration-300">
                    <div class="w-12 h-12 bg-gradient-to-r from-orange-400 to-orange-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Global Network</h3>
                    <p class="text-gray-600">Deploy in multiple data centers across Europe and the US. Low latency worldwide.</p>
                </div>

                <div class="bg-gray-50 rounded-xl p-6 hover:shadow-lg transition-shadow duration-300">
                    <div class="w-12 h-12 bg-gradient-to-r from-red-400 to-red-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Automated Backups</h3>
                    <p class="text-gray-600">Schedule automatic backups and snapshots. Restore your servers with one click.</p>
                </div>

                <div class="bg-gray-50 rounded-xl p-6 hover:shadow-lg transition-shadow duration-300">
                    <div class="w-12 h-12 bg-gradient-to-r from-indigo-400 to-indigo-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Simple Pricing</h3>
                    <p class="text-gray-600">Transparent, predictable pricing. No hidden fees. Pay by the hour or month.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Simple, Transparent Pricing</h2>
                <p class="mt-4 text-xl text-gray-600">Choose the right server for your needs</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Starter</h3>
                    <p class="text-gray-600 mb-4">Perfect for small projects</p>
                    <p class="text-4xl font-bold text-gray-900 mb-1">€3.29</p>
                    <p class="text-gray-600 mb-6">per month</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            1 vCPU
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            2 GB RAM
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            20 GB NVMe SSD
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            20 TB Traffic
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-900 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        Get Started
                    </a>
                </div>

                <div class="bg-white rounded-xl shadow-lg border-2 border-primary p-8 transform scale-105">
                    <div class="bg-primary text-white text-xs font-semibold uppercase px-3 py-1 rounded-full inline-block mb-4">Most Popular</div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Professional</h3>
                    <p class="text-gray-600 mb-4">Great for growing businesses</p>
                    <p class="text-4xl font-bold text-gray-900 mb-1">€11.08</p>
                    <p class="text-gray-600 mb-6">per month</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            2 vCPU
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            8 GB RAM
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            80 GB NVMe SSD
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            20 TB Traffic
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full text-center bg-gradient-to-r from-primary to-secondary hover:from-secondary hover:to-primary text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105">
                        Get Started
                    </a>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Enterprise</h3>
                    <p class="text-gray-600 mb-4">For demanding applications</p>
                    <p class="text-4xl font-bold text-gray-900 mb-1">€32.85</p>
                    <p class="text-gray-600 mb-6">per month</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            8 vCPU
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            32 GB RAM
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            240 GB NVMe SSD
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            20 TB Traffic
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-900 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-primary to-secondary">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">
                Ready to Get Started?
            </h2>
            <p class="text-xl text-white/90 mb-8">
                Join thousands of developers and businesses who trust our platform.
            </p>
            <a href="{{ route('register') }}" class="inline-block bg-white hover:bg-gray-100 text-gray-900 px-8 py-3 rounded-lg text-lg font-semibold transition-colors duration-200 transform hover:scale-105 shadow-lg">
                Start Your Free Trial
            </a>
            <p class="mt-4 text-white/80">
                €20 free credit • No credit card required • Deploy in seconds
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'Cloud Hosting') }}. All rights reserved.</p>
                <div class="mt-4 space-x-6">
                    <a href="#" class="hover:text-white transition-colors duration-200">Terms</a>
                    <a href="#" class="hover:text-white transition-colors duration-200">Privacy</a>
                    <a href="#" class="hover:text-white transition-colors duration-200">Contact</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>