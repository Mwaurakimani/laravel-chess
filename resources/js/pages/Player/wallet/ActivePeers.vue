<script setup lang="ts">
import MobileNav from '@/components/MobileNav.vue';
import SidebarNav from '@/components/SidebarNav.vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import PageHeading from '@/components/PageHeading.vue';

const props = defineProps(['moderators', 'orders']);

const peers = ref(props.moderators);
const orders = ref(props.orders);
const currentTab = ref<'peers' | 'orders'>('peers');
const showRequestForm = ref(false);
const selectedPeer = ref<{ id: number; name: string; phone: string; max: number } | null>(null);

function switchTab(tab: 'peers' | 'orders') {
    currentTab.value = tab;
}

const request = ref({
    amount: '100',
    phone: '0717607177',
});

function openRequestForm(peer: any) {
    selectedPeer.value = peer;
    showRequestForm.value = true;
}

async function submitRequest() {
    showRequestForm.value = false;

    // Null check for selectedPeer
    if (!selectedPeer.value) {
        alert('No peer selected. Please select a peer to request from.');
        return;
    }

    try {
        await axios.post(route('wallet_request.create'), {
            amount: request.value.amount,
            phone: request.value.phone,
            peer_id: selectedPeer.value?.id,
        });

        alert('Request sent successfully.');
        window.location.reload();
    } catch (error: any) {
        console.error('Request failed:', error);

        const message = error.response?.data?.message || 'Failed to send request. Please try again.';

        alert(message);
    }
}
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar for desktop -->
        <SidebarNav />

        <!-- Main content -->
        <div class="min-h-screen bg-gray-50 p-2">
            <PageHeading :heading="'Select Peer'"/>

            <div class="mb-6 flex space-x-4 border-b pb-2">
                <button
                    @click="switchTab('peers')"
                    :class="['text-sm font-medium', currentTab === 'peers' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500']"
                >
                    Peers
                </button>
                <button
                    @click="switchTab('orders')"
                    :class="['text-sm font-medium', currentTab === 'orders' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500']"
                >
                    Orders
                </button>
            </div>

            <div class="space-y-4">
                <!-- Tab: Peers -->
                <div v-if="currentTab === 'peers'" class="space-y-4">
                    <div v-for="peer in peers" :key="peer.phone" class="flex items-center justify-between rounded border bg-white p-4 shadow-sm">
                        <div class="text-xs">
                            <p class="font-semibold">{{ peer.name }}</p>
                            <p class="text-sm text-gray-500">Phone: {{ peer.phone }}</p>
                            <p class="text-sm text-gray-500">Max: KES {{ peer.max.toLocaleString() }}</p>
                        </div>
                        <button class="rounded bg-blue-600 px-4 py-1 text-xs font-medium text-white hover:bg-blue-700" @click="openRequestForm(peer)">
                            Request
                        </button>
                    </div>
                </div>

                <!-- Tab: Orders -->
                <div v-else class="space-y-4">
                    <div v-if="orders.length === 0" class="text-sm text-gray-500">No orders yet.</div>
                    <div v-for="order in orders" :key="order.id" class="space-y-1 rounded border bg-white p-4 text-sm shadow-sm">
                        <p v-if="order.view_as === 'moderator'">
                            <span class="pr-2 font-semibold">Requested by:</span> {{ order.initiatorUser.name }}
                        </p>
                        <p v-else>
                            <span class="pr-2 font-semibold">Requesting from:</span> {{ order.moderator.name }}
                        </p>

                        <p><span class="pr-2 font-semibold">Amount:</span> KES {{ order.transaction.amount }}</p>
                        <p><span class="pr-2 font-semibold">Phone:</span> {{ JSON.parse(order.notes).phone }}</p>
                        <p><span class="pr-2 font-semibold">Status:</span> {{ order.status }}</p>

                        <div class="mt-3">
                            <Link
                                as="button"
                                :href="route('wallet.withdrawal-details', [order.id])"
                                class="rounded bg-blue-600 px-4 py-1 text-xs font-medium text-white hover:bg-blue-700"
                            >
                                View
                            </Link>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Request Withdrawal Modal -->
            <div v-if="showRequestForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
                <div class="w-full max-w-[310px] space-y-4 rounded-lg bg-white p-6">
                    <h2 class="text-lg font-semibold">Request Withdrawal</h2>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Amount (KES)</label>
                        <input v-model="request.amount" type="number" class="mt-1 w-full rounded border px-3 py-2 text-sm" placeholder="1000" />
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Phone Number</label>
                        <input v-model="request.phone" type="tel" class="mt-1 w-full rounded border px-3 py-2 text-sm" placeholder="07XX XXX XXX" />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button class="rounded px-4 py-2 text-sm text-gray-600 hover:text-gray-800" @click="showRequestForm = false">Cancel</button>
                        <button class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white" @click="submitRequest">Send Request</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom nav: mobile only -->
        <MobileNav />
    </div>
</template>

<style lang="scss">
button {
    @apply rounded bg-blue-600 px-4 py-1 text-xs font-medium text-white hover:bg-blue-700;
}
</style>
