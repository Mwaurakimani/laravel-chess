<script setup lang="ts">
import MobileNav from '@/components/MobileNav.vue';
import PageHeading from '@/components/PageHeading.vue';
import SidebarNav from '@/components/SidebarNav.vue';
import { OnlineUser, onlineUsers } from '@/stores/presence';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps(['challengeDetails']);
const page = usePage();
const currentUserId = page.props.auth.user.id;

const challenge = ref({
    ...props.challengeDetails,
    rank: 1220,
    online: true,
});

const gameIsPlayed = Boolean(challenge.value.challenger_ready && challenge.value.contender_ready);

// Figure out who the opponent is
const opponentId = computed<number>(() =>
    challenge.value.user_id === currentUserId ? (challenge.value.opponent_id as number) : challenge.value.user_id,
);

// Computed: is our opponent currently online?
const isActive = computed<boolean>(() => onlineUsers.value.some((u: OnlineUser) => u.id === opponentId.value));

watch(isActive, (newVal) => {
    // Reassign the whole object, updating the `online` flag
    challenge.value = {
        ...challenge.value,
        online: newVal,
    };
});

const isOwner = computed(() => currentUserId === challenge.value.user_id);
const hasOpponent = computed(() => !!challenge.value.opponent);

const myInfo = computed(() => (isOwner.value ? challenge.value.user : challenge.value.opponent));
const otherInfo = computed(() => (isOwner.value ? challenge.value.opponent : challenge.value.user));

const isMatchComplete = computed(() => ['won', 'loss', 'draw'].includes(challenge.value.challenge_status));

const form = useForm({
    challenge_id: challenge.value.id,
});

const handleContend = () => {
    form.post(route('challenges.contend'), {
        onError: (errors) => {
            alert(Object.values(errors).join('\n'));
        },
    });
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar -->
        <SidebarNav />

        <!-- Main content -->
        <main class="flex-1 p-2">
            <div class="w-full max-w-md space-y-4">
                <PageHeading :heading="'Challenge Details'" />

                <div class="space-y-5 rounded-lg bg-white p-6 shadow">
                    <!-- My Info Section -->
                    <div v-if="myInfo" class="flex items-center justify-between border-b pb-3">
                        <div>
                            <p class="font-semibold text-gray-800">{{ myInfo.name }}</p>
                            <p class="text-sm text-gray-500">Rank: {{ challenge.rank }}</p>
                        </div>
                        <div :class="challenge.online ? 'text-green-600' : 'text-gray-400'" class="flex items-center gap-1 text-sm font-medium">
                            <span>{{ challenge.online ? 'Online now' : 'Offline' }}</span>
                            <span :class="challenge.online ? 'bg-green-500' : 'bg-gray-400'" class="h-2 w-2 rounded-full"></span>
                        </div>
                    </div>
                    <div v-else class="flex items-center justify-between border-b pb-3">
                        <div>
                            <p class="font-semibold text-gray-800">{{ challenge.user.name }}</p>
                            <p class="text-sm text-gray-500">Rank: {{ challenge.rank }}</p>
                        </div>
                        <div :class="challenge.online ? 'text-green-600' : 'text-gray-400'" class="flex items-center gap-1 text-sm font-medium">
                            <span>{{ challenge.online ? 'Online now' : 'Offline' }}</span>
                            <span :class="challenge.online ? 'bg-green-500' : 'bg-gray-400'" class="h-2 w-2 rounded-full"></span>
                        </div>
                    </div>

                    <!-- Match Details -->
                    <div class="space-y-3 text-sm text-gray-700">
                        <div class="flex items-center justify-between">
                            <span>🪙 Tokens Required</span>
                            <span class="font-semibold">{{ challenge.tokens }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>💰 Stake Amount</span>
                            <span class="font-semibold">KES {{ challenge.stake }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>⏱️ Time Control</span>
                            <span class="font-semibold">{{ challenge.time_control }}</span>
                        </div>
                        <div v-show="false" class="flex items-start justify-between">
                            <span>📝 Note</span>
                            <span class="text-right">{{ challenge.notes }}</span>
                        </div>
                    </div>

                    <!-- Other User Info -->
                    <div v-if="hasOpponent" class="rounded bg-gray-100 p-3 text-sm text-gray-700">
                        <p><span class="font-semibold">Opponent:</span> {{ otherInfo.name }}</p>
                        <p><span class="font-semibold">Email:</span> {{ otherInfo.email }}</p>
                    </div>

                    <!-- Action Section -->
                    <div class="mt-4 flex flex-col gap-3">
                        <!-- Conditional Buttons -->

                        <!-- Contend Button -->
                        <button
                            v-if="!isOwner && challenge.online && !gameIsPlayed && (challenge.request_state != 'accepted')"
                            @click.prevent="handleContend"
                            class="rounded bg-blue-600 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Challenge Now
                        </button>

                        <Link
                            v-if="hasOpponent && !gameIsPlayed"
                            as="button"
                            :href="route('matches.ready', { id: challenge.id })"
                            class="rounded bg-green-600 py-2 text-sm font-semibold text-white hover:bg-green-700"
                        >
                            Ready Now
                        </Link>

                        <Link
                            v-else-if="hasOpponent && gameIsPlayed"
                            as="button"
                            :href="route('matches.results', { id: challenge.id })"
                            class="rounded bg-blue-600 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            View Results
                        </Link>


                        <!-- Back Button -->
                        <Link
                            as="button"
                            :href="route('matches.active')"
                            class="rounded bg-gray-200 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300"
                        >
                            Back
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Mobile nav -->
        <MobileNav />
    </div>
</template>
