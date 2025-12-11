<template>
    <AppLayout>
        <div class="px-4 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <div class="flex items-center space-x-3 mb-2">
                            <Link :href="route('sites.show', site.id)" class="text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 dark:hover:text-cyan-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                            </Link>
                            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-slate-100">Deployment History</h1>
                        </div>
                        <div class="flex items-center space-x-4 text-sm text-slate-600 dark:text-slate-400">
                            <Link :href="route('sites.show', site.id)" class="hover:text-slate-900 dark:hover:text-slate-200 transition-colors">
                                {{ site.domain }}
                            </Link>
                            <span>•</span>
                            <Link :href="route('servers.show', site.server.id)" class="hover:text-slate-900 dark:hover:text-slate-200 transition-colors">
                                {{ site.server.name }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deployments List -->
            <div class="mb-8">
                <div v-if="deployments.data && deployments.data.length > 0" class="space-y-4">
                    <div
                        v-for="deployment in deployments.data"
                        :key="deployment.id"
                        class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 overflow-hidden"
                    >
                        <!-- Deployment Header -->
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div>
                                        <div class="flex items-center space-x-3">
                                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                                                Deployment #{{ deployment.id }}
                                            </h3>
                                            <span
                                                :class="{
                                                    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 border-emerald-300 dark:border-emerald-700': deployment.status === 'success',
                                                    'bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400 border-rose-300 dark:border-rose-700': deployment.status === 'failed',
                                                    'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 border-amber-300 dark:border-amber-700': deployment.status === 'running',
                                                    'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-400 border-slate-300 dark:border-slate-700': deployment.status === 'pending',
                                                }"
                                                class="px-3 py-1 rounded-full text-xs font-semibold border"
                                            >
                                                {{ deployment.status }}
                                            </span>
                                        </div>
                                        <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-slate-600 dark:text-slate-400">
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="font-medium">Started:</span>
                                                <span class="ml-1">{{ deployment.started_at ? new Date(deployment.started_at).toLocaleString() : 'N/A' }}</span>
                                            </div>
                                            <div v-if="deployment.completed_at" class="flex items-center">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="font-medium">Completed:</span>
                                                <span class="ml-1">{{ new Date(deployment.completed_at).toLocaleString() }}</span>
                                            </div>
                                            <div v-if="deployment.commit_hash" class="flex items-center">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                                </svg>
                                                <span class="font-medium">Commit:</span>
                                                <code class="ml-1 font-mono bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">
                                                    {{ deployment.commit_hash.substring(0, 7) }}
                                                </code>
                                            </div>
                                            <div v-if="deployment.release_path" class="flex items-center">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2 2H5a2 2 0 00-2 2z" />
                                                </svg>
                                                <span class="font-medium">Release:</span>
                                                <code class="ml-1 font-mono text-xs">{{ deployment.release_path.split('/').pop() }}</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    @click="toggleDeploymentLogs(deployment.id)"
                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors"
                                >
                                    <svg v-if="expandedDeployments.includes(deployment.id)" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                    <svg v-else class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                    {{ expandedDeployments.includes(deployment.id) ? 'Hide' : 'View' }} Logs
                                </button>
                            </div>
                            <div v-if="deployment.error_message" class="mt-3 p-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-lg">
                                <div class="text-xs font-semibold text-rose-800 dark:text-rose-400 mb-1">Error Message</div>
                                <div class="text-sm text-rose-700 dark:text-rose-300">{{ deployment.error_message }}</div>
                            </div>
                        </div>

                        <!-- Deployment Logs -->
                        <div v-if="expandedDeployments.includes(deployment.id)" class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50">
                            <div v-if="deployment.logs && deployment.logs.length > 0" class="space-y-3">
                                <div
                                    v-for="log in deployment.logs.sort((a, b) => a.order - b.order)"
                                    :key="log.id"
                                    class="border-l-4 pl-4 py-2 rounded-r"
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
                            <div v-else class="text-center py-8 text-slate-500 dark:text-slate-400">
                                <svg class="mx-auto h-8 w-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p>No logs available for this deployment</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="deployments.links && deployments.links.length > 3" class="flex items-center justify-between bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 px-6 py-4">
                        <div class="text-sm text-slate-600 dark:text-slate-400">
                            Showing {{ deployments.from }} to {{ deployments.to }} of {{ deployments.total }} deployments
                        </div>
                        <div class="flex items-center space-x-2">
                            <Link
                                v-for="link in deployments.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                                    link.active
                                        ? 'bg-cyan-500 text-white'
                                        : link.url
                                        ? 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-600 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            ></Link>
                        </div>
                    </div>
                </div>
                <div v-else class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/50 dark:border-slate-700/50 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">No deployments</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">This site hasn't been deployed yet.</p>
                    <div class="mt-6">
                        <Link
                            :href="route('sites.show', site.id)"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 transition-all"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            Deploy Now
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    site: Object,
    deployments: Object,
});

const expandedDeployments = ref([]);

const toggleDeploymentLogs = (deploymentId) => {
    const index = expandedDeployments.value.indexOf(deploymentId);
    if (index > -1) {
        expandedDeployments.value.splice(index, 1);
    } else {
        expandedDeployments.value.push(deploymentId);
    }
};
</script>
