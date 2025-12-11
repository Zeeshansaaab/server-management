<template>
    <AppLayout>
        <div class="px-4 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-3 mb-2">
                            <h1 class="text-4xl font-bold text-slate-900 dark:text-slate-100">{{ server.name }}</h1>
                            <span
                                :class="server.is_online 
                                    ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 border-emerald-300 dark:border-emerald-700' 
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-400 border-slate-300 dark:border-slate-700'"
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border"
                            >
                                <span :class="server.is_online ? 'bg-emerald-500' : 'bg-slate-400'" class="w-2 h-2 rounded-full mr-2 animate-pulse"></span>
                                {{ server.is_online ? 'Online' : 'Offline' }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-4 text-sm text-slate-600 dark:text-slate-400">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                                {{ server.hostname }}
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                {{ server.ip_address }}
                            </div>
                            <div v-if="server.os" class="flex items-center">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                </svg>
                                {{ server.os }}
                            </div>
                            <div v-if="server.last_seen" class="flex items-center">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Last seen: {{ formatTimeAgo(server.last_seen) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Server Info Cards -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                <!-- SSH Connection Info -->
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">SSH Connection</h3>
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="space-y-2">
                        <div class="text-sm text-slate-600 dark:text-slate-400">
                            <span class="font-medium">User:</span> {{ server.ssh_user || 'root' }}
                        </div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">
                            <span class="font-medium">Port:</span> {{ server.ssh_port || 22 }}
                        </div>
                        <div v-if="server.last_seen" class="text-xs text-slate-500 dark:text-slate-500 mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                            Last seen: {{ new Date(server.last_seen).toLocaleString() }}
                        </div>
                    </div>
                </div>

                <!-- CPU Info -->
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">CPU</h3>
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </div>
                    <div v-if="server.latest_metric">
                        <div class="text-3xl font-bold text-amber-600 dark:text-amber-400 mb-2">
                            {{ server.latest_metric.cpu_usage ? server.latest_metric.cpu_usage.toFixed(1) + '%' : 'N/A' }}
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 mb-2">
                            <div 
                                :class="getCpuColor(server.latest_metric.cpu_usage)"
                                class="h-2 rounded-full transition-all duration-300"
                                :style="{ width: (server.latest_metric.cpu_usage || 0) + '%' }"
                            ></div>
                        </div>
                        <div v-if="server.latest_metric.load_average_1m" class="text-xs text-slate-500 dark:text-slate-500">
                            Load: {{ server.latest_metric.load_average_1m.toFixed(2) }}
                        </div>
                        <div v-if="server.cpu_info" class="text-xs text-slate-500 dark:text-slate-500 mt-1">
                            {{ server.cpu_info }}
                        </div>
                    </div>
                    <div v-else class="text-slate-400 dark:text-slate-600">No data available</div>
                </div>

                <!-- Memory Info -->
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Memory</h3>
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                        </svg>
                    </div>
                    <div v-if="server.latest_metric">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-2">
                            {{ formatBytes((server.latest_metric.memory_used_mb || 0) * 1024 * 1024) }} / {{ formatBytes((server.latest_metric.memory_total_mb || 0) * 1024 * 1024) }}
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 mb-2">
                            <div 
                                :class="getMemoryColor(server.latest_metric.memory_used_mb, server.latest_metric.memory_total_mb)"
                                class="h-2 rounded-full transition-all duration-300"
                                :style="{ width: getMemoryPercent(server.latest_metric.memory_used_mb, server.latest_metric.memory_total_mb) + '%' }"
                            ></div>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-500">
                            {{ getMemoryPercent(server.latest_metric.memory_used_mb, server.latest_metric.memory_total_mb).toFixed(1) }}% used
                        </div>
                    </div>
                    <div v-else-if="server.memory_mb" class="text-sm text-slate-600 dark:text-slate-400">
                        Total: {{ formatBytes(server.memory_mb * 1024 * 1024) }}
                    </div>
                    <div v-else class="text-slate-400 dark:text-slate-600">No data available</div>
                </div>

                <!-- Disk Info -->
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Disk</h3>
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                        </svg>
                    </div>
                    <div v-if="server.latest_metric">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mb-2">
                            {{ (server.latest_metric.disk_used_gb || 0).toFixed(1) }}GB / {{ (server.latest_metric.disk_total_gb || 0).toFixed(1) }}GB
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 mb-2">
                            <div 
                                :class="getDiskColor(server.latest_metric.disk_used_gb, server.latest_metric.disk_total_gb)"
                                class="h-2 rounded-full transition-all duration-300"
                                :style="{ width: getDiskPercent(server.latest_metric.disk_used_gb, server.latest_metric.disk_total_gb) + '%' }"
                            ></div>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-500">
                            {{ getDiskPercent(server.latest_metric.disk_used_gb, server.latest_metric.disk_total_gb).toFixed(1) }}% used
                            <span v-if="server.free_disk_gb" class="ml-2">({{ server.free_disk_gb }}GB free)</span>
                        </div>
                    </div>
                    <div v-else-if="server.free_disk_gb" class="text-sm text-slate-600 dark:text-slate-400">
                        Free: {{ server.free_disk_gb }}GB
                    </div>
                    <div v-else class="text-slate-400 dark:text-slate-600">No data available</div>
                </div>
            </div>

            <!-- Load Average Section -->
            <div v-if="server.latest_metric && (server.latest_metric.load_average_1m || server.latest_metric.load_average_5m || server.latest_metric.load_average_15m)" class="mb-8">
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Load Average</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-slate-50 dark:bg-slate-900/50 rounded-lg">
                            <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">1 minute</div>
                            <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ server.latest_metric.load_average_1m?.toFixed(2) || 'N/A' }}</div>
                        </div>
                        <div class="text-center p-4 bg-slate-50 dark:bg-slate-900/50 rounded-lg">
                            <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">5 minutes</div>
                            <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ server.latest_metric.load_average_5m?.toFixed(2) || 'N/A' }}</div>
                        </div>
                        <div class="text-center p-4 bg-slate-50 dark:bg-slate-900/50 rounded-lg">
                            <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">15 minutes</div>
                            <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ server.latest_metric.load_average_15m?.toFixed(2) || 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sites Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Sites</h2>
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 rounded-full text-sm font-medium">
                            {{ server.sites?.length || 0 }} site{{ (server.sites?.length || 0) !== 1 ? 's' : '' }}
                        </span>
                        <button
                            @click="checkStatus"
                            :disabled="isCheckingStatus"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl text-white bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-lg shadow-emerald-500/30 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="!isCheckingStatus" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ isCheckingStatus ? 'Checking...' : 'Check Status' }}
                        </button>
                        <button
                            @click="scanForSites"
                            :disabled="isScanning"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl text-white bg-gradient-to-r from-violet-500 to-purple-500 hover:from-violet-600 hover:to-purple-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 shadow-lg shadow-violet-500/30 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="!isScanning" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ isScanning ? 'Scanning...' : 'Scan for Sites' }}
                        </button>
                    </div>
                </div>
                
                <!-- Detected Sites Modal -->
                <div v-if="showDetectedSites || isScanning" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeDetectedSites">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-slate-500 bg-opacity-75 dark:bg-opacity-90" @click="closeDetectedSites"></div>
                        <div class="relative inline-block align-bottom bg-white dark:bg-slate-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                            <!-- Loading State -->
                            <div v-if="isScanning && detectedSites.length === 0" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-violet-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <h3 class="mt-4 text-lg font-semibold text-slate-900 dark:text-slate-100">Scanning for Sites...</h3>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">This may take a few moments. Please wait.</p>
                            </div>
                            <!-- Results -->
                            <template v-else>
                                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Detected Sites ({{ detectedSites.length }})</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Select sites to import into your server control panel</p>
                                </div>
                                <div class="px-6 py-4 max-h-96 overflow-y-auto">
                                    <div v-if="detectedSites.length === 0" class="text-center py-8 text-slate-500 dark:text-slate-400">
                                        No sites detected. Make sure your server has nginx/apache configured or web directories exist.
                                    </div>
                                    <div v-else class="space-y-3">
                                        <label v-for="(site, index) in detectedSites" :key="index" class="flex items-start p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer">
                                            <input type="checkbox" v-model="selectedSites" :value="index" class="mt-1 mr-3 h-4 w-4 text-violet-600 focus:ring-violet-500 border-slate-300 rounded">
                                            <div class="flex-1">
                                                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ site.domain }}</div>
                                                <div class="mt-1 text-sm text-slate-600 dark:text-slate-400 space-y-1">
                                                    <div v-if="site.web_directory">Directory: <code class="bg-slate-100 dark:bg-slate-700 px-1 rounded">{{ site.web_directory }}</code></div>
                                                    <div v-if="site.system_user">User: <code class="bg-slate-100 dark:bg-slate-700 px-1 rounded">{{ site.system_user }}</code></div>
                                                    <div v-if="site.php_version">PHP: <code class="bg-slate-100 dark:bg-slate-700 px-1 rounded">{{ site.php_version }}</code></div>
                                                    <div class="text-xs text-slate-500">Source: {{ site.source }}</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end space-x-3">
                                    <button @click="closeDetectedSites" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                                        Cancel
                                    </button>
                                    <button
                                        @click="importSelectedSites"
                                        :disabled="selectedSites.length === 0 || isImporting || detectedSites.length === 0"
                                        class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-violet-500 to-purple-500 hover:from-violet-600 hover:to-purple-600 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {{ isImporting ? 'Importing...' : `Import ${selectedSites.length} Site${selectedSites.length !== 1 ? 's' : ''}` }}
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div v-if="server.sites && server.sites.length > 0" class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
                    <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="site in server.sites" :key="site.id" class="px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <Link :href="route('sites.show', site.id)" class="text-lg font-semibold text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-colors">
                                        {{ site.domain }}
                                    </Link>
                                    <div class="mt-1 flex items-center space-x-4 text-sm text-slate-600 dark:text-slate-400">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            Branch: {{ site.repository_branch }}
                                        </span>
                                        <span v-if="site.latest_deployment" class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Deployed {{ formatTimeAgo(site.latest_deployment.created_at) }}
                                        </span>
                                    </div>
                                </div>
                                <span
                                    :class="site.is_active 
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400' 
                                        : 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-400'"
                                    class="px-3 py-1 rounded-full text-xs font-semibold"
                                >
                                    {{ site.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
                <div v-else class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">No sites</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Get started by creating a new site.</p>
                </div>
            </div>

            <!-- Databases Section -->
            <div v-if="server.databases && server.databases.length > 0" class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Databases</h2>
                    <span class="px-3 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 rounded-full text-sm font-medium">
                        {{ server.databases.length }} database{{ server.databases.length !== 1 ? 's' : '' }}
                    </span>
                </div>
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
                    <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="db in server.databases" :key="db.id" class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ db.name }}</div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ db.type || 'MySQL' }}</div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    server: Object,
});

const isScanning = ref(false);
const isCheckingStatus = ref(false);
const showDetectedSites = ref(false);
const detectedSites = ref([]);
const selectedSites = ref([]);
const isImporting = ref(false);

const checkStatus = async () => {
    isCheckingStatus.value = true;
    try {
        const response = await axios.post(route('servers.check-status', props.server.id));
        if (response.data.success) {
            router.reload();
        } else {
            alert('Status check failed: ' + (response.data.message || 'Unknown error'));
        }
    } catch (error) {
        alert('Status check failed: ' + (error.response?.data?.message || error.message));
    } finally {
        isCheckingStatus.value = false;
    }
};

const scanForSites = async () => {
    isScanning.value = true;
    showDetectedSites.value = false;
    detectedSites.value = [];
    selectedSites.value = [];
    
    try {
        // Start the scan job
        const response = await axios.post(route('servers.scan-sites', props.server.id));
        
        if (response.data.success && response.data.job_id) {
            const jobId = response.data.job_id;
            
            // Poll for job status
            const pollInterval = setInterval(async () => {
                try {
                    const statusResponse = await axios.get(route('servers.scan-sites-status', props.server.id), {
                        params: { job_id: jobId }
                    });
                    
                    if (statusResponse.data.success) {
                        const status = statusResponse.data.status;
                        
                        if (status === 'completed') {
                            clearInterval(pollInterval);
                            isScanning.value = false;
                            detectedSites.value = statusResponse.data.sites || [];
                            selectedSites.value = [];
                            // Always show modal, even if no sites found
                            showDetectedSites.value = true;
                        } else if (status === 'failed') {
                            clearInterval(pollInterval);
                            isScanning.value = false;
                            showDetectedSites.value = false;
                            alert('Scan failed: ' + (statusResponse.data.message || statusResponse.data.error || 'Unknown error'));
                        }
                        // If status is 'processing' or 'not_found', continue polling
                    }
                } catch (error) {
                    // If job not found after a while, stop polling
                    if (error.response?.status === 404) {
                        clearInterval(pollInterval);
                        isScanning.value = false;
                        alert('Scan job expired or not found. Please try again.');
                    }
                }
            }, 2000); // Poll every 2 seconds
            
            // Stop polling after 2 minutes
            setTimeout(() => {
                clearInterval(pollInterval);
                if (isScanning.value) {
                    isScanning.value = false;
                    alert('Scan is taking longer than expected. Please check back later or try again.');
                }
            }, 120000);
        } else {
            isScanning.value = false;
            alert('Failed to start scan: ' + (response.data.message || 'Unknown error'));
        }
    } catch (error) {
        isScanning.value = false;
        alert('Failed to scan for sites: ' + (error.response?.data?.message || error.message));
    }
};

const closeDetectedSites = () => {
    showDetectedSites.value = false;
    detectedSites.value = [];
    selectedSites.value = [];
};

const importSelectedSites = async () => {
    if (selectedSites.value.length === 0) return;
    
    isImporting.value = true;
    try {
        const sitesToImport = selectedSites.value.map(index => detectedSites.value[index]);
        const response = await axios.post(route('servers.import-sites', props.server.id), {
            sites: sitesToImport,
        });
        
        if (response.data.success) {
            closeDetectedSites();
            router.reload();
        }
    } catch (error) {
        alert('Failed to import sites: ' + (error.response?.data?.message || error.message));
    } finally {
        isImporting.value = false;
    }
};

const formatBytes = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const getMemoryPercent = (used, total) => {
    if (!used || !total) return 0;
    return (used / total) * 100;
};

const getDiskPercent = (used, total) => {
    if (!used || !total) return 0;
    return (used / total) * 100;
};

const getCpuColor = (usage) => {
    if (!usage) return 'bg-slate-400';
    if (usage < 50) return 'bg-emerald-500';
    if (usage < 80) return 'bg-amber-500';
    return 'bg-rose-500';
};

const getMemoryColor = (used, total) => {
    const percent = getMemoryPercent(used, total);
    if (percent < 60) return 'bg-blue-500';
    if (percent < 80) return 'bg-amber-500';
    return 'bg-rose-500';
};

const getDiskColor = (used, total) => {
    const percent = getDiskPercent(used, total);
    if (percent < 70) return 'bg-purple-500';
    if (percent < 85) return 'bg-amber-500';
    return 'bg-rose-500';
};

const formatTimeAgo = (date) => {
    if (!date) return 'never';
    const now = new Date();
    const then = new Date(date);
    const diffInSeconds = Math.floor((now - then) / 1000);
    
    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`;
    return then.toLocaleDateString();
};
</script>
