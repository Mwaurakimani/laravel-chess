<script setup lang="ts">
import MobileNav from '@/components/MobileNav.vue';
import SidebarNav from '@/components/SidebarNav.vue';
import PageHeading from '@/components/PageHeading.vue';

const props = defineProps(['user', 'recentMatches']);

const balance = props.user.balance;
const tokens = props.user.token_balance;
const recentMatches = props.recentMatches;
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar -->
        <SidebarNav />

        <!-- Main content -->
        <main class="flex-1  p-4 pb-4">
            <PageHeading :heading="'Your Dashboard'"/>
            <p>Welcome, {{ user.name }}</p>

            <!-- Wallet + Tokens -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-lg bg-white p-4 shadow">
                    <p class="text-sm text-gray-500">Wallet Balance</p>
                    <p class="text-xl font-semibold">KES {{ Number(balance).toLocaleString() }}</p>
                </div>
                <div class="rounded-lg bg-white p-4 shadow">
                    <p class="text-sm text-gray-500">Game Tokens</p>
                    <p class="text-xl font-semibold text-pink-600">🎟️ {{ tokens }}</p>
                </div>
            </div>

            <!-- Recent Matches -->
            <div class="mb-[50px]">
                <h2 class="mb-3 text-lg font-medium">Recent Matches</h2>

                <div v-if="recentMatches.length" class="space-y-2">
                    <div v-for="(match, i) in recentMatches" :key="i" class="flex items-center justify-between rounded-lg bg-white p-4 shadow-sm">
                        <div>
                            <p class="font-semibold">vs. {{ match.opponent }}</p>
                            <p class="text-sm text-gray-500">
                                {{ match.platform }} · 🎟️ {{ match.tokens }} · KES {{ match.stake }} · {{ match.date }}
                            </p>
                        </div>
                        <span
                            class="rounded-full px-2 py-1 text-sm font-medium"
                            :class="{
                                'bg-green-100 text-green-600': match.result === 'Win',
                                'bg-red-100 text-red-600': match.result === 'Loss',
                                'bg-yellow-100 text-yellow-700': match.result === 'Draw',
                                'bg-gray-200 text-gray-600': match.result === 'Anomaly',
                            }"
                        >
                            {{ match.result }}
                        </span>
                    </div>
                </div>

                <div v-else class="text-sm text-gray-500">You have not played any matches yet.</div>
            </div>
        </main>

        <!-- Mobile Nav -->
        <MobileNav />
    </div>
</template>
