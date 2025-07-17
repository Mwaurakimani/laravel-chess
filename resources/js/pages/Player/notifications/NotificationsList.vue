<script setup lang="ts">
import MobileNav from '@/components/MobileNav.vue';
import SidebarNav from '@/components/SidebarNav.vue';
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import PageHeading from '@/components/PageHeading.vue';

interface NotificationItem {
    id: number;
    title: string;
    message: string;
    type: string;
    timestamp: string;
    details?: string;
    routeName?: string;
    routeParams?: Record<string, any>;
}

const notifications = ref<NotificationItem[]>([]);
const selectedNotification = ref<NotificationItem | null>(null);

function openNotification(note: NotificationItem) {
    selectedNotification.value = note;
}

function closeNotification() {
    selectedNotification.value = null;
}

async function loadNotifications() {
    try {
        const res = await fetch('/notifications/all', {
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('Failed to load notifications');
        notifications.value = await res.json();
    } catch (e) {
        console.error('loadNotifications:', e);
    }
}

onMounted(() => {
    loadNotifications();
});
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar for desktop -->
        <SidebarNav />

        <!-- Main content -->
        <div class="min-h-screen bg-gray-50 p-2">
            <PageHeading :heading="'Notifications'"/>


            <p class="mb-4 text-sm text-gray-600">Messages and updates about your activity</p>

            <div class="space-y-3">
                <div
                    v-for="note in notifications"
                    :key="note.id"
                    @click="openNotification(note)"
                    class="flex cursor-pointer items-center justify-between rounded-lg border bg-white px-4 py-3 hover:bg-gray-100"
                >
                    <div class="text-sm text-gray-700">
                        <p class="font-medium">{{ note.title }}</p>
                        <p class="text-xs text-gray-500">{{ note.message }}</p>
                    </div>
<!--                    <span class="text-xs text-gray-400">{{ note }}</span>-->
                </div>
            </div>

            <!-- Notification Detail Modal -->
            <div
                v-if="selectedNotification"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/30"
                @click.self="closeNotification"
            >
                <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-lg font-semibold">{{ selectedNotification.title }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ selectedNotification.timestamp }}</p>
                        </div>
                        <button @click="closeNotification" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <p class="mt-4 text-sm whitespace-pre-line text-gray-700">
                        {{ selectedNotification.details || selectedNotification.message }}
                    </p>

                    <div class="mt-6 text-right space-x-2">
                        <Link
                            v-if="selectedNotification.routeName"
                            :href="route(selectedNotification.routeName, selectedNotification.routeParams)"
                            class="rounded bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                        >
                            View
                        </Link>
                        <button
                            @click="closeNotification"
                            class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom nav: mobile only -->
        <MobileNav />
    </div>
</template>
