// resources/js/app.ts
import '../css/app.css'

import { createApp, h } from 'vue'
import { createInertiaApp, usePage } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import type { DefineComponent } from 'vue'
import { ZiggyVue } from 'ziggy-js'
import axios from 'axios'
import { initializeTheme } from './composables/useAppearance'
import {  initPresence } from './Service/echo';
import { echo } from './Service/echo'

// ————————————————————————————————————————————————————————————
// 1) Axios defaults for session cookies & XSRF
// ————————————————————————————————————————————————————————————
axios.defaults.baseURL        = import.meta.env.VITE_APP_URL || 'http://localhost:8000'
axios.defaults.withCredentials = true
axios.defaults.xsrfCookieName  = 'XSRF-TOKEN'
axios.defaults.xsrfHeaderName  = 'X-XSRF-TOKEN'

createInertiaApp({
    title: title =>
        title
            ? `${title} – ${import.meta.env.VITE_APP_NAME}`
            : import.meta.env.VITE_APP_NAME,
    resolve: name =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el)

        // ————————————————————————————————————————————————————————————
        // 2) REAL-TIME PRESENCE: only if the user is logged in
        // ————————————————————————————————————————————————————————————

        const page = usePage<{ auth: { user: { id: number } | null } }>()

        if (page.props.auth.user) {

            initPresence()

            const userId = page.props.auth.user.id

            echo.private(`App.Models.User.${userId}`)
                .listen('ChallengeAcceptedNow', (payload: { challenge_id: number; message: string }) => {
                    alert(payload.message)
                    window.location.href = route('matches.ready', payload.challenge_id)
                })

        }

        // ————————————————————————————————————————————————————————————
        // 3) Initialize theme (light/dark) after everything
        // ————————————————————————————————————————————————————————————
        initializeTheme()

        return vueApp
    },
    progress: {
        color: '#4B5563',
    },
})
