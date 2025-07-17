<script setup lang="ts">
import MobileNav from '@/components/MobileNav.vue';
import SidebarNav from '@/components/SidebarNav.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import PageHeading from '@/components/PageHeading.vue';

const props = defineProps(['challenges']);
const showMyChallanges = ref(false);
const myChallenges = reactive([...props.challenges]);

const authUserId = ref(usePage().props.auth.user.id);

const getMatchResult = (challenge: any): string => {
    const isChallenger = challenge.user_id === authUserId.value;
    const isOpponent = challenge.opponent_id === authUserId.value;

    switch (challenge.challenge_status) {
        case 'won':
            return isChallenger ? 'Win' : isOpponent ? 'Loss' : 'N/A';
        case 'loss':
            return isChallenger ? 'Loss' : isOpponent ? 'Win' : 'N/A';
        case 'draw':
            return 'Draw';
        case 'anomaly':
            return 'Anomaly';
        default:
            return 'Pending';
    }
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar -->
        <SidebarNav />

        <!-- Main Content -->
        <main class="flex-1  p-2">
            <PageHeading :heading="'My Challenges'"/>

            <!-- Tabs -->
            <div class="mb-4 flex flex-col gap-2">
                <Link
                    as="button"
                    :href="route('matches.create-challenge')"
                    @click="showMyChallanges = false"
                    class="rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700"
                    :class="{ 'opacity-50': showMyChallanges }"
                >
                    + Create Challenge
                </Link>

                <Link
                    as="button"
                    :href="route('matches.active')"
                    class="rounded bg-gray-200 px-4 py-2 font-medium text-gray-700 hover:bg-gray-300"
                    :class="{ 'bg-blue-100 text-blue-700': showMyChallanges }"
                >
                    All Matches
                </Link>
            </div>

            <!-- Challenge Cards -->
            <div class="grid gap-3">
                <Link
                    as="div"
                    v-for="challenge in myChallenges"
                    :key="challenge.id"
                    :href="route('matches.challenge-details', [challenge.id])"
                    class="flex items-center justify-between rounded-lg bg-white p-3 shadow"
                >
                    <!-- Left section -->
                    <div>
                        <p class="font-semibold text-gray-800">
                            <template v-if="challenge.opponent_id === $page.props.auth.user.id"> @{{ challenge.user?.name }} </template>
                            <template v-else-if="challenge.opponent"> @{{ challenge.opponent?.name }} </template>
                            <template v-else> No opponent </template>
                        </p>
                        <p class="text-sm text-gray-600">id : {{ challenge.id }}</p>
                        <p class="text-sm text-gray-600">Tokens 🎟️· {{ challenge.tokens }}</p>
                        <p class="text-sm text-gray-600">Stake: KES {{ challenge.stake }}</p>
                        <p class="text-sm text-gray-500">Time Control: {{ challenge.time_control }}</p>
                    </div>

                    <!-- Right badges -->
                    <div class="flex flex-col items-end gap-1 self-start text-xs">
                        <!-- Challenge Status -->
                        <span
                            class="rounded-full px-2 py-1 font-medium"
                            :class="{
                                'bg-yellow-100 text-yellow-800': challenge.request_state === 'pending',
                                'bg-green-100 text-green-700': challenge.request_state === 'accepted',
                                'bg-red-100 text-red-700': ['rejected', 'canceled'].includes(challenge.request_state),
                                'bg-purple-100 text-purple-700': challenge.request_state === 'disputed',
                            }"
                        >
                            Challenge: {{ challenge.request_state }}
                        </span>

                        <!-- Match Result -->
                        <span
                            class="rounded-full px-2 py-1 font-medium"
                            :class="{
                                'bg-gray-100 text-gray-700': getMatchResult(challenge) === 'Pending',
                                'bg-green-100 text-green-700': getMatchResult(challenge) === 'Win',
                                'bg-blue-100 text-blue-700': getMatchResult(challenge) === 'Draw',
                                'bg-red-100 text-red-700': getMatchResult(challenge) === 'Loss',
                                'bg-yellow-100 text-yellow-700': getMatchResult(challenge) === 'Anomaly',
                            }"
                        >
                            Match: {{ getMatchResult(challenge) }}
                        </span>
                    </div>
                </Link>
            </div>
        </main>

        <!-- Bottom Nav for Mobile -->
        <MobileNav />
    </div>
</template>
