<template>
    <AppLayout>
        <div class="px-4 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="mb-8">
                <!-- Title and Status -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-3 mb-2 flex-wrap">
                            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900 dark:text-slate-100 truncate">{{ site.domain }}</h1>
                            <span
                                :class="site.is_active 
                                    ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 border-emerald-300 dark:border-emerald-700' 
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-400 border-slate-300 dark:border-slate-700'"
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border"
                            >
                                <span :class="site.is_active ? 'bg-emerald-500' : 'bg-slate-400'" class="w-2 h-2 rounded-full mr-2"></span>
                                {{ site.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-sm text-slate-600 dark:text-slate-400">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                                <Link :href="route('servers.show', site.server.id)" class="hover:text-slate-900 dark:hover:text-slate-200 transition-colors">
                                    {{ site.server.name }}
                                </Link>
                            </div>
                            <div v-if="site.repository_url" class="flex items-center space-x-2">
                                <a 
                                    :href="site.repository_url" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    class="flex items-center text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 dark:hover:text-cyan-300 transition-colors"
                                    :title="site.repository_url"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                    </svg>
                                    <span class="hidden sm:inline">{{ getRepositoryDisplay(site.repository_url) }}</span>
                                    <span class="sm:hidden">Repo</span>
                                </a>
                                <span class="text-slate-400">•</span>
                                <span class="text-xs font-mono bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">
                                    {{ site.repository_branch || 'main' }}
                                </span>
                                <svg v-if="site.auto_deploy" class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20" title="Auto-deploy enabled">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div v-if="site.framework || site.node_version || site.php_version" class="flex items-center">
                                <span v-if="isNodeFramework()" class="inline-flex items-center px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded text-xs font-semibold">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ getFrameworkDisplay() }} {{ getRuntimeVersion() }}
                                </span>
                                <span v-else class="inline-flex items-center px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded text-xs font-semibold">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                    </svg>
                                    PHP {{ site.php_version || '8.2' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons - Compact layout with dropdown for secondary actions -->
                <div class="flex flex-wrap items-center gap-2 max-w-full">
                    <!-- Primary Actions -->
                    <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                        <!-- Deploy Button (Primary) -->
                        <button
                            @click="deploy"
                            :disabled="isDeploying || !site.repository_url"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 shadow-lg shadow-cyan-500/30 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 whitespace-nowrap"
                            :title="!site.repository_url ? 'Repository URL required for deployment' : 'Deploy now'"
                        >
                            <svg v-if="!isDeploying" class="w-4 h-4 sm:mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <svg v-else class="w-4 h-4 sm:mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="hidden xs:inline">{{ isDeploying ? 'Deploying...' : 'Deploy' }}</span>
                        </button>

                        <!-- Auto Deploy Toggle -->
                        <button
                            @click="toggleAutoDeploy"
                            :disabled="isTogglingAutoDeploy || !site.repository_url"
                            :class="[
                                'inline-flex items-center px-2.5 py-2 border text-sm font-medium rounded-lg transition-all duration-200 whitespace-nowrap',
                                site.auto_deploy 
                                    ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 border-emerald-300 dark:border-emerald-700 hover:bg-emerald-200 dark:hover:bg-emerald-900/50' 
                                    : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-600 hover:bg-slate-200 dark:hover:bg-slate-600',
                                (!site.repository_url || isTogglingAutoDeploy) ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            :title="!site.repository_url ? 'Repository URL required for auto-deploy' : (site.auto_deploy ? 'Disable auto-deploy' : 'Enable auto-deploy')"
                        >
                            <svg v-if="site.auto_deploy" class="w-4 h-4 sm:mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else class="w-4 h-4 sm:mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            <span class="hidden md:inline">{{ isTogglingAutoDeploy ? 'Updating...' : (site.auto_deploy ? 'Auto-Deploy' : 'Auto-Deploy') }}</span>
                            <span class="md:hidden">{{ isTogglingAutoDeploy ? '...' : (site.auto_deploy ? 'On' : 'Off') }}</span>
                        </button>
                    </div>

                    <!-- Secondary Actions - Dropdown Menu -->
                    <div class="relative flex-shrink-0" @click.stop>
                        <button
                            @click.stop="showActionsMenu = !showActionsMenu"
                            class="inline-flex items-center px-2.5 py-2 border border-slate-300 dark:border-slate-600 text-sm font-medium rounded-lg bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-all duration-200 whitespace-nowrap"
                            title="More actions"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                            <span class="hidden sm:inline ml-1.5">More</span>
                            <svg class="w-3 h-3 ml-1 hidden sm:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div
                            v-if="showActionsMenu"
                            @click.stop
                            class="absolute right-0 mt-2 w-56 rounded-lg shadow-lg bg-white dark:bg-slate-800 ring-1 ring-black ring-opacity-5 z-50 border border-slate-200 dark:border-slate-700"
                        >
                            <div class="py-1">
                                <!-- Re-check Repository -->
                                <button
                                    @click="recheckRepository(); showActionsMenu = false"
                                    :disabled="isRechecking"
                                    class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                                >
                                    <svg v-if="!isRechecking" class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <svg v-else class="w-4 h-4 mr-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ isRechecking ? 'Checking...' : 'Re-check Repository' }}
                                </button>

                                <!-- Edit Site -->
                                <button
                                    @click="openEditModal(); showActionsMenu = false"
                                    class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 flex items-center"
                                >
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit Site
                                </button>

                                <!-- View Deployment Script -->
                                <button
                                    @click="showDeploymentScript(); showActionsMenu = false"
                                    class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 flex items-center"
                                >
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                    </svg>
                                    Deployment Script
                                </button>

                                <!-- View Deployment Logs -->
                                <button
                                    v-if="site.latest_deployment"
                                    @click="loadDeploymentLogs(); showActionsMenu = false"
                                    class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 flex items-center"
                                >
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Deployment Logs
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Site Configuration Cards -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                <!-- Repository Info -->
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Repository</h3>
                        <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <div v-if="site.repository_url" class="space-y-2">
                        <a 
                            :href="site.repository_url" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            class="text-sm text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 dark:hover:text-cyan-300 font-medium truncate block hover:underline"
                            :title="site.repository_url"
                        >
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                </svg>
                                {{ getRepositoryDisplay(site.repository_url) }}
                            </div>
                        </a>
                        <div class="flex items-center space-x-2">
                            <div class="text-xs text-slate-600 dark:text-slate-400">
                                Branch: <span class="font-medium font-mono bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">{{ site.repository_branch || 'main' }}</span>
                            </div>
                            <svg v-if="site.auto_deploy" class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20" title="Auto-deploy enabled">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div v-if="site.latest_deployment?.commit_hash" class="flex items-center space-x-2 text-xs">
                            <span class="text-slate-500 dark:text-slate-500">Commit:</span>
                            <code class="bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded font-mono text-slate-900 dark:text-slate-100">
                                {{ site.latest_deployment.commit_hash.substring(0, 7) }}
                            </code>
                            <a 
                                v-if="site.repository_url" 
                                :href="getCommitUrl(site.repository_url, site.latest_deployment.commit_hash)" 
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-cyan-600 dark:text-cyan-400 hover:underline"
                            >
                                View
                            </a>
                        </div>
                    </div>
                    <div v-else class="text-slate-400 dark:text-slate-600 text-sm">Not configured</div>
                </div>

                <!-- Framework & Runtime Version -->
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                            {{ getFrameworkName() }}
                        </h3>
                        <svg v-if="isNodeFramework()" class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <svg v-else class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-xs font-semibold">
                                {{ getFrameworkDisplay() }}
                            </span>
                        </div>
                        <div class="text-2xl font-bold" :class="isNodeFramework() ? 'text-emerald-600 dark:text-emerald-400' : 'text-purple-600 dark:text-purple-400'">
                            {{ getRuntimeVersion() }}
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            {{ isNodeFramework() ? 'Node.js' : 'PHP' }} Runtime
                        </div>
                    </div>
                </div>

                <!-- Web Directory -->
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Web Directory</h3>
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2 2H5a2 2 0 00-2 2z" />
                        </svg>
                    </div>
                    <div class="text-sm text-slate-900 dark:text-slate-100 font-mono truncate" :title="site.web_directory">
                        {{ site.web_directory || '/home/' + site.system_user + '/sites/' + site.domain }}
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-500 mt-1">
                        User: {{ site.system_user }}
                    </div>
                </div>

                <!-- Current Release -->
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Current Release</h3>
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div v-if="site.current_release" class="text-sm text-slate-900 dark:text-slate-100 font-mono truncate" :title="site.current_release">
                        {{ site.current_release.split('/').pop() }}
                    </div>
                    <div v-else class="text-slate-400 dark:text-slate-600 text-sm">No release</div>
                </div>
            </div>

            <!-- Framework & Deployment Info Section -->
            <div class="mb-8">
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Framework & Deployment</h3>
                        <div class="flex items-center space-x-2">
                            <span
                                v-if="site.auto_deploy"
                                class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 rounded-full text-xs font-semibold"
                            >
                                Auto-Deploy Enabled
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Framework Information -->
                        <div>
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Framework & Runtime</div>
                            <div class="space-y-3">
                                <div class="flex items-center space-x-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <div v-if="isNodeFramework()" class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div v-else class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-semibold text-slate-900 dark:text-slate-100">{{ getFrameworkDisplay() }}</div>
                                        <div class="text-sm text-slate-600 dark:text-slate-400">
                                            {{ isNodeFramework() ? 'Node.js' : 'PHP' }} {{ getRuntimeVersion() }}
                                        </div>
                                    </div>
                                </div>
                                <div v-if="site.repository_url" class="text-sm">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">Repository:</span>
                                    <a 
                                        :href="site.repository_url" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        class="ml-2 text-cyan-600 dark:text-cyan-400 hover:underline truncate block"
                                    >
                                        {{ getRepositoryDisplay(site.repository_url) }}
                                    </a>
                                    <div class="mt-1 flex items-center space-x-3 text-xs text-slate-500 dark:text-slate-400">
                                        <span>Branch: <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">{{ site.repository_branch || 'main' }}</code></span>
                                        <span v-if="site.latest_deployment?.commit_hash">
                                            Commit: 
                                            <a 
                                                :href="getCommitUrl(site.repository_url, site.latest_deployment.commit_hash)"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-cyan-600 dark:text-cyan-400 hover:underline font-mono"
                                            >
                                                {{ site.latest_deployment.commit_hash.substring(0, 7) }}
                                            </a>
                                        </span>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-slate-500 dark:text-slate-400">
                                    No repository configured
                                </div>
                            </div>
                        </div>
                        
                        <!-- Auto-Deploy Info -->
                        <div>
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Auto-Deployment</div>
                            <div class="space-y-3">
                                <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">
                                        {{ site.auto_deploy ? 'Auto-deploy is enabled. The site will automatically deploy when you push to the configured branch.' : 'Auto-deploy is disabled. Deploy manually using the "Deploy Now" button.' }}
                                    </p>
                                </div>
                                <div v-if="site.auto_deploy && site.webhook_token" class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                    <p class="text-xs font-semibold text-blue-900 dark:text-blue-100 mb-1">Webhook URL:</p>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <code class="flex-1 text-xs text-blue-800 dark:text-blue-300 break-all bg-blue-100 dark:bg-blue-900/50 p-2 rounded">
                                            {{ getWebhookUrl(site.webhook_token) }}
                                        </code>
                                        <button
                                            @click="copyWebhookUrl(site.webhook_token)"
                                            class="px-2 py-1 text-xs bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200 rounded hover:bg-blue-300 dark:hover:bg-blue-700 transition-colors"
                                            title="Copy webhook URL"
                                        >
                                            Copy
                                        </button>
                                    </div>
                                    <p class="text-xs text-blue-700 dark:text-blue-400 mt-2">
                                        Add this URL as a webhook in your GitHub repository settings (Settings → Webhooks → Add webhook). 
                                        Select "Just the push event" and use "application/json" as content type.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SSL Certificate Section -->
            <div class="mb-8">
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">SSL Certificate</h3>
                        <div class="flex items-center space-x-2">
                            <button
                                v-if="site.ssl_certificates && site.ssl_certificates.length > 0 && site.ssl_certificates[0].status === 'active'"
                                @click="renewSslCertificate"
                                :disabled="isRenewingSsl"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="isRenewingSsl" class="w-3 h-3 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ isRenewingSsl ? 'Renewing...' : 'Renew' }}
                            </button>
                            <button
                                v-if="!site.ssl_certificates || site.ssl_certificates.length === 0 || site.ssl_certificates[0].status !== 'active'"
                                @click="requestSslCertificate"
                                :disabled="isRequestingSsl"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border border-transparent bg-gradient-to-r from-emerald-500 to-teal-500 text-white hover:from-emerald-600 hover:to-teal-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="isRequestingSsl" class="w-3 h-3 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                {{ isRequestingSsl ? 'Requesting...' : 'Request SSL' }}
                            </button>
                        </div>
                    </div>
                    
                    <div v-if="site.ssl_certificates && site.ssl_certificates.length > 0">
                        <div v-for="cert in site.ssl_certificates" :key="cert.id" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">Status</div>
                                    <div class="flex items-center">
                                        <span
                                            :class="{
                                                'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400': cert.status === 'active',
                                                'bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400': cert.status === 'failed',
                                                'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400': cert.status === 'pending',
                                                'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-400': cert.status === 'expired',
                                            }"
                                            class="px-3 py-1 rounded-full text-xs font-semibold mr-2"
                                        >
                                            {{ cert.status }}
                                        </span>
                                        <svg v-if="cert.status === 'active'" class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">Domain</div>
                                    <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ cert.domain }}</div>
                                </div>
                                <div v-if="cert.issued_at">
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">Issued</div>
                                    <div class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                        {{ new Date(cert.issued_at).toLocaleDateString() }}
                                    </div>
                                </div>
                                <div v-if="cert.expires_at">
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">Expires</div>
                                    <div class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                        {{ new Date(cert.expires_at).toLocaleDateString() }}
                                        <span v-if="cert.expires_at && new Date(cert.expires_at) < new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)" class="text-amber-600 dark:text-amber-400 ml-2">
                                            (Expiring soon)
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="cert.error_message" class="p-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-lg">
                                <div class="text-xs font-semibold text-rose-800 dark:text-rose-400 mb-1">Error</div>
                                <div class="text-sm text-rose-700 dark:text-rose-300">{{ cert.error_message }}</div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">No SSL certificate installed</p>
                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Click "Request SSL" to install a Let's Encrypt certificate</p>
                    </div>
                </div>
            </div>

            <!-- Latest Deployment Status -->
            <div v-if="site.latest_deployment" class="mb-8">
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Latest Deployment</h3>
                        <div class="flex items-center space-x-3">
                            <button
                                @click="loadDeploymentLogs"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-900/20 rounded-lg hover:bg-cyan-100 dark:hover:bg-cyan-900/30 transition-colors"
                            >
                                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                View Logs
                            </button>
                            <span
                                :class="{
                                    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400': site.latest_deployment.status === 'success',
                                    'bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400': site.latest_deployment.status === 'failed',
                                    'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400': site.latest_deployment.status === 'running',
                                    'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-400': site.latest_deployment.status === 'pending',
                                }"
                                class="px-3 py-1 rounded-full text-xs font-semibold"
                            >
                                {{ site.latest_deployment.status }}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">Started</div>
                            <div class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                {{ site.latest_deployment.started_at ? new Date(site.latest_deployment.started_at).toLocaleString() : 'N/A' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">Completed</div>
                            <div class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                {{ site.latest_deployment.completed_at ? new Date(site.latest_deployment.completed_at).toLocaleString() : 'In progress...' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">Commit</div>
                            <div class="text-sm font-medium text-slate-900 dark:text-slate-100 font-mono">
                                {{ site.latest_deployment.commit_hash ? site.latest_deployment.commit_hash.substring(0, 7) : 'N/A' }}
                            </div>
                        </div>
                    </div>
                    <div v-if="site.latest_deployment.error_message" class="mt-4 p-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-lg">
                        <div class="text-xs font-semibold text-rose-800 dark:text-rose-400 mb-1">Error</div>
                        <div class="text-sm text-rose-700 dark:text-rose-300">{{ site.latest_deployment.error_message }}</div>
                    </div>
                </div>
            </div>

            <!-- Deployments History -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Deployment History</h2>
                    <span class="px-3 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 rounded-full text-sm font-medium">
                        {{ site.deployments?.length || 0 }} deployment{{ (site.deployments?.length || 0) !== 1 ? 's' : '' }}
                    </span>
                </div>
                <div v-if="site.deployments && site.deployments.length > 0" class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
                    <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="deployment in site.deployments" :key="deployment.id" class="px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <div class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                                            Deployment #{{ deployment.id }}
                                        </div>
                                        <span
                                            :class="{
                                                'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400': deployment.status === 'success',
                                                'bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400': deployment.status === 'failed',
                                                'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400': deployment.status === 'running',
                                                'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-400': deployment.status === 'pending',
                                            }"
                                            class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                        >
                                            {{ deployment.status }}
                                        </span>
                                    </div>
                                    <div class="mt-2 flex items-center space-x-4 text-sm text-slate-600 dark:text-slate-400">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ new Date(deployment.created_at).toLocaleString() }}
                                        </span>
                                        <span v-if="deployment.commit_hash" class="flex items-center font-mono">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                            </svg>
                                            {{ deployment.commit_hash.substring(0, 7) }}
                                        </span>
                                        <span v-if="deployment.release_path" class="flex items-center">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2 2H5a2 2 0 00-2 2z" />
                                            </svg>
                                            {{ deployment.release_path.split('/').pop() }}
                                        </span>
                                    </div>
                                    <div v-if="deployment.error_message" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
                                        {{ deployment.error_message }}
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div v-else class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">No deployments</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Deploy your site to see deployment history.</p>
                </div>
            </div>

            <!-- Databases Section -->
            <div v-if="site.databases && site.databases.length > 0" class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Databases</h2>
                    <span class="px-3 py-1 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 rounded-full text-sm font-medium">
                        {{ site.databases.length }} database{{ site.databases.length !== 1 ? 's' : '' }}
                    </span>
                </div>
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
                    <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="db in site.databases" :key="db.id" class="px-6 py-4">
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

            <!-- SSL Certificates Section -->
            <div v-if="site.ssl_certificates && site.ssl_certificates.length > 0" class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">SSL Certificates</h2>
                    <span class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full text-sm font-medium">
                        {{ site.ssl_certificates.length }} certificate{{ site.ssl_certificates.length !== 1 ? 's' : '' }}
                    </span>
                </div>
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
                    <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="cert in site.ssl_certificates" :key="cert.id" class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ cert.domain }}</div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                        Expires: {{ cert.expires_at ? new Date(cert.expires_at).toLocaleDateString() : 'N/A' }}
                                    </div>
                                </div>
                                <span
                                    :class="cert.is_active 
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400' 
                                        : 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-400'"
                                    class="px-3 py-1 rounded-full text-xs font-semibold"
                                >
                                    {{ cert.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Scheduled Commands (Cron Jobs) Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Scheduled Commands (Cron Jobs)</h2>
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-sm font-medium">
                            {{ scheduledCommands.length }} command{{ scheduledCommands.length !== 1 ? 's' : '' }}
                        </span>
                        <button
                            @click="showAddCronModal = true"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl text-white bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 shadow-lg shadow-amber-500/30 transition-all duration-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Cron Job
                        </button>
                    </div>
                </div>
                <div v-if="scheduledCommands.length > 0" class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
                    <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="cmd in scheduledCommands" :key="cmd.id" class="px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="text-lg font-semibold text-slate-900 dark:text-slate-100 font-mono text-sm">{{ cmd.command }}</div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                        <span class="font-medium">Schedule:</span> 
                                        <code class="bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">{{ cmd.cron_expression }}</code>
                                    </div>
                                    <div v-if="cmd.user" class="text-xs text-slate-500 dark:text-slate-500 mt-1">
                                        User: {{ cmd.user }}
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span
                                        :class="cmd.is_active 
                                            ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400' 
                                            : 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-400'"
                                        class="px-3 py-1 rounded-full text-xs font-semibold"
                                    >
                                        {{ cmd.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <button
                                        @click="deleteCronJob(cmd.id)"
                                        class="text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 transition-colors"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div v-else class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">No cron jobs</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Add scheduled commands to run automatically on your server.</p>
                </div>
            </div>

            <!-- PM2 Logs Section -->
            <div v-if="isNodeFramework()" class="mb-8">
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">PM2 Logs</h2>
                        <div class="flex items-center space-x-3">
                            <select
                                v-model="pm2LogLines"
                                @change="loadPm2Logs"
                                class="px-3 py-1.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-500"
                            >
                                <option :value="50">Last 50 lines</option>
                                <option :value="100">Last 100 lines</option>
                                <option :value="200">Last 200 lines</option>
                                <option :value="500">Last 500 lines</option>
                            </select>
                            <button
                                @click="loadPm2Logs"
                                :disabled="isLoadingPm2Logs"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="isLoadingPm2Logs" class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Refresh
                            </button>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="pm2AutoRefresh"
                                    class="rounded border-slate-300 dark:border-slate-600 text-cyan-600 focus:ring-cyan-500"
                                />
                                <span class="text-sm text-slate-700 dark:text-slate-300">Auto-refresh</span>
                            </label>
                        </div>
                    </div>
                    <div class="bg-slate-900 dark:bg-slate-950 rounded-lg p-4 overflow-x-auto">
                        <pre v-if="isLoadingPm2Logs" class="text-slate-400 text-sm">Loading logs...</pre>
                        <pre v-else-if="pm2Logs" class="text-green-400 text-xs font-mono whitespace-pre-wrap">{{ pm2Logs }}</pre>
                        <pre v-else class="text-slate-400 text-sm">No logs available. Make sure PM2 is running for this site.</pre>
                    </div>
                </div>
            </div>

            <!-- .env File Editor Section -->
            <div class="mb-8">
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Environment Variables (.env)</h2>
                        <div class="flex items-center space-x-3">
                            <button
                                @click="loadEnvFile"
                                :disabled="isLoadingEnvFile"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="isLoadingEnvFile" class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Reload
                            </button>
                            <button
                                @click="saveEnvFile"
                                :disabled="isSavingEnvFile || !envFileContent"
                                class="inline-flex items-center px-4 py-1.5 text-sm font-medium rounded-lg text-white bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="isSavingEnvFile" class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ isSavingEnvFile ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </div>
                    <div v-if="envFileError" class="mb-4 p-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-lg">
                        <div class="text-sm text-rose-800 dark:text-rose-400">{{ envFileError }}</div>
                    </div>
                    <div v-if="envFileSuccess" class="mb-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                        <div class="text-sm text-emerald-800 dark:text-emerald-400">{{ envFileSuccess }}</div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700">
                        <textarea
                            v-model="envFileContent"
                            rows="20"
                            class="w-full px-4 py-3 rounded-lg border-0 bg-transparent text-slate-900 dark:text-slate-100 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 resize-none"
                            placeholder="Loading .env file..."
                        ></textarea>
                    </div>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        Edit your environment variables directly. Changes will be saved to the .env file on the server.
                    </p>
                </div>
            </div>

            <!-- Edit Site Modal -->
            <div v-if="showEditModal" class="fixed inset-0 z-[100] overflow-y-auto" @click.self="showEditModal = false">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity bg-slate-500 bg-opacity-75 dark:bg-opacity-90 z-[101]" @click="showEditModal = false"></div>
                    <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full relative z-[102]">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Edit Site Configuration</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Update repository, framework, and directory information</p>
                        </div>
                        <form @submit.prevent="saveSiteEdit" class="px-6 py-4">
                            <div class="space-y-4">
                                <!-- Repository URL -->
                                <div>
                                    <label for="edit_repository_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Repository URL
                                    </label>
                                    <input
                                        id="edit_repository_url"
                                        v-model="editForm.repository_url"
                                        type="url"
                                        placeholder="https://github.com/user/repo.git"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                    />
                                </div>

                                <!-- Repository Branch -->
                                <div>
                                    <label for="edit_repository_branch" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Repository Branch
                                    </label>
                                    <input
                                        id="edit_repository_branch"
                                        v-model="editForm.repository_branch"
                                        type="text"
                                        placeholder="main"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                    />
                                </div>

                                <!-- Framework -->
                                <div>
                                    <label for="edit_framework" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Framework
                                    </label>
                                    <select
                                        id="edit_framework"
                                        v-model="editForm.framework"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                    >
                                        <option value="">Auto-detect</option>
                                        <option value="laravel">Laravel</option>
                                        <option value="nextjs">Next.js</option>
                                        <option value="react">React</option>
                                        <option value="nodejs">Node.js</option>
                                    </select>
                                </div>

                                <!-- PHP Version (show if Laravel or empty framework) -->
                                <div v-if="!editForm.framework || editForm.framework === 'laravel'">
                                    <label for="edit_php_version" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        PHP Version
                                    </label>
                                    <select
                                        id="edit_php_version"
                                        v-model="editForm.php_version"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                    >
                                        <option value="">Auto-detect</option>
                                        <option value="8.3">PHP 8.3</option>
                                        <option value="8.2">PHP 8.2</option>
                                        <option value="8.1">PHP 8.1</option>
                                        <option value="8.0">PHP 8.0</option>
                                        <option value="7.4">PHP 7.4</option>
                                    </select>
                                </div>

                                <!-- Node Version (show if Next.js/React/Node.js) -->
                                <div v-if="editForm.framework === 'nextjs' || editForm.framework === 'react' || editForm.framework === 'nodejs'">
                                    <label for="edit_node_version" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Node.js Version
                                    </label>
                                    <input
                                        id="edit_node_version"
                                        v-model="editForm.node_version"
                                        type="text"
                                        placeholder="18.x or 20.x"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                    />
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Enter Node.js version (e.g., 18, 20) or leave empty to auto-detect
                                    </p>
                                </div>

                                <!-- Web Directory -->
                                <!-- Web Directory -->
                                <div>
                                    <label for="edit_web_directory" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Web Directory
                                    </label>
                                    <input
                                        id="edit_web_directory"
                                        v-model="editForm.web_directory"
                                        type="text"
                                        placeholder="/var/www/html/{domain}"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-mono text-sm"
                                    />
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Absolute path to the site directory. Use {domain} placeholder if needed.
                                    </p>
                                </div>

                                <!-- System User -->
                                <div>
                                    <label for="edit_system_user" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        System User
                                    </label>
                                    <input
                                        id="edit_system_user"
                                        v-model="editForm.system_user"
                                        type="text"
                                        placeholder="www-data"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                    />
                                </div>

                                <!-- Deployment Script -->
                                <div>
                                    <label for="edit_deployment_script" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Deployment Script (Optional)
                                    </label>
                                    <textarea
                                        id="edit_deployment_script"
                                        v-model="editForm.deployment_script"
                                        rows="8"
                                        placeholder="# Custom deployment commands&#10;# Use placeholders: {release_path}, {current_path}, {releases_path}, {branch}, {domain}, {web_directory}"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-mono text-sm"
                                    ></textarea>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Leave empty to use default deployment script. One command per line. Lines starting with # are comments.
                                    </p>
                                </div>
                            </div>
                            <div class="mt-6 flex items-center justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="showEditModal = false"
                                    class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSavingEdit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ isSavingEdit ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Deployment Script Modal -->
            <div v-if="showDeploymentScriptModal" class="fixed inset-0 z-[100] overflow-y-auto" @click.self="showDeploymentScriptModal = false">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity bg-slate-500 bg-opacity-75 dark:bg-opacity-90 z-[101]" @click="showDeploymentScriptModal = false"></div>
                    <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full relative z-[102]">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Deployment Script</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Review and edit the commands that will run during deployment</p>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                Available placeholders: <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">{release_path}</code>, 
                                <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">{current_path}</code>, 
                                <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">{releases_path}</code>, 
                                <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">{branch}</code>, 
                                <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">{domain}</code>, 
                                <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">{web_directory}</code>
                            </p>
                        </div>
                        <div class="px-6 py-4">
                            <div v-if="isLoadingScript" class="text-center py-8">
                                <svg class="mx-auto h-8 w-8 text-cyan-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Loading deployment script...</p>
                            </div>
                            <textarea
                                v-else
                                v-model="deploymentScript"
                                rows="20"
                                class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                placeholder="# Deployment script will appear here..."
                            ></textarea>
                        </div>
                        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end space-x-3">
                            <button
                                type="button"
                                @click="showDeploymentScriptModal = false"
                                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                @click="saveDeploymentScript"
                                :disabled="isSavingScript"
                                class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ isSavingScript ? 'Saving...' : 'Save Script' }}
                            </button>
                            <button
                                type="button"
                                @click="confirmDeploy"
                                :disabled="isSavingScript || !site.repository_url"
                                class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Save & Deploy
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deployment Logs Modal -->
            <div v-if="showDeploymentLogsModal" class="fixed inset-0 z-[100] overflow-y-auto" @click.self="showDeploymentLogsModal = false">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity bg-slate-500 bg-opacity-75 dark:bg-opacity-90 z-[101]" @click="showDeploymentLogsModal = false"></div>
                    <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full relative z-[102]">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Deployment Logs</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        {{ site.latest_deployment ? `Deployment #${site.latest_deployment.id} - ${site.latest_deployment.status}` : 'No deployment found' }}
                                    </p>
                                </div>
                                <button
                                    @click="showDeploymentLogsModal = false"
                                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                            <div v-if="isLoadingLogs" class="text-center py-8">
                                <svg class="mx-auto h-8 w-8 text-cyan-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Loading logs...</p>
                            </div>
                            <div v-else-if="(!site.latest_deployment || !site.latest_deployment.logs || site.latest_deployment.logs.length === 0) && deploymentLogs.length === 0" class="text-center py-8 text-slate-500 dark:text-slate-400">
                                No deployment logs available
                            </div>
                            <div v-else class="space-y-4">
                                <div
                                    v-for="log in (deploymentLogs.length > 0 ? deploymentLogs : (site.latest_deployment?.logs || [])).sort((a, b) => a.order - b.order)"
                                    :key="log.id"
                                    class="border-l-4 pl-4 py-2"
                                    :class="{
                                        'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/10': log.level === 'info',
                                        'border-rose-500 bg-rose-50 dark:bg-rose-900/10': log.level === 'error',
                                        'border-amber-500 bg-amber-50 dark:bg-amber-900/10': log.level === 'warning',
                                    }"
                                >
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ log.step }}</span>
                                        <span
                                            class="px-2 py-0.5 rounded text-xs font-medium"
                                            :class="{
                                                'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-400': log.level === 'info',
                                                'bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400': log.level === 'error',
                                                'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400': log.level === 'warning',
                                            }"
                                        >
                                            {{ log.level }}
                                        </span>
                                    </div>
                                    <pre class="text-xs font-mono text-slate-700 dark:text-slate-300 whitespace-pre-wrap break-words mt-2">{{ log.output }}</pre>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end">
                            <button
                                @click="showDeploymentLogsModal = false"
                                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Cron Job Modal -->
            <div v-if="showAddCronModal" class="fixed inset-0 z-[100] overflow-y-auto" @click.self="showAddCronModal = false">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity bg-slate-500 bg-opacity-75 dark:bg-opacity-90 z-[101]" @click="showAddCronModal = false"></div>
                    <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full relative z-[102]">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Add Cron Job</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Schedule a command to run automatically</p>
                        </div>
                        <form @submit.prevent="submitCronJob" class="px-6 py-4">
                            <div class="space-y-4">
                                <div>
                                    <label for="cron_expression" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Cron Expression <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="cron_expression"
                                        v-model="cronForm.cron_expression"
                                        type="text"
                                        required
                                        placeholder="0 * * * *"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all font-mono"
                                    />
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Format: minute hour day month weekday (e.g., "0 * * * *" = every hour)
                                    </p>
                                </div>
                                <div>
                                    <label for="cron_command" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Command <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        id="cron_command"
                                        v-model="cronForm.command"
                                        required
                                        rows="3"
                                        placeholder="php /path/to/artisan schedule:run"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all font-mono text-sm"
                                    ></textarea>
                                </div>
                                <div>
                                    <label for="cron_user" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        User
                                    </label>
                                    <input
                                        id="cron_user"
                                        v-model="cronForm.user"
                                        type="text"
                                        :placeholder="site.system_user"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                    />
                                </div>
                            </div>
                            <div class="mt-6 flex items-center justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="showAddCronModal = false"
                                    class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSubmittingCron"
                                    class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ isSubmittingCron ? 'Adding...' : 'Add Cron Job' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    site: Object,
});

const isDeploying = ref(false);
const isTogglingAutoDeploy = ref(false);
const isRechecking = ref(false);
const isRequestingSsl = ref(false);
const isRenewingSsl = ref(false);
const showEditModal = ref(false);
const isSavingEdit = ref(false);
const showDeploymentScriptModal = ref(false);
const showDeploymentLogsModal = ref(false);
const showActionsMenu = ref(false);
const deploymentScript = ref('');
const isLoadingScript = ref(false);
const isSavingScript = ref(false);
const deploymentLogs = ref([]);
const isLoadingLogs = ref(false);
const recheckJobId = ref(null);
const scheduledCommands = ref(props.site.scheduled_commands || []);
const showAddCronModal = ref(false);
const isSubmittingCron = ref(false);
const cronForm = ref({
    command: '',
    cron_expression: '',
    user: props.site.system_user || '',
    is_active: true,
});

// PM2 Logs
const pm2Logs = ref('');
const isLoadingPm2Logs = ref(false);
const pm2LogLines = ref(100);
const pm2AutoRefresh = ref(false);
let pm2RefreshInterval = null;

// .env File
const envFileContent = ref('');
const isLoadingEnvFile = ref(false);
const isSavingEnvFile = ref(false);
const envFileError = ref('');
const envFileSuccess = ref('');

const editForm = ref({
    repository_url: props.site.repository_url || '',
    repository_branch: props.site.repository_branch || 'main',
    framework: props.site.framework || '',
    php_version: props.site.php_version || '',
    node_version: props.site.node_version || '',
    web_directory: props.site.web_directory || '',
    system_user: props.site.system_user || '',
    deployment_script: props.site.deployment_script || '',
});

const getRepositoryDisplay = (url) => {
    if (!url) return 'Not configured';
    try {
        const urlObj = new URL(url);
        if (urlObj.hostname.includes('github.com')) {
            return urlObj.pathname.replace('.git', '').replace(/^\//, '');
        }
        return urlObj.pathname.split('/').pop() || url;
    } catch {
        return url.split('/').pop() || url;
    }
};

const getCommitUrl = (repoUrl, commitHash) => {
    if (!repoUrl || !commitHash) return '#';
    try {
        const url = repoUrl.replace('.git', '');
        if (url.includes('github.com')) {
            return `${url}/commit/${commitHash}`;
        }
        return url;
    } catch {
        return '#';
    }
};

const getWebhookUrl = (token) => {
    return `${window.location.origin}/api/webhooks/deploy/${token}`;
};

const copyWebhookUrl = async (token) => {
    const url = getWebhookUrl(token);
    try {
        await navigator.clipboard.writeText(url);
        alert('Webhook URL copied to clipboard!');
    } catch (err) {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = url;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Webhook URL copied to clipboard!');
    }
};

const isNodeFramework = () => {
    return props.site.framework === 'nextjs' || 
           props.site.framework === 'react' || 
           props.site.framework === 'nodejs' ||
           props.site.node_version;
};

const getFrameworkName = () => {
    if (props.site.framework === 'nextjs') return 'Framework';
    if (props.site.framework === 'react') return 'Framework';
    if (props.site.framework === 'laravel') return 'Framework';
    if (props.site.node_version) return 'Runtime';
    return 'Runtime';
};

const getFrameworkDisplay = () => {
    if (props.site.framework === 'nextjs') return 'Next.js';
    if (props.site.framework === 'react') return 'React';
    if (props.site.framework === 'laravel') return 'Laravel';
    if (props.site.framework === 'nodejs') return 'Node.js';
    if (props.site.node_version) return 'Node.js';
    return 'PHP';
};

const getRuntimeVersion = () => {
    if (isNodeFramework()) {
        return props.site.node_version || '18.x';
    }
    return props.site.php_version || '8.2';
};

// Close dropdown when clicking outside
let clickOutsideHandler = null;
onMounted(() => {
    clickOutsideHandler = (event) => {
        if (showActionsMenu.value && !event.target.closest('.relative')) {
            showActionsMenu.value = false;
        }
    };
    document.addEventListener('click', clickOutsideHandler);
});

onBeforeUnmount(() => {
    if (clickOutsideHandler) {
        document.removeEventListener('click', clickOutsideHandler);
    }
});

// SSL Certificate Functions
const requestSslCertificate = async () => {
    if (!confirm(`Request SSL certificate for ${props.site.domain}? This will use Let's Encrypt to generate a free SSL certificate.`)) {
        return;
    }
    
    isRequestingSsl.value = true;
    
    try {
        const response = await axios.post(route('sites.ssl.request', props.site.id));
        
        if (response.data.success) {
            alert('SSL certificate installed successfully!');
            router.reload();
        } else {
            alert('Failed to install SSL certificate: ' + (response.data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('SSL request failed:', error);
        alert('Failed to request SSL certificate: ' + (error.response?.data?.message || error.message));
    } finally {
        isRequestingSsl.value = false;
    }
};

const renewSslCertificate = async () => {
    if (!confirm(`Renew SSL certificate for ${props.site.domain}?`)) {
        return;
    }
    
    isRenewingSsl.value = true;
    
    try {
        const response = await axios.post(route('sites.ssl.renew', props.site.id));
        
        if (response.data.success) {
            alert('SSL certificate renewed successfully!');
            router.reload();
        } else {
            alert('Failed to renew SSL certificate: ' + (response.data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('SSL renewal failed:', error);
        alert('Failed to renew SSL certificate: ' + (error.response?.data?.message || error.message));
    } finally {
        isRenewingSsl.value = false;
    }
};

const showDeploymentScript = async () => {
    showDeploymentScriptModal.value = true;
    isLoadingScript.value = true;
    try {
        const response = await axios.get(route('sites.deployment-script', props.site.id));
        if (response.data.success) {
            deploymentScript.value = response.data.script || '';
        }
    } catch (error) {
        alert('Failed to load deployment script: ' + (error.response?.data?.message || error.message));
    } finally {
        isLoadingScript.value = false;
    }
};

const saveDeploymentScript = async () => {
    isSavingScript.value = true;
    try {
        const response = await axios.post(route('sites.update', props.site.id), {
            deployment_script: deploymentScript.value,
            _method: 'POST',
        });
        
        if (response.data.success) {
            showDeploymentScriptModal.value = false;
            router.reload();
        } else {
            alert('Failed to save deployment script');
        }
    } catch (error) {
        alert('Failed to save deployment script: ' + (error.response?.data?.message || error.message));
    } finally {
        isSavingScript.value = false;
    }
};

const loadDeploymentLogs = async () => {
    if (!props.site.latest_deployment) {
        alert('No deployment found');
        return;
    }
    
    showDeploymentLogsModal.value = true;
    isLoadingLogs.value = true;
    
    // Use existing logs if available
    if (props.site.latest_deployment?.logs && props.site.latest_deployment.logs.length > 0) {
        deploymentLogs.value = props.site.latest_deployment.logs.sort((a, b) => a.order - b.order);
        isLoadingLogs.value = false;
    } else {
        // Reload to get fresh logs
        router.reload({
            only: ['site'],
            preserveState: true,
            onSuccess: () => {
                if (props.site.latest_deployment?.logs) {
                    deploymentLogs.value = props.site.latest_deployment.logs.sort((a, b) => a.order - b.order);
                } else {
                    deploymentLogs.value = [];
                }
                isLoadingLogs.value = false;
            },
            onError: () => {
                isLoadingLogs.value = false;
            }
        });
    }
};

const deploy = async () => {
    // Show deployment script first
    await showDeploymentScript();
    
    // Wait for user to confirm or edit script
    // The actual deployment will be triggered from the script modal
};

const confirmDeploy = async () => {
    // Save script if modified
    if (deploymentScript.value !== (props.site.deployment_script || '')) {
        await saveDeploymentScript();
    }
    
    showDeploymentScriptModal.value = false;
    isDeploying.value = true;
    
    try {
        // Start the deployment job
        const response = await axios.post(route('sites.deploy', props.site.id));
        
        if (response.data.success && response.data.job_id) {
            const jobId = response.data.job_id;
            
            // Show logs modal
            showDeploymentLogsModal.value = true;
            
            // Poll for job status and logs
            const pollInterval = setInterval(async () => {
                try {
                    const statusResponse = await axios.get(route('sites.deployment-status', props.site.id), {
                        params: { job_id: jobId }
                    });
                    
                    if (statusResponse.data.success) {
                        const status = statusResponse.data.status;
                        
                        // Reload to get fresh logs
                        router.reload({
                            only: ['site'],
                            preserveState: true,
                            onSuccess: () => {
                                if (props.site.latest_deployment?.logs) {
                                    deploymentLogs.value = props.site.latest_deployment.logs.sort((a, b) => a.order - b.order);
                                } else if (props.site.latest_deployment) {
                                    // Try to load logs from the deployment
                                    deploymentLogs.value = [];
                                }
                            }
                        });
                        
                        if (status === 'completed') {
                            clearInterval(pollInterval);
                            isDeploying.value = false;
                            // Final reload to get all logs
                            router.reload();
                        } else if (status === 'failed') {
                            clearInterval(pollInterval);
                            isDeploying.value = false;
                            // Final reload to get error logs
                            router.reload();
                        }
                        // If status is 'processing', continue polling
                    }
                } catch (error) {
                    if (error.response?.status === 404) {
                        clearInterval(pollInterval);
                        isDeploying.value = false;
                        alert('Deployment job expired or not found. Please try again.');
                    }
                }
            }, 2000); // Poll every 2 seconds
            
            // Stop polling after 10 minutes
            setTimeout(() => {
                clearInterval(pollInterval);
                if (isDeploying.value) {
                    isDeploying.value = false;
                    alert('Deployment is taking longer than expected. Please check back later.');
                }
            }, 600000);
        } else {
            isDeploying.value = false;
            alert('Failed to start deployment: ' + (response.data.message || 'Unknown error'));
        }
    } catch (error) {
        isDeploying.value = false;
        alert('Deployment failed: ' + (error.response?.data?.message || error.message));
    }
};

const submitCronJob = async () => {
    isSubmittingCron.value = true;
    try {
        const response = await axios.post(route('sites.cron-jobs.store', props.site.id), cronForm.value);
        if (response.data.success) {
            scheduledCommands.value.push(response.data.scheduled_command);
            showAddCronModal.value = false;
            cronForm.value = {
                command: '',
                cron_expression: '',
                user: props.site.system_user || '',
                is_active: true,
            };
            router.reload();
        }
    } catch (error) {
        alert('Failed to add cron job: ' + (error.response?.data?.message || error.message));
    } finally {
        isSubmittingCron.value = false;
    }
};

const openEditModal = () => {
    // Initialize form with current site values
    editForm.value = {
        repository_url: props.site.repository_url || '',
        repository_branch: props.site.repository_branch || 'main',
        framework: props.site.framework || '',
        php_version: props.site.php_version || '',
        node_version: props.site.node_version || '',
        web_directory: props.site.web_directory || '',
        system_user: props.site.system_user || '',
        deployment_script: props.site.deployment_script || '',
    };
    showEditModal.value = true;
};

const recheckRepository = async () => {
    isRechecking.value = true;
    try {
        const response = await axios.post(route('sites.recheck', props.site.id));
        
        if (response.data.success && response.data.job_id) {
            recheckJobId.value = response.data.job_id;
            
            // Poll for job status
            const pollInterval = setInterval(async () => {
                try {
                    const statusResponse = await axios.get(route('sites.recheck-status', props.site.id), {
                        params: { job_id: recheckJobId.value }
                    });
                    
                    if (statusResponse.data.success) {
                        const status = statusResponse.data.status;
                        
                        if (status === 'completed') {
                            clearInterval(pollInterval);
                            isRechecking.value = false;
                            
                            if (statusResponse.data.detected) {
                                // Update edit form with detected values
                                editForm.value = {
                                    repository_url: statusResponse.data.detected.repository_url || props.site.repository_url || '',
                                    repository_branch: statusResponse.data.detected.repository_branch || props.site.repository_branch || 'main',
                                    framework: statusResponse.data.detected.framework || props.site.framework || '',
                                    php_version: statusResponse.data.detected.php_version || props.site.php_version || '',
                                    node_version: statusResponse.data.detected.node_version || props.site.node_version || '',
                                    web_directory: statusResponse.data.detected.web_directory || props.site.web_directory || '',
                                    system_user: props.site.system_user || '',
                                };
                                
                                // Ask user if they want to apply the detected values
                                if (confirm('Repository information detected! Would you like to apply these changes?\n\nClick OK to save, or Cancel to review in the edit form.')) {
                                    await saveSiteEdit();
                                } else {
                                    // Show edit modal with detected values pre-filled
                                    showEditModal.value = true;
                                }
                            }
                        } else if (status === 'failed') {
                            clearInterval(pollInterval);
                            isRechecking.value = false;
                            alert('Re-check failed: ' + (statusResponse.data.message || 'Unknown error'));
                        }
                        // If status is 'processing', continue polling
                    }
                } catch (error) {
                    if (error.response?.status === 404) {
                        clearInterval(pollInterval);
                        isRechecking.value = false;
                        alert('Re-check job expired or not found. Please try again.');
                    }
                }
            }, 2000); // Poll every 2 seconds
            
            // Stop polling after 2 minutes
            setTimeout(() => {
                clearInterval(pollInterval);
                if (isRechecking.value) {
                    isRechecking.value = false;
                    alert('Re-check is taking longer than expected. Please check back later.');
                }
            }, 120000);
        } else {
            isRechecking.value = false;
            alert('Failed to start re-check: ' + (response.data?.message || 'Unknown error'));
        }
    } catch (error) {
        isRechecking.value = false;
        alert('Failed to re-check repository: ' + (error.response?.data?.message || error.message));
    }
};

const saveSiteEdit = async () => {
    isSavingEdit.value = true;
    try {
        const response = await axios.post(route('sites.update', props.site.id), {
            ...editForm.value,
            _method: 'POST',
        });
        
        if (response.data.success) {
            showEditModal.value = false;
            router.reload();
        } else {
            alert('Failed to update site: ' + (response.data?.message || 'Unknown error'));
        }
    } catch (error) {
        alert('Failed to update site: ' + (error.response?.data?.message || error.message));
    } finally {
        isSavingEdit.value = false;
    }
};

const toggleAutoDeploy = async () => {
    if (!props.site.repository_url) {
        alert('Repository URL is required to enable auto-deploy');
        return;
    }
    
    isTogglingAutoDeploy.value = true;
    try {
        const response = await axios.post(route('sites.update', props.site.id), {
            auto_deploy: !props.site.auto_deploy,
            _method: 'POST',
        });
        
        if (response.data.success) {
            router.reload();
        } else {
            alert('Failed to toggle auto-deploy');
        }
    } catch (error) {
        alert('Failed to toggle auto-deploy: ' + (error.response?.data?.message || error.message));
    } finally {
        isTogglingAutoDeploy.value = false;
    }
};

const deleteCronJob = async (id) => {
    if (!confirm('Are you sure you want to delete this cron job?')) return;
    
    try {
        const response = await axios.delete(route('cron-jobs.delete', id));
        if (response.data.success) {
            scheduledCommands.value = scheduledCommands.value.filter(cmd => cmd.id !== id);
            router.reload();
        }
    } catch (error) {
        alert('Failed to delete cron job: ' + (error.response?.data?.message || error.message));
    }
};

// PM2 Logs Functions
const loadPm2Logs = async () => {
    if (!isNodeFramework()) return;
    
    isLoadingPm2Logs.value = true;
    try {
        const response = await axios.get(route('sites.pm2-logs', props.site.id), {
            params: { lines: pm2LogLines.value }
        });
        
        if (response.data.success) {
            pm2Logs.value = response.data.logs || response.data.error || 'No logs available';
        } else {
            pm2Logs.value = response.data.message || 'Failed to load logs';
        }
    } catch (error) {
        pm2Logs.value = 'Error loading logs: ' + (error.response?.data?.message || error.message);
    } finally {
        isLoadingPm2Logs.value = false;
    }
};

// Watch for auto-refresh toggle
watch(pm2AutoRefresh, (enabled) => {
    if (enabled) {
        // Start auto-refresh every 5 seconds
        pm2RefreshInterval = setInterval(() => {
            if (isNodeFramework()) {
                loadPm2Logs();
            }
        }, 5000);
    } else {
        // Stop auto-refresh
        if (pm2RefreshInterval) {
            clearInterval(pm2RefreshInterval);
            pm2RefreshInterval = null;
        }
    }
});

// Clean up interval on unmount
onBeforeUnmount(() => {
    if (pm2RefreshInterval) {
        clearInterval(pm2RefreshInterval);
    }
});

// .env File Functions
const loadEnvFile = async () => {
    isLoadingEnvFile.value = true;
    envFileError.value = '';
    envFileSuccess.value = '';
    
    try {
        const response = await axios.get(route('sites.env-file', props.site.id));
        
        if (response.data.success) {
            envFileContent.value = response.data.content || '';
        } else {
            envFileError.value = response.data.message || '.env file not found';
            envFileContent.value = '';
        }
    } catch (error) {
        envFileError.value = 'Error loading .env file: ' + (error.response?.data?.message || error.message);
        envFileContent.value = '';
    } finally {
        isLoadingEnvFile.value = false;
    }
};

const saveEnvFile = async () => {
    if (!envFileContent.value) {
        envFileError.value = 'Cannot save empty .env file';
        return;
    }
    
    isSavingEnvFile.value = true;
    envFileError.value = '';
    envFileSuccess.value = '';
    
    try {
        const response = await axios.post(route('sites.env-file.update', props.site.id), {
            content: envFileContent.value
        });
        
        if (response.data.success) {
            envFileSuccess.value = '.env file updated successfully!';
            // Clear success message after 3 seconds
            setTimeout(() => {
                envFileSuccess.value = '';
            }, 3000);
        } else {
            envFileError.value = response.data.message || 'Failed to save .env file';
        }
    } catch (error) {
        envFileError.value = 'Error saving .env file: ' + (error.response?.data?.message || error.message);
    } finally {
        isSavingEnvFile.value = false;
    }
};

// Load .env file on mount if it's a Node.js framework
onMounted(() => {
    if (isNodeFramework()) {
        loadPm2Logs();
    }
    loadEnvFile();
});
</script>
