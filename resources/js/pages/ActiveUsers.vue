<script setup lang="ts">
import SidebarNav from '@/components/SidebarNav.vue';
import MobileNav from '@/components/MobileNav.vue';
import { ref } from 'vue';

defineProps(['user']);

const onlineUsers = ref<any[]>([]);

</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar for desktop -->
        <SidebarNav />

        <!-- Main content -->
        <main class="flex-1 p-2">
            <h1 class="text-2xl font-bold">Active Users</h1>
            <p>Welcome, {{ user.name }}</p>
            <div v-if="onlineUsers.length > 0">
                <ul>
                    <li class="mb-1 w-[300px] p-4 shadow" v-for="item in onlineUsers" :key="item.id">
                        <div class="flex">
                            <h4 class="w-[150px]">Name:</h4>
                            <p>{{ item.name }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </main>

        <!-- Bottom nav: mobile only -->
        <MobileNav />
    </div>
</template>
