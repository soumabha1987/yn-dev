<div>
    @auth
        <script type="text/javascript">
            (function () {
                window.__insp = window.__insp || []
                __insp.push(
                    ['wid', '{{ config('services.inspectlet.user_id') }}'],
                    ['identify', '{{ auth()->user()->last_name . '_' . auth()->user()->dob->toDateString() . '_' . auth()->user()->last4ssn }}'],
                    ['tagSession', {
                        consumer_id: '{{ auth()->id() }}',
                        company_id: '{{ auth()->user()->company_id }}',
                        subclient_id: '{{ auth()->user()->subclient_id }}',
                    }]
                )

                var ldinsp = function () {
                    if (typeof window.__inspld != 'undefined') return
                    window.__inspld = 1
                    var insp = document.createElement('script')
                    insp.type = 'text/javascript'
                    insp.async = true
                    insp.id = 'inspsync'
                    insp.src = ('https:' === document.location.protocol ? 'https' : 'http') + '://cdn.inspectlet.com/inspectlet.js?wid='+ '{{ config('services.inspectlet.user_id') }}' + '&r=' + Math.floor(new Date().getTime() / 3600000)
                    var script = document.getElementsByTagName('script')[0]
                    script.parentNode.insertBefore(insp, script)
                }

                setTimeout(ldinsp, 0)
            })()
        </script>
    @endauth
</div>
