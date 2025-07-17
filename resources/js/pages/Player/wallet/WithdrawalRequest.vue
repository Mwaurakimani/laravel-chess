<script setup lang="ts">
import SidebarNav from '@/components/SidebarNav.vue';
import MobileNav from '@/components/MobileNav.vue';
import PageHeading from '@/components/PageHeading.vue';

const withdrawal = {
    amount: 700,
    date: 'June 26, 2025',
    status: 'Completed',
    processor: {
        name: 'Brian O.',
        phone: '07•••••72',
        reference: 'P2PWD20250626BRIAN',
        confirmed: true,
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar for desktop -->
        <SidebarNav />

        <!-- Main content -->
        <div class="min-h-screen bg-gray-50 p-2">
            <PageHeading :heading="'Withdrawal Details'"/>

            <!-- Transaction Info -->
            <div class="bg-white border rounded-lg p-5 shadow-sm space-y-2 max-w-md">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Amount</p>
                    <p class="text-xl font-semibold">KES {{ withdrawal.amount.toLocaleString() }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500 font-medium">Date</p>
                    <p class="text-sm">{{ withdrawal.date }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500 font-medium">Status</p>
                    <p class="text-sm text-green-600 font-medium">
                        ✓ {{ withdrawal.status }}
                    </p>
                </div>
            </div>

            <!-- Processor Info -->
            <div class="bg-white border rounded-lg p-5 shadow-sm mt-4 max-w-md">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Processed by</p>
                    <p class="text-sm font-semibold">{{ withdrawal.processor.name }} ({{ withdrawal.processor.phone }})</p>
                    <p class="text-xs text-gray-500">Reference: {{ withdrawal.processor.reference }}</p>
                    <p class="text-xs text-gray-600 mt-1" v-if="withdrawal.processor.confirmed">Confirmed by User</p>
                </div>
            </div>
        </div>

        <!-- Bottom nav: mobile only -->
        <MobileNav />
    </div>
</template>

<style scoped>

</style>
