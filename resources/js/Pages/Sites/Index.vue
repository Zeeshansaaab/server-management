<template>
    <AppLayout>
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Sites</h1>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Manage your deployed sites</p>
                </div>
                <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex items-center space-x-3">
                    <button
                        @click="checkAllSitesHealth"
                        :disabled="isCheckingAll"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="isCheckingAll" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ isCheckingAll ? 'Checking...' : 'Check All' }}
                    </button>
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center justify-center rounded-lg border border-transparent bg-gradient-to-r from-cyan-500 to-blue-500 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-cyan-500/30 hover:from-cyan-600 hover:to-blue-600 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 transition-all duration-200"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Site
                    </button>
                </div>
            </div>

            <div class="mt-8 flex flex-col">
                <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                        <div class="overflow-hidden shadow-lg ring-1 ring-slate-200 dark:ring-slate-700 md:rounded-xl bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-900/50">
                                    <tr>
                                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-slate-100 sm:pl-6">Domain</th>
                                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-slate-100">Server</th>
                                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-slate-100">Repository</th>
                                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-slate-100">Status</th>
                                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-slate-100">Last Deployment</th>
                                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-800/50">
                                    <tr v-for="site in sites" :key="site.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 dark:text-slate-100 sm:pl-6">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                                </svg>
                                                <Link :href="route('sites.show', site.id)" class="text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 dark:hover:text-cyan-300 transition-colors font-semibold">
                                                    {{ site.domain }}
                                                </Link>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 dark:text-slate-400">
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                                </svg>
                                                <Link :href="route('servers.show', site.server.id)" class="hover:text-slate-900 dark:hover:text-slate-200">
                                                    {{ site.server.name }}
                                                </Link>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 dark:text-slate-400">
                                            <div v-if="site.repository_url" class="flex items-center">
                                                <svg class="w-4 h-4 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                                </svg>
                                                <span class="truncate max-w-xs">{{ site.repository_branch || 'main' }}</span>
                                            </div>
                                            <span v-else class="text-slate-400 dark:text-slate-600">N/A</span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                                            <div class="flex items-center space-x-2">
                                                <span
                                                    :class="site.is_active 
                                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400' 
                                                        : 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-400'"
                                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                                >
                                                    <span :class="site.is_active ? 'bg-emerald-500' : 'bg-slate-400'" class="w-1.5 h-1.5 rounded-full mr-1.5"></span>
                                                    {{ site.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                                <span
                                                    v-if="site.latest_deployment"
                                                    :class="{
                                                        'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400': site.latest_deployment.status === 'success',
                                                        'bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400': site.latest_deployment.status === 'failed',
                                                        'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400': site.latest_deployment.status === 'running',
                                                        'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-400': site.latest_deployment.status === 'pending',
                                                    }"
                                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                                >
                                                    {{ site.latest_deployment.status }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 dark:text-slate-400">
                                            <div v-if="site.latest_deployment" class="flex items-center">
                                                <svg class="w-4 h-4 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ formatTimeAgo(site.latest_deployment.created_at) }}
                                            </div>
                                            <span v-else class="text-slate-400 dark:text-slate-600">Never</span>
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <div class="flex items-center justify-end space-x-3">
                                                <button
                                                    @click="checkSiteHealth(site)"
                                                    :disabled="healthCheckLoading[site.id]"
                                                    class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-lg border transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                                    :class="{
                                                        'border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30': siteHealthStatus[site.id]?.success,
                                                        'border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 hover:bg-rose-100 dark:hover:bg-rose-900/30': siteHealthStatus[site.id] && !siteHealthStatus[site.id]?.success,
                                                        'border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600': !siteHealthStatus[site.id],
                                                    }"
                                                    :title="siteHealthStatus[site.id] ? `${siteHealthStatus[site.id].message} (${siteHealthStatus[site.id].status_code})` : 'Check site health'"
                                                >
                                                    <svg v-if="healthCheckLoading[site.id]" class="w-3 h-3 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <svg v-else-if="siteHealthStatus[site.id]?.success" class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                    <svg v-else-if="siteHealthStatus[site.id]" class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                    </svg>
                                                    <svg v-else class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span v-if="siteHealthStatus[site.id]?.status_code">{{ siteHealthStatus[site.id].status_code }}</span>
                                                    <span v-else>Check</span>
                                                </button>
                                                <Link :href="route('sites.show', site.id)" class="text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 dark:hover:text-cyan-300 transition-colors">
                                                    View
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Site Modal -->
        <div v-if="showAddModal" class="fixed inset-0 z-[100] overflow-y-auto" @click.self="showAddModal = false">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-slate-500 bg-opacity-75 dark:bg-opacity-90 z-[101]" @click="showAddModal = false"></div>
                <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full relative z-[102]">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Add New Site</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Create a new site on one of your servers</p>
                    </div>
                    <form @submit.prevent="submitSite" class="px-6 py-4">
                        <div class="space-y-4">
                            <!-- Server Selection -->
                            <div>
                                <label for="server_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Server <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    id="server_id"
                                    v-model="form.server_id"
                                    required
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                >
                                    <option value="">Select a server</option>
                                    <option v-for="server in servers" :key="server.id" :value="server.id">
                                        {{ server.name }} ({{ server.ip_address }})
                                    </option>
                                </select>
                            </div>

                            <!-- Domain -->
                            <div>
                                <label for="domain" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Domain <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="domain"
                                    v-model="form.domain"
                                    type="text"
                                    required
                                    placeholder="example.com"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                />
                            </div>

                            <!-- System User -->
                            <div>
                                <label for="system_user" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    System User
                                </label>
                                <input
                                    id="system_user"
                                    v-model="form.system_user"
                                    type="text"
                                    placeholder="Auto-generated if empty"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                />
                            </div>

                            <!-- Web Directory -->
                            <div>
                                <label for="web_directory" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Web Directory
                                </label>
                                <input
                                    id="web_directory"
                                    v-model="form.web_directory"
                                    type="text"
                                    placeholder="/home/{user}/sites/{domain}"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-mono text-sm"
                                />
                            </div>

                            <!-- Repository URL -->
                            <div>
                                <label for="repository_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Repository URL
                                </label>
                                <input
                                    id="repository_url"
                                    v-model="form.repository_url"
                                    type="url"
                                    placeholder="https://github.com/user/repo.git"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                />
                            </div>

                            <!-- Repository Branch -->
                            <div>
                                <label for="repository_branch" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Repository Branch
                                </label>
                                <input
                                    id="repository_branch"
                                    v-model="form.repository_branch"
                                    type="text"
                                    placeholder="main"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                />
                            </div>

                            <!-- Framework Selection -->
                            <div>
                                <label for="framework" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Framework <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    id="framework"
                                    v-model="form.framework"
                                    required
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                >
                                    <option value="">Select a framework</option>
                                    <option value="nextjs">Next.js</option>
                                    <option value="react">React</option>
                                    <option value="nodejs">Node.js</option>
                                    <option value="laravel">Laravel</option>
                                    <option value="other">Other</option>
                                </select>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Select the framework your site uses. This helps configure the correct deployment settings.
                                </p>
                            </div>

                            <!-- Node.js Version (for Next.js, React, Node.js) -->
                            <div v-if="form.framework === 'nextjs' || form.framework === 'react' || form.framework === 'nodejs'">
                                <label for="node_version" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Node.js Version
                                </label>
                                <select
                                    id="node_version"
                                    v-model="form.node_version"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                >
                                    <option value="">Auto-detect</option>
                                    <option value="22">Node.js 22</option>
                                    <option value="20">Node.js 20</option>
                                    <option value="18">Node.js 18</option>
                                    <option value="16">Node.js 16</option>
                                </select>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Select the Node.js version. If not specified, it will be auto-detected from your project.
                                </p>
                            </div>

                            <!-- PHP Version (for Laravel or Other) -->
                            <div v-if="form.framework === 'laravel' || form.framework === 'other' || !form.framework">
                                <label for="php_version" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    PHP Version
                                </label>
                                <select
                                    id="php_version"
                                    v-model="form.php_version"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                >
                                    <option value="8.3">PHP 8.3</option>
                                    <option value="8.2">PHP 8.2</option>
                                    <option value="8.1">PHP 8.1</option>
                                    <option value="8.0">PHP 8.0</option>
                                    <option value="7.4">PHP 7.4</option>
                                </select>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Select the PHP version for your Laravel or PHP application.
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end space-x-3">
                            <button
                                type="button"
                                @click="showAddModal = false"
                                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="isSubmitting"
                                class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ isSubmitting ? 'Creating...' : 'Create Site' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    sites: Array,
    servers: Array,
});

const showAddModal = ref(false);
const isSubmitting = ref(false);
const isCheckingAll = ref(false);
const healthCheckLoading = ref({});
const siteHealthStatus = ref({});
const form = ref({
    server_id: '',
    domain: '',
    system_user: '',
    web_directory: '',
    repository_url: '',
    repository_branch: 'main',
    framework: '',
    node_version: '',
    php_version: '8.2',
});

const submitSite = async () => {
    // Validate required fields
    if (!form.value.server_id || !form.value.domain || !form.value.framework) {
        alert('Please fill in all required fields (Server, Domain, and Framework)');
        return;
    }

    isSubmitting.value = true;
    
    router.post(route('sites.store'), form.value, {
        onSuccess: () => {
            showAddModal.value = false;
            form.value = {
                server_id: '',
                domain: '',
                system_user: '',
                web_directory: '',
                repository_url: '',
                repository_branch: 'main',
                framework: '',
                node_version: '',
                php_version: '8.2',
            };
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
            let errorMsg = 'Failed to create site. ';
            if (errors && typeof errors === 'object') {
                const errorMessages = Object.values(errors).flat();
                errorMsg += errorMessages.join(', ');
            } else if (typeof errors === 'string') {
                errorMsg += errors;
            } else {
                errorMsg += 'Please check your input and try again.';
            }
            alert(errorMsg);
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
        preserveScroll: true,
    });
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

const checkSiteHealth = async (site) => {
    healthCheckLoading.value[site.id] = true;
    
    try {
        const response = await axios.post(route('sites.check-health', site.id));
        siteHealthStatus.value[site.id] = response.data;
        
        // Show notification
        if (response.data.success) {
            // Success - site is up
            console.log(`Site ${site.domain} is healthy: ${response.data.status_code} - ${response.data.message}`);
        } else {
            // Error - site is down or has issues
            alert(`Site ${site.domain} health check: ${response.data.message} (${response.data.status_code || 'No response'})`);
        }
    } catch (error) {
        console.error('Health check failed:', error);
        siteHealthStatus.value[site.id] = {
            success: false,
            status_code: null,
            message: error.response?.data?.message || 'Failed to check site health',
        };
        alert(`Failed to check site health: ${error.response?.data?.message || error.message}`);
    } finally {
        healthCheckLoading.value[site.id] = false;
    }
};

const checkAllSitesHealth = async () => {
    if (props.sites.length === 0) {
        return;
    }
    
    isCheckingAll.value = true;
    
    // Check all sites in parallel with a small delay between batches to avoid overwhelming the server
    const batchSize = 5;
    const sites = [...props.sites];
    let successCount = 0;
    let failureCount = 0;
    
    for (let i = 0; i < sites.length; i += batchSize) {
        const batch = sites.slice(i, i + batchSize);
        
        // Check batch in parallel
        const promises = batch.map(async (site) => {
            healthCheckLoading.value[site.id] = true;
            
            try {
                const response = await axios.post(route('sites.check-health', site.id));
                siteHealthStatus.value[site.id] = response.data;
                
                if (response.data.success) {
                    successCount++;
                } else {
                    failureCount++;
                }
            } catch (error) {
                siteHealthStatus.value[site.id] = {
                    success: false,
                    status_code: null,
                    message: error.response?.data?.message || 'Failed to check site health',
                };
                failureCount++;
            } finally {
                healthCheckLoading.value[site.id] = false;
            }
        });
        
        await Promise.all(promises);
        
        // Small delay between batches to avoid overwhelming
        if (i + batchSize < sites.length) {
            await new Promise(resolve => setTimeout(resolve, 200));
        }
    }
    
    isCheckingAll.value = false;
    
    // Show summary
    const total = sites.length;
    const message = `Health check complete: ${successCount} healthy, ${failureCount} with issues out of ${total} sites`;
    console.log(message);
    
    if (failureCount > 0) {
        alert(message);
    }
};
</script>
