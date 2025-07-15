@extends('layouts.app')

@section('title', 'Billing')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Billing & Usage</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Manage your billing and view usage details</p>
    </div>

    <!-- Current Month Usage -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Current Month Usage</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">July 2024</span>
            </div>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <!-- Servers -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Servers</h4>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">€{{ number_format(collect($currentUsage['servers'])->sum('price'), 2) }}</span>
                    </div>
                    <div class="space-y-2">
                        @foreach($currentUsage['servers'] as $server)
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400">{{ $server['name'] }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-500">({{ $server['type'] }}, {{ $server['location'] }})</span>
                            </div>
                            <span class="text-gray-900 dark:text-white">€{{ number_format($server['price'], 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Volumes -->
                @if(count($currentUsage['volumes']) > 0)
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Volumes</h4>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">€{{ number_format(collect($currentUsage['volumes'])->sum('price'), 2) }}</span>
                    </div>
                    <div class="space-y-2">
                        @foreach($currentUsage['volumes'] as $volume)
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10a2 2 0 002 2h12a2 2 0 002-2V7M4 7l8-4 8 4M4 7l8 4m0 0l8-4m-8 4v10"></path>
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400">{{ $volume['name'] }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-500">({{ $volume['size'] }}GB, {{ $volume['location'] }})</span>
                            </div>
                            <span class="text-gray-900 dark:text-white">€{{ number_format($volume['price'], 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Snapshots -->
                @if(count($currentUsage['snapshots']) > 0)
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Snapshots</h4>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">€{{ number_format(collect($currentUsage['snapshots'])->sum('price'), 2) }}</span>
                    </div>
                    <div class="space-y-2">
                        @foreach($currentUsage['snapshots'] as $snapshot)
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400">{{ $snapshot['name'] }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-500">({{ $snapshot['size'] }}GB)</span>
                            </div>
                            <span class="text-gray-900 dark:text-white">€{{ number_format($snapshot['price'], 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Total -->
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-base font-medium text-gray-900 dark:text-white">Total Monthly Cost</span>
                        <span class="text-xl font-bold text-gray-900 dark:text-white">€{{ number_format($currentUsage['total'], 2) }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Billed on the 1st of each month</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Invoices -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Invoices</h3>
                <a href="{{ route('billing.invoices') }}" class="text-sm text-primary hover:text-primary/80">View All</a>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($invoices as $invoice)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $invoice['id'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($invoice['date'])->format('M d, Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">€{{ number_format($invoice['amount'], 2) }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $invoice['status'] === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' }}">
                                {{ ucfirst($invoice['status']) }}
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Payment Method -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Payment Method</h3>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-8 bg-gradient-to-r from-blue-600 to-blue-400 rounded flex items-center justify-center">
                            <span class="text-white text-xs font-bold">VISA</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">•••• •••• •••• {{ $paymentMethod['last4'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Expires {{ $paymentMethod['expires'] }}</p>
                        </div>
                    </div>
                    <button class="text-sm text-primary hover:text-primary/80">Change</button>
                </div>
                
                <div class="mt-4 space-y-3">
                    <div class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-400">Automatic monthly billing enabled</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-400">Invoice emails enabled</span>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button class="w-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        Update Billing Settings
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage History Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Usage History</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @foreach($usageHistory as $history)
                <div class="flex items-center">
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $history['month'] }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">€{{ number_format($history['amount'], 2) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width: {{ ($history['amount'] / 1000) * 100 }}%"></div>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $history['servers'] }} servers</span>
                            <span class="text-xs {{ $history['status'] === 'current' ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ ucfirst($history['status']) }}
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Cost Optimization Tips -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Cost Optimization Tips</h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Turn off development servers when not in use</li>
                        <li>Use snapshots instead of keeping idle servers running</li>
                        <li>Consider ARM-based servers for compatible workloads (up to 40% savings)</li>
                        <li>Delete old snapshots and volumes you no longer need</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection