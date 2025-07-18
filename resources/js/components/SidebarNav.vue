<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CreditCard, Home, Joystick, Settings } from 'lucide-vue-next';

const isActive = (routeName: string) => {
    const currentRoute = route().current();
    return currentRoute === routeName || currentRoute.startsWith(routeName + '.');
};
</script>

<template>
    <aside class="hidden w-64 flex-col space-y-6 bg-slate-900 px-4 py-6 text-white md:flex">
        <nav class="space-y-3">
            <h2 class="text-lg font-semibold">Dashboard</h2>
            <ul class="space-y-2">
                <li class="px-1 py-2">
                    <a :href="route('dashboard')" class="flex space-x-1" :class="{ 'text-blue-400': isActive('dashboard') }">
                        <Home class="h-6 w-6" :class="{ 'text-blue-600': isActive('dashboard') }" />
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="px-1 py-2">
                    <Link :href="route('matches.active')" class="flex space-x-1" :class="{ 'text-blue-400': isActive('matches') }">
                        <Joystick class="h-6 w-6" :class="{ 'text-blue-600': isActive('matches') }" />
                        <span>Matches</span>
                    </Link>
                </li>
                <li class="px-1 py-2">
                    <Link :href="route('wallet.main')" class="flex space-x-1" :class="{ 'text-blue-400': isActive('wallet') }">
                        <CreditCard class="h-6 w-6" :class="{ 'text-blue-600': isActive('wallet') }" />
                        <span>Wallet</span>
                    </Link>
                </li>
                <li class="px-1 py-2">
                    <Link :href="route('profile.edit')" class="flex space-x-1" :class="{ 'text-blue-400': isActive('profile') }">
                        <Settings class="h-6 w-6" :class="{ 'text-blue-600': isActive('profile.edit') || isActive('password.edit') }" />
                        <span>Settings</span>
                    </Link>
                </li>
            </ul>
        </nav>
    </aside>
</template>
