<div>
    <main class="w-full pb-8">
        <div class="card p-3">
            <div class="flex items-center space-x-4 py-5 lg:py-6">
                <h2 class="text-xl mx-auto font-bold text-slate-800 lg:text-2xl">
                    {{ $consumer->first_name . ' ' . $consumer->last_name }}'s
                    {{ __('Secure Self Service Account Access') }}
                </h2>
            </div>
            <div class="flex items-center">
                <h3 class="text-md mx-auto font-medium text-slate-800 lg:text-xl">
                    {{ __('Creditor Name(s) associated with this account') }}:
                    <span class="text-primary font-semibold">
                        @if ($consumer->subclient)
                            {{ $consumer->subclient->subclient_name }}/
                        @endif
                        {{ $consumer->company->company_name . ', ' . $consumer->original_account_name }}
                    </span>
                </h3>
            </div>

            <div class="flex flex-1 flex-col justify-between rounded-lg p-4 sm:p-5">
                <div class="w-3/4 justify-center mx-auto content-center">
                    <div>
                        <h3 class="mt-3 font-medium text-slate-600 text-md text-justify">
                            {{ __('Your account has been placed on the YouNegotiate® platform to provide you 24/7 self service access to view discount terms, make payments, negotiate a plan you can afford, send offers, and manage your payment plans to support your life circumstances. YouNegotiate® is an independent platform creditors use to provide their consumers 24/7 access to their accounts and set up communication controls.') }}
                        </h3>
                        <h3 class="mt-3 font-medium text-slate-600 text-md text-center">
                            {{ __('If we can help you in any way, please email our Rocking YouNegotiate Help Team at help@younegotiate.com!') }}
                        </h3>
                        <h3 class="mt-3 font-medium text-slate-600 text-md text-center">
                            {{ __('Please enter the last 4 digits of your SSN to authenticate your identity and access your account') }}
                        </h3>
                    </div>
                </div>

                <div class="flex justify-center my-5">
                    <div class="flex items-center space-x-2">
                        <div>
                            <img class="w-32" src="{{ asset('images/search.png') }}" />
                        </div>
                        <form
                            x-data="verifySsn"
                            x-effect="resetRecaptcha"
                            method="POST"
                            wire:submit="checkSsn"
                            autocomplete="off"
                        >
                            <div class="mt-3">
                                <p class="text-md text-center max-w-xl font-medium text-slate-600 lg:text-lg">
                                    {{ __('Please enter the last 4 digits of your SSN to authenticate your identity and access your account') }}
                                </p>
                                <div class="mt-3">
                                    <label>
                                        <input
                                            type="text"
                                            wire:model="form.last_four_ssn"
                                            class="w-full rounded-lg border border-slate-300 px-3 py-2 hover:border-slate-400 focus:border-primary"
                                            placeholder="{{ __('Your Last 4 Digit Of Social Security Number') }}"
                                            autocomplete="off"
                                        />
                                    </label>
                                    <div class="mt-1">
                                        @error('form.last_four_ssn')
                                            <span class="text-error">
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="flex">
                                    <div
                                        wire:ignore
                                        data-callback="handleCaptcha"
                                        class="g-recaptcha mt-3"
                                        data-sitekey="{{ config('services.google_recaptcha.site_key') }}"
                                    ></div>
                                </div>
                                <div class="mt-1">
                                    @error('form.recaptcha')
                                        <span class="text-error">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="flex items-center mt-3 justify-center">
                                    <button
                                        type="submit"
                                        class="btn bg-primary font-medium text-white text-md hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                                    >
                                        {{ __('Validate My Account') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div>
                    <h3 class="mt-3 font-medium text-slate-600 text-md">
                        {{ __('YouNegotiate®, is owned and patented by H2H Technologies. This platform was built with and for consumers, to help us gain control over our accounts and live our best financial life.') }}
                    </h3>
                    <div class="flex justify-center inline-space mt-5">
                        <div class="w-16">
                            <img class="mask" src="{{ asset('images/techLock.png') }}" />
                        </div>

                        <div class="w-32">
                            <img class="mask" src="{{ asset('images/pci2.png') }}" />
                        </div>

                        <div class="w-32">
                            <img class="mask is-squircle" src="{{ asset('images/aws.png') }}" />
                        </div>

                        <div class="w-16">
                            <img class="mask" src="{{ asset('images/truste.jpeg') }}" />
                        </div>

                        <div class="w-20">
                            <img class="mask" src="{{ asset('images/verisign.png') }}" />
                        </div>

                        <div class="w-16">
                            <img class="mask" src="{{ asset('images/rsa.png') }}" />
                        </div>
                    </div>
                    <h3 class="mt-3 font-bold text-slate-600 text-md">
                        {{ __('Bank-level security') }}
                    </h3>
                    <h3 class="mt-3 font-medium text-slate-600 text-md">
                        {{ __('Your account is protected using the same 128-bit encryption and physical security used by leading financial institutions. All practices are monitored and verified by TRUSTe and VeriSign,') }}
                    </h3>

                    <h3 class="mt-3 font-bold text-slate-600 text-md">
                        {{ __('Ways You Can Accesss Your Account(s)') }}:
                    </h3>
                    <ul>
                        <li>&bull;&nbsp;&nbsp;&nbsp;&nbsp;
                            {{ __('Secure link or QR code, authenticate using the last digits of your SSN') }}</li>
                        <li>&bull;&nbsp;&nbsp;&nbsp;&nbsp;
                            {{ __('Visit the creditor website to access your account') }} </li>
                        <li>&bull;&nbsp;&nbsp;&nbsp;&nbsp;
                            {{ __('Visit www.younegotiate.com from your browser or creditor URL link, create a profile to view all accounts matching your name and SSN number') }}
                        </li>
                    </ul>

                    <h3 class="mt-3 font-medium text-slate-600 text-md">
                        {{ __('Security and privacy are very important. If you recognize this creditor(s), we appreciate one final level of confirmation to provide you access. Please enter the last four of your social.') }}
                    </h3>
                </div>
            </div>
        </div>
    </main>
    @script
        <script>
            Alpine.data('verifySsn', () => ({
                init () {
                    window.handleCaptcha = this.handleCaptcha

                    let script = document.createElement('script')
                    script.src = 'https://www.google.com/recaptcha/api.js'
                    script.async = true
                    script.defer = true
                    window.document.body.appendChild(script)
                },
                resetRecaptcha() {
                    if ($wire.resetCaptcha) {
                        grecaptcha.reset()
                        $wire.form.recaptcha = ''
                        $wire.resetCaptcha = false
                    }
                },
                handleCaptcha (response) {
                    $wire.form.recaptcha = response
                },
            }))
        </script>
    @endscript
</div>
