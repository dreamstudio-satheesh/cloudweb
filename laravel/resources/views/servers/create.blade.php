@extends('layouts.app')

@section('title', 'Create Server')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create New Server</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Deploy a new cloud server in seconds</p>
    </div>

    <form action="{{ route('servers.store') }}" method="POST" x-data="serverForm()">
        @csrf
        
        <!-- Server Name -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Server Details</h3>
            
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Server Name</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white"
                           placeholder="my-server-01"
                           value="{{ old('name') }}">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Use lowercase letters, numbers, and hyphens only</p>
                </div>
            </div>
        </div>

        <!-- Location Selection -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Location</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($locations as $location)
                <label class="relative cursor-pointer">
                    <input type="radio" name="location" value="{{ $location->id }}" x-model="selectedLocation" class="sr-only peer" {{ old('location') == $location->id ? 'checked' : '' }}>
                    <div class="border-2 border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:border-primary peer-checked:border-primary peer-checked:bg-primary/5 transition-all duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">{{ $location->flag }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $location->city }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $location->country }}</p>
                            </div>
                        </div>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Server Type Selection -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Server Type</h3>
            
            <div class="mb-4">
                <div class="flex space-x-2 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button type="button" @click="serverCategory = 'shared'" 
                            :class="serverCategory === 'shared' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                            class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition-all duration-200 text-gray-700 dark:text-gray-300">
                        Shared vCPU
                    </button>
                    <button type="button" @click="serverCategory = 'dedicated'" 
                            :class="serverCategory === 'dedicated' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                            class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition-all duration-200 text-gray-700 dark:text-gray-300">
                        Dedicated vCPU
                    </button>
                    <button type="button" @click="serverCategory = 'arm'" 
                            :class="serverCategory === 'arm' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                            class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition-all duration-200 text-gray-700 dark:text-gray-300">
                        ARM
                    </button>
                </div>
            </div>

            <div class="space-y-3">
                <template x-for="type in filteredServerTypes" :key="type.id">
                    <label class="relative cursor-pointer block">
                        <input type="radio" name="server_type" :value="type.id" x-model="selectedType" class="sr-only peer">
                        <div class="border-2 border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:border-primary peer-checked:border-primary peer-checked:bg-primary/5 transition-all duration-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white" x-text="type.name"></h4>
                                    <div class="flex items-center space-x-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        <span x-text="`${type.cores} vCPU${type.cores > 1 ? 's' : ''}`"></span>
                                        <span x-text="`${type.ram}GB RAM`"></span>
                                        <span x-text="`${type.disk}GB ${type.disk_type}`"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="`€${type.price}/mo`"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="`€${(type.price / 730).toFixed(3)}/hour`"></p>
                                </div>
                            </div>
                        </div>
                    </label>
                </template>
            </div>
        </div>

        <!-- Additional Options -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Additional Options</h3>
            
            <div class="space-y-4">
                <!-- SSH Keys -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SSH Keys</label>
                    <div class="space-y-2">
                        @foreach($sshKeys as $key)
                        <label class="flex items-center">
                            <input type="checkbox" name="ssh_keys[]" value="{{ $key->id }}" 
                                   class="rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $key->name }}</span>
                        </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Select SSH keys to add to the server</p>
                </div>

                <!-- Backups -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="enable_backups" value="1" 
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Enable Automated Backups</span>
                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">(+20% of server cost)</span>
                    </label>
                </div>

                <!-- IPv6 -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="enable_ipv6" value="1" checked
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Enable IPv6</span>
                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">(Free)</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Summary and Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Summary</h3>
            
            <div class="space-y-2 mb-6">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Server Type</span>
                    <span class="font-medium text-gray-900 dark:text-white" x-text="selectedTypeDetails?.name || 'Not selected'"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Location</span>
                    <span class="font-medium text-gray-900 dark:text-white" x-text="selectedLocationDetails?.city || 'Not selected'"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Base Price</span>
                    <span class="font-medium text-gray-900 dark:text-white" x-text="selectedTypeDetails ? `€${selectedTypeDetails.price}/mo` : '€0/mo'"></span>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-700 pt-2 mt-2">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-900 dark:text-white">Total Monthly Cost</span>
                        <span class="font-semibold text-lg text-gray-900 dark:text-white" x-text="totalCost"></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('servers.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-primary hover:bg-primary/90 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200"
                        :disabled="!selectedType || !selectedLocation">
                    Create Server
                </button>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function serverForm() {
    return {
        serverCategory: 'shared',
        selectedType: '{{ old('server_type') }}',
        selectedLocation: '{{ old('location') }}',
        enableBackups: false,
        
        serverTypes: @json($serverTypes ?? []),
        locations: @json($locations ?? []),
        
        get filteredServerTypes() {
            return this.serverTypes.filter(type => type.category === this.serverCategory);
        },
        
        get selectedTypeDetails() {
            return this.serverTypes.find(type => type.id == this.selectedType);
        },
        
        get selectedLocationDetails() {
            return this.locations.find(loc => loc.id == this.selectedLocation);
        },
        
        get totalCost() {
            if (!this.selectedTypeDetails) return '€0/mo';
            let cost = this.selectedTypeDetails.price;
            if (this.enableBackups) cost *= 1.2;
            return `€${cost.toFixed(2)}/mo`;
        }
    }
}
</script>
@endsection