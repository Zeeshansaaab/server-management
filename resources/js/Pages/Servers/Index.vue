<template>
    <AppLayout>
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Servers</h1>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Manage your servers</p>
                </div>
                <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center justify-center rounded-lg border border-transparent bg-gradient-to-r from-violet-500 to-indigo-500 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-violet-500/30 hover:from-violet-600 hover:to-indigo-600 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 transition-all duration-200"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Server
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
                                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-slate-100 sm:pl-6">Name</th>
                                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-slate-100">IP Address</th>
                                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-slate-100">Status</th>
                                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-slate-100">CPU</th>
                                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-slate-100">Memory</th>
                                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-slate-100">Last Seen</th>
                                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-800/50">
                                    <tr v-for="server in servers" :key="server.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 dark:text-slate-100 sm:pl-6">
                                            <Link :href="route('servers.show', server.id)" class="text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-colors">
                                                {{ server.name }}
                                            </Link>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 dark:text-slate-400">{{ server.ip_address }}</td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                                            <span
                                                :class="server.is_online 
                                                    ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400' 
                                                    : 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-400'"
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            >
                                                <span :class="server.is_online ? 'bg-emerald-500' : 'bg-slate-400'" class="w-1.5 h-1.5 rounded-full mr-1.5"></span>
                                                {{ server.is_online ? 'Online' : 'Offline' }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 dark:text-slate-400">
                                            <span v-if="server.latest_metric?.cpu_usage" class="font-medium">
                                                {{ server.latest_metric.cpu_usage.toFixed(1) }}%
                                            </span>
                                            <span v-else class="text-slate-400 dark:text-slate-600">N/A</span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 dark:text-slate-400">
                                            <span v-if="server.latest_metric?.memory_used_mb && server.latest_metric?.memory_total_mb">
                                                {{ Math.round((server.latest_metric.memory_used_mb / server.latest_metric.memory_total_mb) * 100) }}%
                                            </span>
                                            <span v-else class="text-slate-400 dark:text-slate-600">N/A</span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 dark:text-slate-400">
                                            {{ server.last_seen ? new Date(server.last_seen).toLocaleString() : 'Never' }}
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <Link :href="route('servers.show', server.id)" class="text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-colors">
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Server Modal -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
                
                <!-- Modal -->
                <div class="relative w-full max-w-2xl bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-700">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Add New Server</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Connect and manage your server</p>
                        </div>
                        <button
                            @click="closeModal"
                            class="p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Server Name <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="My Production Server"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                            />
                            <p v-if="fieldErrors.name" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ fieldErrors.name[0] }}</p>
                        </div>

                        <!-- Hostname -->
                        <div>
                            <label for="hostname" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Hostname <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="hostname"
                                v-model="form.hostname"
                                type="text"
                                required
                                placeholder="server.example.com"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                            />
                            <p v-if="fieldErrors.hostname" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ fieldErrors.hostname[0] }}</p>
                        </div>

                        <!-- IP Address -->
                        <div>
                            <label for="ip_address" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                IP Address <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="ip_address"
                                v-model="form.ip_address"
                                type="text"
                                required
                                placeholder="192.168.1.100"
                                pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                            />
                            <p v-if="fieldErrors.ip_address" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ fieldErrors.ip_address[0] }}</p>
                        </div>

                        <!-- SSH Configuration -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="ssh_user" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    SSH User
                                </label>
                                <input
                                    id="ssh_user"
                                    v-model="form.ssh_user"
                                    type="text"
                                    placeholder="root"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                                />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Defaults to "root" if not specified</p>
                            </div>
                            <div>
                                <label for="ssh_port" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    SSH Port
                                </label>
                                <input
                                    id="ssh_port"
                                    v-model.number="form.ssh_port"
                                    type="number"
                                    min="1"
                                    max="65535"
                                    placeholder="22"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                                />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Defaults to 22 if not specified</p>
                            </div>
                        </div>

                        <!-- Security Notice -->
                        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-100 mb-1">Security Best Practice</h4>
                                    <p class="text-xs text-amber-800 dark:text-amber-300 mb-2">
                                        <strong>Recommended:</strong> Create a <strong>dedicated SSH key pair</strong> just for ForgeLite. 
                                        Don't use your main MacBook key to reduce risk if the platform is compromised.
                                    </p>
                                    <div class="text-xs text-amber-700 dark:text-amber-400 space-y-1">
                                        <p><strong>1. Generate a dedicated key:</strong></p>
                                        <code class="block bg-amber-100 dark:bg-amber-900/50 px-2 py-1 rounded mt-1">ssh-keygen -t ed25519 -f ~/.ssh/forgelite_key -C "forgelite"</code>
                                        <p class="mt-2"><strong>2. Copy public key to server:</strong></p>
                                        <code class="block bg-amber-100 dark:bg-amber-900/50 px-2 py-1 rounded mt-1">ssh-copy-id -i ~/.ssh/forgelite_key.pub user@your-server-ip</code>
                                        <p class="mt-2"><strong>3. Paste the private key below:</strong></p>
                                        <code class="block bg-amber-100 dark:bg-amber-900/50 px-2 py-1 rounded mt-1">cat ~/.ssh/forgelite_key</code>
                                    </div>
                                    <p class="text-xs text-amber-700 dark:text-amber-400 mt-2">
                                        <strong>Note:</strong> Your private key is encrypted (AES-256) before storage, but using a dedicated key limits exposure.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- SSH Private Key -->
                        <div>
                            <label for="ssh_private_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                SSH Private Key <span class="text-rose-500">*</span> (Required for connection)
                            </label>
                            <textarea
                                id="ssh_private_key"
                                v-model="form.ssh_private_key"
                                rows="8"
                                placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAABlwAAAAdzc2gtcn&#10;...&#10;-----END OPENSSH PRIVATE KEY-----"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all font-mono text-xs"
                            ></textarea>
                            <div class="mt-2 space-y-2">
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                    <p class="text-xs font-semibold text-blue-900 dark:text-blue-100 mb-1">How to get your private key:</p>
                                    <div class="text-xs text-blue-800 dark:text-blue-300 space-y-1 font-mono">
                                        <p><strong>1. Open Terminal on your MacBook</strong></p>
                                        <p><strong>2. Run:</strong> <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">cat ~/.ssh/id_ed25519</code></p>
                                        <p class="mt-2">Or if you have RSA key:</p>
                                        <p><code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">cat ~/.ssh/id_rsa</code></p>
                                        <p class="mt-2 text-blue-700 dark:text-blue-400"><strong>3. Copy the ENTIRE output</strong> (including BEGIN and END lines) and paste it here</p>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    <strong>Important:</strong> The key must include the "-----BEGIN" and "-----END" lines. Copy the entire key exactly as it appears.
                                </p>
                                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                    <p class="text-xs font-semibold text-amber-900 dark:text-amber-100 mb-1">⚠️ Common Issues:</p>
                                    <ul class="text-xs text-amber-800 dark:text-amber-300 space-y-1 list-disc list-inside">
                                        <li><strong>Key has passphrase:</strong> Remove it with: <code class="bg-amber-100 dark:bg-amber-900/50 px-1 rounded">ssh-keygen -p -f ~/.ssh/id_ed25519</code> (press Enter twice for no passphrase)</li>
                                        <li><strong>Wrong key format:</strong> Make sure you're copying the PRIVATE key, not the public key</li>
                                        <li><strong>Extra characters:</strong> Copy the key exactly - no extra spaces or line breaks</li>
                                        <li><strong>Key not authorized:</strong> Make sure the public key is in the server's <code class="bg-amber-100 dark:bg-amber-900/50 px-1 rounded">~/.ssh/authorized_keys</code></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- SSH Public Key -->
                        <div>
                            <label for="ssh_public_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                SSH Public Key (Optional)
                            </label>
                            <input
                                id="ssh_public_key"
                                v-model="form.ssh_public_key"
                                type="text"
                                placeholder="ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAA..."
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all font-mono text-sm"
                            />
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Your public key (usually found in <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">~/.ssh/id_rsa.pub</code>). 
                                This should match the private key above and be authorized on the server.
                            </p>
                        </div>

                        <!-- Error Message -->
                        <div v-if="errorMessage" class="p-4 rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800">
                            <p class="text-sm text-rose-800 dark:text-rose-300">{{ errorMessage }}</p>
                        </div>

                        <!-- Test Connection Result -->
                        <div v-if="testConnectionStatus" class="p-4 rounded-xl" :class="testConnectionStatus === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800'">
                            <div class="flex items-start">
                                <svg v-if="testConnectionStatus === 'success'" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 mr-2 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-else class="w-5 h-5 text-rose-600 dark:text-rose-400 mr-2 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium mb-2" :class="testConnectionStatus === 'success' ? 'text-emerald-800 dark:text-emerald-300' : 'text-rose-800 dark:text-rose-300'">
                                        {{ testConnectionStatus === 'success' ? 'Connection successful! You can now add this server.' : 'Connection Failed' }}
                                    </p>
                                    <div v-if="testConnectionStatus === 'error'" class="space-y-3">
                                        <p class="text-sm text-rose-700 dark:text-rose-300 whitespace-pre-wrap">{{ testConnectionMessage }}</p>
                                        
                                        <!-- Troubleshooting Guide for libcrypto errors -->
                                        <div v-if="testConnectionDetails?.error && testConnectionDetails.error.includes('error in libcrypto')" class="p-4 bg-rose-50 dark:bg-rose-900/30 rounded-lg border border-rose-200 dark:border-rose-800">
                                            <p class="text-sm font-semibold text-rose-900 dark:text-rose-200 mb-2">🔧 How to Fix "error in libcrypto":</p>
                                            <div class="space-y-2 text-xs text-rose-800 dark:text-rose-300">
                                                <div>
                                                    <p class="font-semibold mb-1">Step 1: Check if your key has a passphrase</p>
                                                    <p class="ml-2">Run: <code class="bg-rose-100 dark:bg-rose-900/50 px-1 rounded">ssh-keygen -y -f ~/.ssh/id_ed25519</code></p>
                                                    <p class="ml-2 mt-1">If it asks for a passphrase, your key is encrypted.</p>
                                                </div>
                                                <div>
                                                    <p class="font-semibold mb-1">Step 2: Remove the passphrase</p>
                                                    <code class="block bg-rose-100 dark:bg-rose-900/50 px-2 py-1 rounded mt-1">ssh-keygen -p -f ~/.ssh/id_ed25519</code>
                                                    <p class="ml-2 mt-1">Enter your current passphrase, then press Enter twice (no new passphrase)</p>
                                                </div>
                                                <div>
                                                    <p class="font-semibold mb-1">Step 3: Copy the key again</p>
                                                    <code class="block bg-rose-100 dark:bg-rose-900/50 px-2 py-1 rounded mt-1">cat ~/.ssh/id_ed25519</code>
                                                    <p class="ml-2 mt-1">Copy the ENTIRE output and paste it here</p>
                                                </div>
                                                <div>
                                                    <p class="font-semibold mb-1">Step 4: Verify the key format</p>
                                                    <p class="ml-2">The key should start with <code class="bg-rose-100 dark:bg-rose-900/50 px-1 rounded">-----BEGIN</code> and end with <code class="bg-rose-100 dark:bg-rose-900/50 px-1 rounded">-----END</code></p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div v-if="testConnectionDetails" class="p-3 bg-rose-100 dark:bg-rose-900/30 rounded-lg border border-rose-200 dark:border-rose-800">
                                            <p class="text-xs font-semibold text-rose-900 dark:text-rose-200 mb-2">Error Details:</p>
                                            <div class="space-y-1 text-xs font-mono text-rose-800 dark:text-rose-300">
                                                <div v-if="testConnectionDetails.error">
                                                    <span class="font-semibold">SSH Error:</span>
                                                    <pre class="mt-1 whitespace-pre-wrap break-words bg-rose-50 dark:bg-rose-900/50 p-2 rounded">{{ testConnectionDetails.error }}</pre>
                                                </div>
                                                <div v-if="testConnectionDetails.output">
                                                    <span class="font-semibold">Output:</span>
                                                    <pre class="mt-1 whitespace-pre-wrap break-words bg-rose-50 dark:bg-rose-900/50 p-2 rounded">{{ testConnectionDetails.output }}</pre>
                                                </div>
                                                <div v-if="testConnectionDetails.exit_code !== null">
                                                    <span class="font-semibold">Exit Code:</span> {{ testConnectionDetails.exit_code }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                            <button
                                type="button"
                                @click="testConnection"
                                :disabled="isTestingConnection || !form.hostname || !form.ip_address"
                                class="px-4 py-2.5 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <span v-if="isTestingConnection" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Testing...
                                </span>
                                <span v-else class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Test Connection
                                </span>
                            </button>
                            <div class="flex items-center space-x-3">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-4 py-2.5 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSubmitting"
                                    class="px-6 py-2.5 rounded-xl text-sm font-medium text-white bg-gradient-to-r from-violet-500 to-indigo-500 hover:from-violet-600 hover:to-indigo-600 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 shadow-lg shadow-violet-500/30 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                                >
                                    <span v-if="isSubmitting" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Adding...
                                    </span>
                                    <span v-else>Add Server</span>
                                </button>
                            </div>
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

defineProps({
    servers: Array,
});

const showAddModal = ref(false);
const isSubmitting = ref(false);
const isTestingConnection = ref(false);
const testConnectionStatus = ref(null); // 'success', 'error', or null
const testConnectionMessage = ref('');
const testConnectionDetails = ref(null);
const errorMessage = ref('');
const fieldErrors = ref({});

const form = ref({
    name: '',
    hostname: '',
    ip_address: '',
    ssh_user: '',
    ssh_port: 22,
    ssh_private_key: '',
    ssh_public_key: '',
});

const closeModal = () => {
    showAddModal.value = false;
    form.value = {
        name: '',
        hostname: '',
        ip_address: '',
        ssh_user: '',
        ssh_port: 22,
        ssh_private_key: '',
        ssh_public_key: '',
    };
    errorMessage.value = '';
    fieldErrors.value = {};
    testConnectionStatus.value = null;
    testConnectionMessage.value = '';
    testConnectionDetails.value = null;
};

const testConnection = async () => {
    if (!form.value.hostname || !form.value.ip_address) {
        testConnectionStatus.value = 'error';
        testConnectionMessage.value = 'Please fill in hostname and IP address first.';
        return;
    }

    isTestingConnection.value = true;
    testConnectionStatus.value = null;
    testConnectionMessage.value = '';
    testConnectionDetails.value = null;

    try {
        const response = await axios.post(route('servers.test-connection'), {
            hostname: form.value.hostname,
            ip_address: form.value.ip_address,
            ssh_user: form.value.ssh_user || null,
            ssh_port: form.value.ssh_port || null,
            ssh_private_key: form.value.ssh_private_key || null,
        });

        if (response.data.connected) {
            testConnectionStatus.value = 'success';
            testConnectionMessage.value = '';
            testConnectionDetails.value = null;
        } else {
            testConnectionStatus.value = 'error';
            testConnectionMessage.value = response.data.message || 'Connection failed. Please check your credentials and server accessibility.';
            testConnectionDetails.value = {
                error: response.data.error || null,
                output: response.data.output || null,
                exit_code: response.data.exit_code || null,
            };
        }
    } catch (error) {
        testConnectionStatus.value = 'error';
        if (error.response?.data?.message) {
            testConnectionMessage.value = error.response.data.message;
            testConnectionDetails.value = {
                error: error.response.data.error || error.response.data.message,
                output: error.response.data.output || null,
                exit_code: error.response.data.exit_code || null,
                exception: error.response.data.exception || null,
            };
        } else if (error.response?.data?.errors) {
            const errors = error.response.data.errors;
            testConnectionMessage.value = Object.values(errors).flat().join(', ');
        } else {
            testConnectionMessage.value = 'Failed to test connection. Please check your server details.';
            testConnectionDetails.value = {
                error: error.message || 'Network error',
                output: null,
                exit_code: null,
            };
        }
    } finally {
        isTestingConnection.value = false;
    }
};

const submit = () => {
    isSubmitting.value = true;
    errorMessage.value = '';
    fieldErrors.value = {};

    router.post(route('servers.store'), {
        name: form.value.name,
        hostname: form.value.hostname,
        ip_address: form.value.ip_address,
        ssh_user: form.value.ssh_user || null,
        ssh_port: form.value.ssh_port || null,
        ssh_private_key: form.value.ssh_private_key || null,
        ssh_public_key: form.value.ssh_public_key || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            closeModal();
        },
        onError: (errors) => {
            isSubmitting.value = false;
            if (errors) {
                // Convert Inertia error format to field errors
                fieldErrors.value = {};
                Object.keys(errors).forEach(key => {
                    if (Array.isArray(errors[key])) {
                        fieldErrors.value[key] = errors[key];
                    } else {
                        fieldErrors.value[key] = [errors[key]];
                    }
                });
                
                // Set general error message if no field-specific errors
                if (Object.keys(fieldErrors.value).length === 0) {
                    errorMessage.value = 'Please check the form for errors.';
                }
            } else {
                errorMessage.value = 'Failed to add server. Please try again.';
            }
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>
