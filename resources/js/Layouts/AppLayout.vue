<template>
    <div class="min-h-screen bg-[#f8fafc] dark:bg-[#0a0e1a] transition-colors duration-300">
        <!-- Animated Background Pattern -->
        <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
            <!-- Light Mode Pattern -->
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px] dark:hidden"></div>
            <!-- Dark Mode Pattern -->
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#ffffff08_1px,transparent_1px),linear-gradient(to_bottom,#ffffff08_1px,transparent_1px)] bg-[size:24px_24px] hidden dark:block"></div>
            <!-- Gradient Overlays -->
            <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-violet-500/5 via-transparent to-cyan-500/5 dark:from-violet-500/10 dark:via-transparent dark:to-cyan-500/10"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-gradient-to-tl from-indigo-500/5 via-transparent to-purple-500/5 dark:from-indigo-500/10 dark:via-transparent dark:to-purple-500/10"></div>
        </div>

        <!-- Navigation -->
        <nav class="sticky top-0 z-50 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl border-b border-slate-200/50 dark:border-slate-800/50 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="shrink-0 flex items-center">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <h1 class="text-xl font-bold bg-gradient-to-r from-violet-600 via-indigo-600 to-cyan-600 dark:from-violet-400 dark:via-indigo-400 dark:to-cyan-400 bg-clip-text text-transparent">
                                    ForgeLite
                                </h1>
                            </div>
                        </div>
                        <div class="hidden sm:ml-10 sm:flex sm:space-x-1">
                            <Link
                                :href="route('dashboard')"
                                :class="[
                                    route().current('dashboard') 
                                        ? 'bg-gradient-to-r from-violet-500 to-indigo-500 text-white shadow-lg shadow-violet-500/30' 
                                        : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100/80 dark:hover:bg-slate-800/80',
                                    'inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200'
                                ]"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Dashboard
                            </Link>
                            <Link
                                :href="route('servers.index')"
                                :class="[
                                    route().current('servers.*') 
                                        ? 'bg-gradient-to-r from-violet-500 to-indigo-500 text-white shadow-lg shadow-violet-500/30' 
                                        : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100/80 dark:hover:bg-slate-800/80',
                                    'inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200'
                                ]"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                                Servers
                            </Link>
                            <Link
                                :href="route('sites.index')"
                                :class="[
                                    route().current('sites.*') 
                                        ? 'bg-gradient-to-r from-violet-500 to-indigo-500 text-white shadow-lg shadow-violet-500/30' 
                                        : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100/80 dark:hover:bg-slate-800/80',
                                    'inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200'
                                ]"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                                Sites
                            </Link>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center space-x-4">
                            <span v-if="$page.props.auth.user" class="hidden md:block text-sm text-slate-600 dark:text-slate-400 font-medium px-3 py-1.5 rounded-lg bg-slate-100/50 dark:bg-slate-800/50">
                                {{ $page.props.auth.user.email }}
                            </span>
                            
                            <!-- Dark Mode Toggle -->
                            <button
                                @click="toggleDarkMode"
                                class="relative p-2.5 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100/80 dark:hover:bg-slate-800/80 transition-all duration-200 border border-slate-200/50 dark:border-slate-700/50"
                                :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                            >
                                <svg v-if="!isDark" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                                <svg v-else class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </button>
                            
                            <form @submit.prevent="logout">
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl text-white bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 shadow-lg shadow-rose-500/30 transition-all duration-200"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="relative py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const isDark = ref(false);

const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    localStorage.setItem('darkMode', isDark.value.toString());
    updateDarkModeClass();
};

const updateDarkModeClass = () => {
    const html = document.documentElement;
    if (isDark.value) {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }
};

onMounted(() => {
    // Check localStorage for saved preference
    const saved = localStorage.getItem('darkMode');
    if (saved !== null) {
        isDark.value = saved === 'true';
    } else {
        // Check system preference
        isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    updateDarkModeClass();
    
    // Watch for system preference changes
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const handleChange = (e) => {
        if (localStorage.getItem('darkMode') === null) {
            isDark.value = e.matches;
            updateDarkModeClass();
        }
    };
    mediaQuery.addEventListener('change', handleChange);
});

const logout = () => {
    router.post(route('logout'));
};
</script>
