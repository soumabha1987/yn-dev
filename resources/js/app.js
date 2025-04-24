import './bootstrap'
import '../css/app.css'

import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm'
import alpineUi from '@alpinejs/ui'
import Quill from 'quill'
import SimpleBar from 'simplebar'
import flatpickr from 'flatpickr'

import ImageResize from 'quill-image-resize'

import { ImageFormat } from './quill-image-format'

import breakpoints from './utils/breakpoints'
import tooltip from './directives/tooltip'
import notification from './magics/notification'
import confetti from './magics/confetti'
import { sort } from '@alpinejs/sort'
import Tom from 'tom-select/dist/js/tom-select.complete.min'

Quill.register('modules/imageResize', ImageResize)
Quill.register(ImageFormat, true)

window.Alpine = Alpine
window.Quill = Quill
window.SimpleBar = SimpleBar
window.flatpickr = flatpickr
window.Tom = Tom

document.addEventListener('alpine:init', () => {
    Alpine.store('breakpoints', breakpoints)
    Alpine.plugin(alpineUi)
    Alpine.plugin(sort)

    Alpine.store('global', {
        isSidebarExpanded: false,
        init() {
            this.isSidebarExpanded = Alpine.store('breakpoints').xlAndUp;

            Alpine.effect(() => {
                this.isSidebarExpanded
                    ? document.body.classList.add('is-sidebar-open')
                    : document.body.classList.remove('is-sidebar-open')
            })
        },
    })

    Alpine.data('loading', () => ({
        visible: false,
        init() {
            document.addEventListener('livewire:navigate', () => {
                this.visible = true
            })

            document.addEventListener('livewire:navigated', () => {
                this.visible = false
                Alpine.store('global').isSidebarExpanded = true
                Alpine.store('global').isSidebarExpanded = !Alpine.store('breakpoints').lgAndDown
            })

            window.addEventListener('resize', (e) => {
                e.preventDefault()
                Alpine.store('global').isSidebarExpanded = !(e.currentTarget.screen.availWidth < 1025)
            })
        },
    }))

    Alpine.store('sidebar', {
        collapsedGroups: Alpine.$persist([]).as('collapsedGroups'),

        groupIsCollapsed: function (group) {
            return this.collapsedGroups.includes(group)
        },

        collapseGroup: function (group) {
            if (this.collapsedGroups.includes(group)) return

            this.collapsedGroups = this.collapsedGroups.concat(group)
        },

        toggleCollapsedGroup: function (group) {
            this.collapsedGroups = this.collapsedGroups.includes(group)
                ? this.collapsedGroups.filter((collapsedGroup) => collapsedGroup !== group)
                : this.collapsedGroups.concat(group)
        },

        removeCollapsedGroup: function (group) {
            this.collapsedGroups = this.collapsedGroups.includes(group)
                ? []
                : [group]
        }
    })

    Alpine.directive('tooltip', tooltip)
    Alpine.magic('notification', () => notification)
    Alpine.magic('confetti', () => confetti)

    Alpine.data('accordionItem', (id) => ({
        acc_id: id,
        get expanded() {
            return this.expandedItem === this.acc_id;
        },
        set expanded(val) {
            this.expandedItem = val ? this.acc_id : null;
        }
    }))
})

window.addEventListener('popstate', () => window.location.reload())

Livewire.start()
