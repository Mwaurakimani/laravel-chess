<template>
    <div class="min-h-screen flex items-center justify-center bg-[#f8fbff] px-4">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-md space-y-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900">Create Account</h2>
                <p class="text-sm text-gray-600">Join to play, earn and challenge others</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <input
                        type="text"
                        v-model="form.name"
                        placeholder="Username"
                        autocomplete="username"
                        class="w-full rounded-lg border bg-gray-100 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <input
                        type="email"
                        v-model="form.email"
                        placeholder="Email address"
                        autocomplete="email"
                        class="w-full rounded-lg border bg-gray-100 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <div>
                    <input
                        type="tel"
                        v-model="form.phone"
                        placeholder="Phone number"
                        autocomplete="tel"
                        class="w-full rounded-lg border bg-gray-100 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.phone }"
                    />
                    <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                </div>


                <div>
                    <input
                        type="password"
                        v-model="form.password"
                        placeholder="Password"
                        autocomplete="new-password"
                        class="w-full rounded-lg border bg-gray-100 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.password }"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>

                <div>
                    <input
                        type="password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm Password"
                        autocomplete="new-password"
                        class="w-full rounded-lg border bg-gray-100 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.password_confirmation }"
                    />
                    <p v-if="form.errors.password_confirmation" class="mt-1 text-sm text-red-600">
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>

                <p class="text-xs text-gray-500 text-center">
                    By continuing, you agree to our
                    <a href="#" class="text-blue-600 font-medium hover:underline">Terms & Privacy</a>.
                </p>

                <button
                    type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition disabled:opacity-50"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Creating account...</span>
                    <span v-else>Sign Up</span>
                </button>

                <p class="text-center text-sm text-gray-600">
                    Already have an account?
                    <a :href="route('login')" class="text-blue-600 hover:underline">Log in</a>
                </p>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    phone:'',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
