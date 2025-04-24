@use('Illuminate\Support\Number')
@use('App\Enums\MerchantType')

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <title>{{ config('app.name') .  '-' . __('Agreement') }}</title>
    <style>.font-poppins{font-family:Poppins,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,'Noto Sans',sans-serif,'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol','Noto Color Emoji'}h2,p{margin:0}table{border-collapse:collapse;border-color:inherit;text-indent:0}body{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}</style>
</head>
<body class="font-poppins" style="font-size: .875rem;">
    <style>
        .banner {
            position: relative;
            width: 100%;
            max-width: 64rem;
            height: 150px; 
            margin: 2rem auto 0;
            overflow: hidden;
        }

        .banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>

    <div style="display: grid;">
        <div style="padding-left: 1.25rem; padding-right: 1.25rem; padding-top: 3rem; padding-bottom: 3rem;">
            <div style="display: flex; align-items: center; flex-direction: column; text-align: center; gap: 4px">
                <img src="images/dfa.png" style="width: 12rem;" alt="dfa-logo">
                <p style="font-size: 1.5rem">{{ __('YouNegotiate Debt Resolution Platform') }}</p>
                <p style="font-size: 1.5rem">{{ __('Donation Tax Receipt') }}</p>
            </div>

            <div style="margin-top: 2rem; max-width: 64rem; margin-left: auto; margin-right: auto;">
                <table style="width: 100%; text-align: left; font-size: 0.75rem">
                    <tr style="border: 1px solid black; color: #1e293b;">
                        <td style="font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left; color: #232f6c">
                            <span style="font-weight: bold">{{ __("Date:") }}</span>
                        </td>
                        <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; color: #2563eb">
                            {{ $externalPaymentProfile->created_at->formatWithTimezone() }}
                        </td>
                    </tr>

                    <tr style="border: 1px solid black; color: #1e293b;">
                        <td style="font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left; color: #232f6c">
                            <span style="font-weight: bold">{{ __("Donation Amount:") }}</span>
                        </td>
                        <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; color: #2563eb">
                            {{ Number::currency((float) $externalPaymentProfile->transaction->amount) }}
                        </td>
                    </tr>

                    <tr style="border: 1px solid black; color: #1e293b;">
                        <td style="font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left; color: #232f6c">
                            <span style="font-weight: bold">{{ __("Donor Full Name:") }}</span>
                        </td>
                        <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; color: #2563eb">
                            {{ $externalPaymentProfile->first_name . ' ' . $externalPaymentProfile->last_name }}
                        </td>
                    </tr>

                    <tr style="border: 1px solid black; color: #1e293b;">
                        <td style="font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left; color: #232f6c">
                            <span style="font-weight: bold">{{ __("The full donation will be donated to help consumer account:") }}</span>
                        </td>
                        <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; color: #2563eb">
                            {{ 'xxxx-xxxx-xxxx-' . substr($consumer->member_account_number ?? $consumer->account_number, -4) }}
                        </td>
                    </tr>

                    <tr style="border: 1px solid black; color: #1e293b;">
                        <td style="font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left; color: #232f6c">
                            <span style="font-weight: bold">{{ __("Consumer Full Name:") }}</span>
                        </td>
                        <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; color: #2563eb">
                            <span>{{ $consumer->first_name . ' ' . $consumer->last_name }}</span>
                        </td>
                    </tr>

                    <tr style="border: 1px solid black; color: #1e293b;">
                        <td style="font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left; color: #232f6c">
                            <span style="font-weight: bold">{{ __("Date of Birth:") }}</span>
                        </td>
                        <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; color: #2563eb">
                            <span>{{ $consumer->dob->format('M d, Y') }}</span>
                        </td>
                    </tr>

                    <tr style="border: 1px solid black; color: #1e293b;">
                        <td style="font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left; color: #232f6c">
                            <span style="font-weight: bold">{{ __("Last 2 SSN:") }}</span>
                        </td>
                        <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: black; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; color: #2563eb">
                            <span>{{ 'xx' . substr($consumer->last4ssn, -2) }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="font-size: 0.825rem; max-width: 64rem; margin-left: auto; margin-right: auto; margin-top: 2rem">
                <p>*{{ __('If the donator name matches the consumer name this is not a tax deductible payment and does not meet the IRS donation requirements.' )}}</p>
                <p style="margin-top: 4px">{{ __('DFA certifies it provided no goods or services in exchange for this contribution. 100% of all donations made as part of the DFA-YouNegotiate partnership will be applied to help Americans resolve delinquent balances to prevent wage garnishment, bank levy, car respossession and other pentalties associated with delinquent account balances.') }}</p>
            </div>

            <div style="font-size: 0.825rem; max-width: 64rem; margin-left: auto; margin-right: auto; margin-top: 4rem">
                <div style="display: flex; flex-direction: column; gap: 2rem; text-align: center">
                    <p>Debt Free Americans “DFA”</p>
                    <p style="margin-top: 4px">5470 Kiryzke, Suite 300, Reno NV 89511</p>
                    <p style="font-weight: bolder; margin-top: 4px">EIN: 99-2948019</p>
                </div>
            </div>

            <div class="banner">
                <img src="images/recipt-banner-dfa.png" alt="Receipt Banner">
            </div>
        </div>
    </div>
</body>
</html>
