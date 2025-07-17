<script setup lang="ts">
import SidebarNav from '@/components/SidebarNav.vue';
import MobileNav from '@/components/MobileNav.vue';
import { Link } from '@inertiajs/vue3';
import PageHeading from '@/components/PageHeading.vue';

const props = defineProps<{
    result: 'win' | 'loss' | 'draw' | 'canceled';
    opponent: string;
    tokens: number;
    winnings: number;
    timeControl: string;
    newRank: number;
    rankChange: number;
}>();

const match = {
    opponent: props.opponent,
    tokens: props.tokens,
    winnings: props.winnings,
    timeControl: props.timeControl,
    newRank: props.newRank,
    rankChange: props.rankChange,
};

</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar for desktop -->
        <SidebarNav />

        <!-- Main content -->
        <main class="flex-1 p-2">
            <PageHeading :heading="'Match Result'"/>

            <div class="max-w-xl bg-white shadow border border-gray-200 rounded-lg p-6 space-y-4">
                <!-- Result Banner -->
                <div v-if="result === 'win'" class="text-green-600 font-semibold text-sm">
                    🎉 You Won!
                    <div class="text-gray-700 text-sm">
                        Opponent: {{ match.opponent }} <br />
                        New Rank: {{ match.newRank }} ({{ match.rankChange > 0 ? '+' : '' }}{{ match.rankChange }})
                    </div>
                </div>

                <div v-else-if="result === 'loss'" class="text-red-600 font-semibold text-sm">
                    ❌ You Lost
                    <div class="text-gray-700 text-sm">
                        Opponent: {{ match.opponent }} <br />
                        Better luck next time.
                    </div>
                </div>

                <div v-else-if="result === 'draw'" class="text-yellow-600 font-semibold text-sm">
                    🤝 It's a Draw!
                    <div class="text-gray-700 text-sm">
                        Opponent: {{ match.opponent }} <br />
                        Tokens refunded.
                    </div>
                </div>

                <div v-else-if="result === 'canceled'" class="text-gray-600 font-semibold text-sm">
                    ⚠️ Match Canceled
                    <div class="text-gray-700 text-sm">
                        This match was canceled before it started. No rank changes applied.
                    </div>
                </div>

                <!-- Common Details -->
                <div v-if="result !== 'canceled'" class="text-sm text-gray-700 pt-3">
                    <p><strong>Tokens Used:</strong> {{ match.tokens }}</p>
                    <p v-if="result === 'win'"><strong>Winnings:</strong> KES {{ match.winnings }}</p>
                    <p><strong>Time Control:</strong> {{ match.timeControl }}</p>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <Link
                        as="button"
                        :href="route('matches.active')"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2 rounded text-sm font-semibold"
                    >
                    Return to Lobby
                    </Link>
                </div>

            </div>
        </main>

        <!-- Bottom nav: mobile only -->
        <MobileNav />
    </div>
</template>



