<script setup lang="ts">
import SidebarNav from '@/components/SidebarNav.vue';
import MobileNav from '@/components/MobileNav.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PageHeading from '@/components/PageHeading.vue';

const props = defineProps(['withdrawalRequest']);

const order = {
    peer: {
        name: props.withdrawalRequest.moderator.name,
        phone: props.withdrawalRequest.moderator.phone,
        status: props.withdrawalRequest.transaction.transaction_stage,
    },
    view_as: props.withdrawalRequest.view_as,
    amount: props.withdrawalRequest.transaction.amount,
    toPhone: props.withdrawalRequest.initiator_user.phone,
    requestor_status:props.withdrawalRequest.transaction.confirmation_status == 0 ? 'Pending' : 'Confirmed',
};

const showConfirmation = ref(false);

const markAsSent = () => {
    if (!showConfirmation.value) {
        showConfirmation.value = true;
        return;
    }

    router.post(
        route('wallet.withdrawal-mark-sent', { id: props.withdrawalRequest.id }),
        {},
        {
            onSuccess: () => {
                alert('Marked as sent.');
                window.location.reload();
            },
            onError: (error) => {
                alert('Failed to mark as sent.');
                console.error(error);
            },
        }
    );
};


const confirmReceipt = () => {
    if (!showConfirmation.value) {
        showConfirmation.value = true;
        return;
    }

    router.post(
        route('wallet.withdrawal-confirm', { id: props.withdrawalRequest.id }),
        {},
        {
            onSuccess: () => {
                // Optional success handling
                alert('Receipt confirmed.');
                window.location.reload();
            },
            onError: (errors) => {
                alert('Failed to confirm receipt.');
                console.error(errors);
            },
        }
    );
};

</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar for desktop -->
        <SidebarNav />

        <!-- Main content -->
        <div class="min-h-screen bg-gray-50 p-2">
            <PageHeading :heading="'Withdrawal (Peer-to-Peer)'" />

            <p class="text-sm text-gray-600 mb-6">Select a peer to process your withdrawal</p>

            <!-- Peer Info -->
            <div class="bg-white border rounded-lg p-5 shadow-sm mb-4">
                <p class="font-medium">Peer Details</p>
                <p>Name: {{ order.peer.name }}</p>
                <p>Phone: {{ order.peer.phone }}</p>
                <p>Status: {{ order.peer.status }}</p>
            </div>

            <!-- Order View -->
            <div class="bg-white border rounded-lg p-5 shadow-sm mb-4">
                <p class="text-sm font-medium mb-2">Withdrawal Order</p>
                <p class="text-sm text-gray-700 mb-2">
                    Amount Requested: <span class="font-semibold">KES {{ order.amount }}</span><br />
                    To: {{ order.toPhone }} <br>
                    Status: {{ order.requestor_status }}
                </p>

                <!-- If viewing as the moderator -->
                <template v-if="order.view_as === 'moderator'">
                    <button
                        class="mt-4 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm font-semibold"
                        @click.prevent="markAsSent"
                    >
                        {{ showConfirmation ? 'Proceed with Confirmation' : 'Mark as Sent' }}
                    </button>

                    <div v-if="!showConfirmation" class="mt-3 text-red-600 text-sm">
                        ⚠️ Please confirm that you have sent the amount to the requester. This action <strong>cannot be reversed</strong>.
                    </div>

                    <div v-if="showConfirmation" class="mt-3 text-yellow-600 text-sm">
                        Are you sure you’ve sent the funds? Click again to proceed, or refresh to cancel.
                    </div>
                </template>

                <!-- If viewing as the initiator -->
                <template v-else>
                    <button
                        class="mt-4 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm font-semibold"
                        @click.prevent="confirmReceipt"
                    >
                        {{ showConfirmation ? 'Proceed with Confirmation' : 'Confirm Receipt' }}
                    </button>

                    <div v-if="!showConfirmation" class="mt-3 text-red-600 text-sm">
                        ⚠️ Please ensure you have received the full amount before confirming. This action <strong>cannot be reversed</strong>.
                    </div>

                    <div v-if="showConfirmation" class="mt-3 text-yellow-600 text-sm">
                        Are you sure you’ve received the funds? Click again to proceed, or refresh to cancel.
                    </div>
                </template>
            </div>

        </div>

        <!-- Bottom nav: mobile only -->
        <MobileNav />
    </div>
</template>

<style scoped>
</style>
