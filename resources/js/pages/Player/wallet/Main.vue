<script setup lang="ts">
import MobileNav from '@/components/MobileNav.vue';
import SidebarNav from '@/components/SidebarNav.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import TokensTransactions from '@/pages/Player/wallet/Components/TokensTransactions.vue';
import WalletTransactions from '@/pages/Player/wallet/Components/WalletTransactions.vue';
import MatchTransactions from '@/pages/Player/wallet/Components/MatchTransactions.vue';
import PageHeading from '@/components/PageHeading.vue';

// Props from backend
const props = defineProps<{
    wallet: {
        balance: number;
        tokens: number;
        transactions: { tokens: number; date: string; note?: string }[];
        depositTransactions: { type: string; amount: number; date: string }[];
        matchTransactions: { opponent: string; result: string; tokens: number; amount: number; date: string }[];
    };
}>();

const currentTab = ref<'tokens' | 'matches' | 'deposits'>('tokens');
</script>


<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar for desktop -->
        <SidebarNav />

        <!-- Main content -->
        <main class="flex-1  p-2">
            <PageHeading :heading="'Wallet'" />

            <!-- Dark Balance Banner -->
            <div class="flex items-center justify-between rounded-xl bg-gray-900 p-5 text-white">
                <div>
                    <p class="text-sm text-gray-400">Balance</p>
                    <p class="text-2xl font-bold">KES {{ props.wallet.balance.toLocaleString() }}</p>
                </div>
                <div class="flex flex-col gap-2">
                    <Link
                        as="button"
                        :href="route('wallet.deposit')"
                        class="rounded bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600"
                    >
                        Deposit
                    </Link>
                    <Link as="button" :href="route('wallet.active-peers')" class="rounded bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-200">
                        Withdraw
                    </Link>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-2">
                <button
                    @click="currentTab = 'tokens'"
                    :class="currentTab === 'tokens' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="rounded px-4 py-2 text-sm font-medium"
                >
                    Token
                </button>
                <button
                    @click="currentTab = 'matches'"
                    :class="currentTab === 'matches' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="rounded px-4 py-2 text-sm font-medium"
                >
                    Match
                </button>
                <button
                    @click="currentTab = 'deposits'"
                    :class="currentTab === 'deposits' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="rounded px-4 py-2 text-sm font-medium"
                >
                    Transaction
                </button>
            </div>

            <TokensTransactions
                v-if="currentTab === 'tokens'"
                :transactions="props.wallet.transactions"
                :tokens="props.wallet.tokens"
            />

            <WalletTransactions
                v-if="currentTab === 'deposits'"
                :depositTransactions="props.wallet.depositTransactions"
            />

            <MatchTransactions
                v-if="currentTab === 'matches'"
                :matchTransactions="props.wallet.matchTransactions"
            />

        </main>

        <!-- Bottom nav: mobile only -->
        <MobileNav />
    </div>
</template>
