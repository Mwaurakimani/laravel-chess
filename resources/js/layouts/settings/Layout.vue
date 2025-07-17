<script setup lang="ts">
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import MobileNav from '@/components/MobileNav.vue';
import SidebarNav from '@/components/SidebarNav.vue';

const tabs: NavItem[] = [
    { title: 'Profile',  href: '/settings/profile' },
    { title: 'Password', href: '/settings/password' },
];

const page = usePage<{ ziggy?: { location: string } }>();
const currentPath = page.props.ziggy?.location
    ? new URL(page.props.ziggy.location).pathname
    : '';
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <SidebarNav />

        <main class="flex-1  p-6">
            <h1 class="text-2xl font-bold">Settings</h1>

            <!-- Tabs -->
            <nav class="flex space-x-4 border-b border-gray-200">
                <Link
                    v-for="tab in tabs"
                    :key="tab.href"
                    :href="tab.href"
                    class="pb-2 text-gray-600 hover:text-gray-800"
                    :class="{
            'border-b-2 border-blue-600 text-blue-600': currentPath === tab.href
          }"
                >
                    {{ tab.title }}
                </Link>
            </nav>

            <!-- Mobile separator -->
            <Separator class="my-4 md:hidden" />

            <!-- Content slot -->
            <section class="space-y-6">
                <slot />
            </section>
        </main>

        <MobileNav />
    </div>
</template>
