<script setup lang="ts">
import MobileNav from '@/components/MobileNav.vue';
import SidebarNav from '@/components/SidebarNav.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PageHeading from '@/components/PageHeading.vue';

const currentTab = ref('deposit');

const wallet = {
    balance: 5000,
};

const depositForm = ref({
    method: 'TinyPesa (M-Pesa STK Push)',
    amount: 0,
    phone: '',
});

const handleDeposit = () => {
    router.visit(route('wallet.deposit-details', [1]));
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar for desktop -->
        <SidebarNav />

        <!-- Main content -->
        <main class="flex-1  p-2">
            <PageHeading :heading="'Deposit Details'" />
            <p class="text-sm text-gray-600">Review and confirm your deposit</p>

            <!-- Dark Banner -->
            <div class="flex items-center justify-between rounded-xl bg-gray-900 p-5 text-white">
                <div>
                    <p class="text-sm text-gray-400">Current Balance</p>
                    <p class="text-2xl font-bold">KES {{ wallet.balance.toLocaleString() }}</p>
                </div>
            </div>

            <!-- Deposit Form -->
            <div v-if="currentTab === 'deposit'" class="max-w-xl space-y-4 rounded-lg border bg-white p-6">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Payment Method</label>
                    <input type="text" v-model="depositForm.method" readonly class="w-full rounded border bg-gray-100 px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Amount to Deposit</label>
                    <input
                        type="text"
                        v-model="depositForm.amount"
                        class="w-full rounded border px-3 py-2 text-sm focus:border-blue-500 focus:ring"
                        placeholder="KES 1000"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Phone Number</label>
                    <input
                        type="tel"
                        v-model="depositForm.phone"
                        class="w-full rounded border px-3 py-2 text-sm focus:border-blue-500 focus:ring"
                        placeholder="2547XX XXX XXX"
                    />
                </div>

                <button @click.prevent="handleDeposit" class="rounded bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Deposit Now
                </button>
            </div>
        </main>

        <!-- Bottom nav: mobile only -->
        <MobileNav />
    </div>
</template>
