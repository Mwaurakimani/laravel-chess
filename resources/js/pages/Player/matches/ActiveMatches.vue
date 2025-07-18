<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import SidebarNav from '@/components/SidebarNav.vue';
import MobileNav from '@/components/MobileNav.vue';
import { Link } from '@inertiajs/vue3';
import PageHeading from '@/components/PageHeading.vue';
import axios from 'axios';
import { onlineUsers, type OnlineUser } from '@/stores/presence';
import { echo } from '@/Service/echo';
import type { PresenceChannel } from 'laravel-echo';

const showMyChallenges = ref(false);
const availableMatches = ref<any[]>([]);
const channel = ref<PresenceChannel | null>(null);

async function fetchAvailableMatches(users: OnlineUser[]) {
    try {
        const { data } = await axios.post(route('matches.get-active-matches'), {
            onlineUsers: users,
        });

        availableMatches.value = data.map((c: any) => ({
            id: c.id,
            username: c.user.name,
            rank: 1220,
            tokens: c.tokens,
            stake: c.stake,
            timeControl: c.time_control,
        }));
    } catch (e) {
        console.error('Failed to fetch active matches', e);
    }
}

onMounted(() => {
    // 1) initial fetch
    fetchAvailableMatches(onlineUsers.value);

    // 2) join the presence channel and store the instance
    channel.value = echo.join('presence-online');

    // 3) listen for ChallengeCreated — pass a callback, not the result of a call
    if (channel.value) {
        channel.value.listen('ChallengeCreated', () => {
            // re‑fetch when a new challenge comes in
            fetchAvailableMatches(onlineUsers.value);
        });
    }
});

onBeforeUnmount(() => {
    if (channel.value) {
        // stop just the ChallengeCreated listener
        channel.value.stopListening('ChallengeCreated');

        // leave the presence channel entirely
        echo.leave('presence-online');
    }
});

// whenever the list of online users changes, re‑fetch
watch(onlineUsers, (newList) => fetchAvailableMatches(newList), { deep: true });
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar for desktop -->
        <SidebarNav />

        <!-- Main content -->
        <main class="flex-1 p-2">
            <PageHeading :heading="'Active Challenges'" />

            <!-- Tabs -->
            <div class="mb-4 flex flex-col gap-2">
                <Link
                    as="button"
                    :href="route('matches.create-challenge')"
                    class="rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700"
                >
                    + Create Challenge
                </Link>
                <Link
                    as="button"
                    :href="route('matches.my-challenges')"
                    class="rounded bg-gray-200 px-4 py-2 font-medium text-gray-700 hover:bg-gray-300"
                    :class="{ 'bg-blue-100 text-blue-700': showMyChallenges }"
                >
                    My Challenges
                </Link>
            </div>

            <!-- Active Challenges -->
            <div v-if="showMyChallenges" class="space-y-4">
                <div v-for="(challenge, i) in availableMatches" :key="i" class="flex items-center justify-between rounded-lg bg-white p-4 shadow">
                    <div>
                        <p class="font-semibold text-gray-800">{{ challenge.username }}</p>
                        <p class="text-sm text-gray-500">
                            Rank: {{ challenge.rank }} <br />
                            🎟️ {{ challenge.tokens }} Tokens · KES {{ challenge.stake }} <br />
                            Time Control: {{ challenge.timeControl }}
                        </p>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        <span class="text-sm font-medium text-green-500">● Online</span>
                        <button
                            @click.prevent="window.location.href = route('matches.challenge-details', challenge.id)"
                            class="rounded bg-blue-600 px-4 py-1 text-sm text-white hover:bg-blue-700"
                        >
                            Challenge
                        </button>
                    </div>
                </div>
            </div>

            <!-- Match List -->
            <div v-else class="space-y-3">
                <div v-for="(match, i) in availableMatches" :key="i" class="flex items-center justify-between rounded-lg bg-white p-4 shadow-sm">
                    <div>
                        <p class="font-semibold text-gray-800">User: {{ match.username }}</p>
                        <p class="text-sm text-gray-500">Rank: {{ match.rank }} · Tokens: 🎟️ {{ match.tokens }} · Stake: KES {{ match.stake }}</p>
                    </div>
                    <Link
                        as="button"
                        :href="route('matches.challenge-details', [match.id])"
                        class="rounded bg-green-500 px-4 py-1 text-sm text-white hover:bg-green-600"
                    >
                        Challenge
                    </Link>
                </div>
            </div>
        </main>

        <!-- Bottom nav: mobile only -->
        <MobileNav />
    </div>
</template>
