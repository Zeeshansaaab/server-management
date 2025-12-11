<template>
    <AppLayout>
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Dashboard</h1>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Overview of your servers and sites</p>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm overflow-hidden shadow-lg rounded-xl border border-slate-200/50 dark:border-slate-700/50 hover:shadow-xl transition-shadow">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="p-3 bg-gradient-to-br from-violet-500 to-indigo-500 rounded-lg">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Servers</dt>
                                    <dd class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ servers.length }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm overflow-hidden shadow-lg rounded-xl border border-slate-200/50 dark:border-slate-700/50 hover:shadow-xl transition-shadow">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="p-3 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-lg">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Sites</dt>
                                    <dd class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ sites.length }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm overflow-hidden shadow-lg rounded-xl border border-slate-200/50 dark:border-slate-700/50 hover:shadow-xl transition-shadow">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="p-3 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-lg">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Recent Deployments</dt>
                                    <dd class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ recentDeployments.length }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-4">Recent Deployments</h2>
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-sm shadow-lg overflow-hidden sm:rounded-xl border border-slate-200/50 dark:border-slate-700/50">
                    <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="deployment in recentDeployments" :key="deployment.id" class="px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ deployment.site.domain }}</p>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ deployment.site.server.name }}</p>
                                </div>
                                <div class="flex items-center">
                                    <span
                                        :class="{
                                            'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400': deployment.status === 'success',
                                            'bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400': deployment.status === 'failed',
                                            'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400': deployment.status === 'running',
                                            'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-400': deployment.status === 'pending',
                                        }"
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ deployment.status }}
                                    </span>
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
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    servers: Array,
    sites: Array,
    recentDeployments: Array,
});
</script>
