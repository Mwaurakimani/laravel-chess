<script setup lang="ts">
import SidebarNav from '@/components/SidebarNav.vue';
import MobileNav from '@/components/MobileNav.vue';
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PageHeading from '@/components/PageHeading.vue';
import axios from 'axios';

const props = defineProps<{ challenge: any }>();
const page = usePage<{ auth: { user: { id: number } } }>();

// IDs & roles
const authUserId = page.props.auth.user.id;
const challenge = props.challenge;
const isChallenger = computed(() => challenge.user_id === authUserId);
//
// Time control mapping
const timeControlMap: Record<string, string> = {
    '5+0 Blitz': '300|0',
    '3+2 Blitz': '180|2',
    '10+0 Blitz': '600|0',
};

const displayTimeControl = computed(() => timeControlMap[challenge.time_control] || challenge.time_control);

const challengerLink = computed(() => {
    const url = new URL(challenge.platform.link + '/play/online/new');
    url.searchParams.set('opponent', challenge.platform.name == 'chess.com' ? challenge.opponent.chess_com_link : challenge.opponent.lichess_link)
    url.searchParams.set('time', displayTimeControl.value);
    return url.toString();
});

// Match info
const match = {
    id: challenge.id,
    challenger: challenge.user.name,
    opponent: challenge.opponent.name,
    tokens: challenge.tokens,
    stake: challenge.stake,
    timeControl: challenge.time_control,
    status: 'Funds Secured in Escrow',
    platform: challenge.platform.link,
    gameUrl: challengerLink,
};

// Popup state for opponent
const showOpponentPopup = ref(false);

const handleGameCreation = () => {
    axios.post(route('matches.game-created',[challenge.id]))
}
const handleOpponentJoin = () => {
    axios.post(route('matches.opponent-joined',[challenge.id]))
}

onMounted(() => {
    if (!isChallenger.value) {
        // After 30s, show the popup
        setTimeout(() => {
            showOpponentPopup.value = true
        }, 20_000)
    }
})

</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar -->
        <SidebarNav />

        <!-- Main content -->
        <main class="flex-1 p-2">
            <PageHeading heading="Match Ready" />

            <div class="max-w-xl space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow">
                <div class="text-sm text-gray-700">
                    <p class="mb-2">
                        <strong>Challenger:</strong>
                        <span class="text-black">{{ match.challenger }}</span>
                    </p>
                    <p class="mb-2">
                        <strong>Opponent:</strong>
                        <span class="text-black">{{ match.opponent }}</span>
                    </p>
                    <hr class="mb-2" />
                    <p class="mb-2"><strong>Stake:</strong> KES {{ match.stake }}</p>
                    <p class="mb-2"><strong>Tokens:</strong> {{ match.tokens }}</p>
                    <p class="mb-2"><strong>Time Control:</strong> {{ match.timeControl }}</p>
                    <hr class="mb-2" />
                    <p class="mt-2 font-medium text-green-600">✔ {{ match.status }}</p>
                </div>

                <p v-if="isChallenger" class="mt-2 text-sm text-orange-600">⏱️ You have 2 minutes to start</p>

                <!-- Action Buttons -->
                <div class="flex flex-col gap-4 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <!-- Challenger: immediate link to create/invite -->
                    <a
                        v-if="isChallenger"
                        :href="match.gameUrl.value"
                        target="_blank"
                        class="rounded bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700"
                        @click="handleGameCreation"
                    >
                        Create Game & Invite Opponent
                    </a>

                    <!-- Opponent: hide button, popup will appear after delay -->
                    <button  v-else disabled class="cursor-not-allowed rounded bg-gray-300 px-5 py-2 text-sm font-semibold text-gray-600">
                        Waiting for Game Setup…
                    </button>
                </div>

                <div v-if="isChallenger">
                    <p class="p-4 text-gray-700 text-justify">
                        All game details are set up.Do not change the game set up. Press
                        "Send Challenge" to invite opponent.
                    </p>
                    <img src="/storage/images/chess_dot_com_guide.png" />
                </div>
            </div>
        </main>

        <!-- Mobile nav -->
        <MobileNav />

        <!-- Opponent Popup -->
        <div v-if="showOpponentPopup" class="bg-opacity-50 fixed inset-0 flex items-center justify-center bg-black p-4">
            <div class="w-full max-w-sm space-y-4 rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-lg font-semibold">Game Ready!</h2>
                <p class="text-sm">The game has been set up. Please click the link below to join on the platform.</p>
                <a
                    :href="match.platform"
                    target="_blank"
                    class="block rounded bg-blue-600 px-4 py-2 text-center font-medium text-white hover:bg-blue-700"
                    @click="handleOpponentJoin"
                >
                    Go to Game
                </a>
                <Link
                    :href="route('matches.active')"
                    class="block rounded bg-blue-600 px-4 py-2 text-center font-medium text-white hover:bg-blue-700"
                >
                    Matches
                </Link>
            </div>
        </div>
    </div>
</template>
