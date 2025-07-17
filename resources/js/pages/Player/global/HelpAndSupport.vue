<script setup lang="ts">
import SidebarNav from '@/components/SidebarNav.vue';
import MobileNav from '@/components/MobileNav.vue';
import PageHeading from '@/components/PageHeading.vue';
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar for desktop -->
        <SidebarNav />

        <!-- Main content -->
        <main class="flex-1 p-2 ">
            <PageHeading :heading="'Help and Support'"/>

            <!-- Your content here -->

        </main>

        <!-- Bottom nav: mobile only -->
        <MobileNav />
    </div>
</template>

<style scoped>

</style>
