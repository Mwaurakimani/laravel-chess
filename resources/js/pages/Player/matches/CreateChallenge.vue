<script setup lang="ts">
import MobileNav from '@/components/MobileNav.vue';
import SidebarNav from '@/components/SidebarNav.vue';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PageHeading from '@/components/PageHeading.vue';

const isEditing = ref(false); // toggle to true for edit mode

const form = useForm({
    stake: 0,
    platform: 'chess.com',
    timeControl: '5+0 Blitz',
});

const handlePostChallenge = () => {
    if (form.processing) return;
    form.clearErrors();
    form.post(route('matches.store-challenge'), {
        preserveScroll: true,
        onError: () => {
            // Scroll to first error field or show toast if needed
        },
    });
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar for desktop -->
        <SidebarNav />

        <main class="flex-1 p-6">
            <PageHeading :heading="'Create Challenge'"/>

            <div class="max-w-xl space-y-4 rounded-lg bg-white p-6 shadow">

                <!-- Stake -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Stake Amount (KES)</label>
                    <input
                        v-model.number="form.stake"
                        type="number"
                        min="10"
                        placeholder="Minimum 10 KES"
                        class="w-full rounded border px-3 py-2 text-sm focus:border-blue-500 focus:ring"
                        :class="{ 'border-red-500': form.errors.stake }"
                    />
                    <p v-if="form.errors.stake" class="text-sm text-red-600 mt-1">{{ form.errors.stake }}</p>
                </div>

                <!-- Platform -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Platform</label>
                    <select
                        v-model="form.platform"
                        class="w-full rounded border px-3 py-2 text-sm focus:border-blue-500 focus:ring"
                        :class="{ 'border-red-500': form.errors.platform }"
                    >
                        <option value="chess.com">Chess.com</option>
                    </select>
                    <p v-if="form.errors.platform" class="text-sm text-red-600 mt-1">{{ form.errors.platform }}</p>
                </div>

                <!-- Time Control -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Time Control</label>
                    <select
                        v-model="form.timeControl"
                        class="w-full rounded border px-3 py-2 text-sm focus:border-blue-500 focus:ring"
                        :class="{ 'border-red-500': form.errors.timeControl }"
                    >
                        <option value="5+0 Blitz">5+0 Blitz</option>
                    </select>
                    <p v-if="form.errors.timeControl" class="text-sm text-red-600 mt-1">{{ form.errors.timeControl }}</p>
                </div>

                <!-- Submit Button -->
                <div class="mt-4 flex justify-between">
                    <button
                        :disabled="form.processing"
                        @click.prevent="handlePostChallenge"
                        class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ isEditing ? 'Update Challenge' : 'Post Challenge' }}
                    </button>
                </div>
            </div>
        </main>

        <!-- Bottom nav: mobile only -->
        <MobileNav />
    </div>
</template>
