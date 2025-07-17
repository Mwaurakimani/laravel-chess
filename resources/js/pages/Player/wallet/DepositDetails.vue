<script setup lang="ts">
import MobileNav from '@/components/MobileNav.vue';
import SidebarNav from '@/components/SidebarNav.vue';
import { Link } from '@inertiajs/vue3';
import PageHeading from '@/components/PageHeading.vue';

const deposit = {
    method: 'TinyPesa',
    reference: 'TPX20384834',
    phone: '+254712345678',
    amount: 500,
    tokens: 5,
    date: 'June 30, 2025',
    status: 'Completed',
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar for desktop -->
        <SidebarNav />

        <!-- Main content -->
        <div class="min-h-screen bg-gray-50 p-2">
            <PageHeading :heading="'Deposit Details'"/>

            <!-- Payment Card -->
            <div class="bg-white border rounded-lg p-5 shadow-sm mb-4">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="font-semibold text-gray-800">Deposit via {{ deposit.method }}</p>
                        <p class="text-sm text-gray-600">Amount: KES {{ deposit.amount.toLocaleString() }}</p>
<!--                        <p class="text-sm text-gray-600">Tokens Purchased: <span class="text-red-600">{{ deposit.tokens }} 🎟️</span></p>-->
                        <p class="text-sm text-gray-600">Date: {{ deposit.date }}</p>
                    </div>
                    <div class="text-sm text-green-600 font-medium flex items-center gap-1">
                        <span class="text-green-500">✔</span> {{ deposit.status }}
                    </div>
                </div>
            </div>

            <!-- Reference Info -->
            <div class="bg-white border rounded-lg p-5 shadow-sm mb-6">
                <p class="text-sm text-gray-700">Reference: <span class="font-medium">{{ deposit.reference }}</span></p>
                <p class="text-sm text-gray-700">Phone Number: <span class="font-medium">{{ deposit.phone }}</span></p>
            </div>

            <!-- Download Button -->
            <Link as="button" :href="route('wallet.main')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded font-medium text-sm"
            >
                Back to Wallet
            </Link>
        </div>

        <!-- Bottom nav: mobile only -->
        <MobileNav />
    </div>
</template>
