import './bootstrap'
import '../css/app.css'

import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm'
import alpineUi from '@alpinejs/ui'
import Tom from 'tom-select/dist/js/tom-select.complete.min'
import SimpleBar from 'simplebar'
import flatpickr from 'flatpickr'

import store from './store'
import tooltip from './directives/tooltip'
import inputMask from './directives/inputMask.js'
import breakpoints from './utils/breakpoints'
import notification from './magics/notification'
import confetti from './magics/confetti'

window.Alpine = Alpine
window.flatpickr = flatpickr
window.SimpleBar = SimpleBar
window.Tom = Tom

Alpine.data('loading', () => ({
    visible: false,
    init() {
        this.isSidebarExpanded = this.$store.global.isSidebarExpanded;

        document.addEventListener('livewire:navigate', () => {
            this.visible = true
            this.$store.global.isSidebarExpanded = false
        })

        document.addEventListener('livewire:navigated', () => {
            this.visible = false
        })
    },
}))

document.addEventListener('alpine:init', function () {
    Alpine.magic('notification', () => notification)
    Alpine.directive('tooltip', tooltip)
    Alpine.plugin(alpineUi)
    Alpine.directive('input-mask', inputMask)
    Alpine.magic('confetti', () => confetti)

    Alpine.store('breakpoints', breakpoints)
    Alpine.store('global', store)
})

document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                window.location.href = '/'

                preventDefault()
            }
        })
    })
})

window.addEventListener('popstate', () => window.location.reload())

Livewire.start()
