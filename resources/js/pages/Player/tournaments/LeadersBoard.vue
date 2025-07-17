<script setup lang="ts">
import { ref } from 'vue';
import SidebarNav from '@/components/SidebarNav.vue';
import MobileNav from '@/components/MobileNav.vue';
import PageHeading from '@/components/PageHeading.vue';

const currentTab = ref<'wins' | 'staked'>('wins');

const leaderboardData = {
    wins: [
        { username: '@chessking', wins: 38, tokens: 80, stake: 5000 },
        { username: '@smartpawn', wins: 32, tokens: 65, stake: 4200 },
        { username: '@endgamebishop', wins: 29, tokens: 60, stake: 3900 },
    ],
    staked: [
        { username: '@strategyman', wins: 18, tokens: 55, stake: 7200 },
        { username: '@knightstorm', wins: 13, tokens: 29, stake: 2800 },
        { username: '@rookcharge', wins: 10, tokens: 40, stake: 2500 },
    ],
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <SidebarNav />

        <main class="flex-1 p-6 ">
            <PageHeading :heading="'Leaderboard'"/>

            <p class="text-sm text-gray-600">Top players based on wins and stake</p>

            <!-- Tabs -->
            <div class="flex gap-2">
                <button
                    @click="currentTab = 'wins'"
                    :class="currentTab === 'wins' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-4 py-2 rounded text-sm font-medium"
                >
                    Wins
                </button>
                <button
                    @click="currentTab = 'staked'"
                    :class="currentTab === 'staked' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-4 py-2 rounded text-sm font-medium"
                >
                    Staked
                </button>
            </div>

            <!-- Leaderboard Table -->
            <div class="overflow-x-auto bg-white shadow rounded-lg">
                <table class="min-w-full text-sm text-left text-gray-700">
                    <thead class="bg-gray-100 text-xs font-semibold text-gray-600">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Username</th>
                        <th class="px-4 py-3">Wins</th>
                        <th class="px-4 py-3">Tokens Earned</th>
                        <th class="px-4 py-3">Total Stake</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr
                        v-for="(player, index) in leaderboardData[currentTab]"
                        :key="player.username"
                        class="border-b hover:bg-gray-50"
                    >
                        <td class="px-4 py-3">{{ index + 1 }}</td>
                        <td class="px-4 py-3">{{ player.username }}</td>
                        <td class="px-4 py-3 text-green-600 font-medium">{{ player.wins }}</td>
                        <td class="px-4 py-3">🎟️ {{ player.tokens }}</td>
                        <td class="px-4 py-3">KES {{ player.stake.toLocaleString() }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </main>

        <MobileNav />
    </div>
</template>

