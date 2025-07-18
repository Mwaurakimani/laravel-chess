<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CreditCard, Home, Joystick, Settings } from 'lucide-vue-next';

const isActive = (routeName: string) => {
    const currentRoute = route().current();
    return currentRoute === routeName || currentRoute.startsWith(routeName + '.');
};


</script>

<template>
    <nav class="fixed right-0 bottom-0 left-0 z-50 flex justify-around border-t bg-white py-3 shadow md:hidden">

        <a :href="route('dashboard')" class="flex flex-col items-center text-gray-600 hover:text-blue-600">
            <Home class="h-6 w-6" :class="{ 'text-blue-600': isActive('dashboard') }" />
        </a>

        <Link :href="route('matches.active')" class="flex flex-col items-center text-gray-600 hover:text-blue-600">
            <Joystick class="h-6 w-6" :class="{ 'text-blue-600': isActive('matches') }" />
        </Link>

        <Link :href="route('wallet.main')" class="flex flex-col items-center text-gray-600 hover:text-blue-600">
            <CreditCard class="h-6 w-6" :class="{ 'text-blue-600': isActive('wallet') }" />
        </Link>

        <Link :href="route('profile.edit')" class="flex flex-col items-center text-gray-600 hover:text-blue-600">
            <Settings class="h-6 w-6" :class="{ 'text-blue-600': isActive('profile.edit') || isActive('password.edit') }" />
        </Link>

    </nav>
</template>
