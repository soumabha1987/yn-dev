export default {
    isSidebarExpanded: false,

    init() {
        this.isSidebarExpanded =
            document.querySelector('.sidebar') &&
            document.body.classList.contains('is-sidebar-open') &&
            Alpine.store('breakpoints').lgAndDown

        Alpine.effect(() => {
            this.isSidebarExpanded
                ? document.body.classList.add('is-sidebar-open')
                : document.body.classList.remove('is-sidebar-open')
        })

        window.addEventListener('resize', (e) => {
            e.preventDefault()
            this.isSidebarExpanded = false
        })
    },
}
