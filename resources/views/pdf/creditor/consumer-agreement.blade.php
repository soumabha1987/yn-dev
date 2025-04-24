@use('Illuminate\Support\Number')
@use('App\Enums\MerchantType')
@use('App\Enums\TransactionStatus')

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name') .  '-' . __('Agreement') }}</title>
    <style>.font-poppins{font-family:Poppins,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,'Noto Sans',sans-serif,'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol','Noto Color Emoji'}h2,p{margin:0}table{border-collapse:collapse;border-color:inherit;text-indent:0}body{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}</style>
</head>
<body class="font-poppins" style="font-size: .875rem;">
    <div style="display: grid;">
        <div style="padding-left: 1.25rem; padding-right: 1.25rem; padding-top: 3rem; padding-bottom: 3rem;">
            <div style="justify-content: space-between;">
                <div style="float: left">
                    <div style="line-height: 2rem;">
                        <img
                            src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('/images/app-logo.png'))) }}"
                            width="200"
                            alt="YouNegotiate"
                        >
                    </div>
                    <div style="padding-top: 0.5rem;">
                        <p>PO Box 27740</p>
                        <p>Las Vegas, NV 89126</p>
                    </div>
                </div>
                <div style="float: right">
                    <h2 style="font-size: 1.5rem; font-weight: 600; color: #4295F6;">
                        {{ __('Consumer Agreement') }}
                    </h2>
                    <div style="padding-top: 0.5rem;">
                        <p>
                            {{ __('Account Number') }}
                            <span style="font-weight: 600;">
                                {{ '#' . $consumer->account_number }}
                            </span>
                        </p>
                        <p>
                            {{ __('Original Balance') }}:
                            <span style="font-weight: 600;">
                                {{ Number::currency((float) $consumer->total_balance) }}
                            </span>
                        </p>
                        <p>
                            {{ __('Settlement Balance') }}:
                            <span style="font-weight: 600;">
                                {{ Number::currency((float) $negotiationCurrentAmount) }}
                            </span>
                        </p>
                        <p>
                            {{ __('Number of Installments') }}:
                            <span style="font-weight: 500;">
                                {{ $scheduleTransactions->count() + $transactions->count() }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div style="clear: both"></div>
            <div style="margin-top: 1.75rem; margin-bottom: 1.75rem; height: 1px; background-color: #e2e8f0;"></div>

            <div style="justify-content: space-between;">
                <div style="float: left;">
                    <h2 style="font-size: 1.125rem; line-height: 1.75rem; font-weight: 500; color: #475569;">
                        {{ __('Creditor Information') }}
                    </h2>
                    <div style="padding-top: 0.5rem;">
                        <p>
                            {{ __('Original Creditor') }}:
                            <span style="font-weight: 600">
                                {{ $consumer->subclient->subclient_name ?? $consumer->company->company_name }}
                            </span>
                        </p>
                        <p>
                            {{ __('Current Creditor') }}:
                            <span style="font-weight: 600">
                                {{ $consumer->company->company_name }}
                            </span>
                        </p>
                        <p>{{ $consumer->company->owner_email }}</p>
                        <p>
                            {!!
                                implode(
                                    ',<br>', array_filter([
                                        $consumer->company->address,
                                        $consumer->company->city,
                                        $consumer->company->state,
                                        $consumer->company->zip
                                    ])
                                )
                            !!}
                        </p>
                    </div>
                </div>

                <div style="float: right">
                    <h2 style="font-size: 1.125rem; line-height: 1.75rem; font-weight: 500; color: #475569;">
                        {{ __('Consumer Information') }}
                    </h2>
                    <div style="padding-top: 0.5rem;">
                        <p style="font-weight: 500;">{{ $consumer->first_name . ' ' . $consumer->last_name }}</p>
                        <p>{{ $consumer->email1 }}</p>
                        <p>{{ $consumer->address1 }} {{ $consumer->city }} {{ $consumer->zip }}</p>
                        <p style="font-weight: 500;">
                            @if($paymentProfile?->method === 'ach')
                                ACH ********* {{ $paymentProfile->account_number }}
                            @elseif($paymentProfile?->method === 'cc')
                                CARD **** **** **** {{ $paymentProfile->last4digit }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div style="clear: both"></div>
            <div style="margin-top: 1.75rem; margin-bottom: 1.75rem; height: 1px; background-color: #e2e8f0;"></div>

            <div style="border: solid 2px; padding: 1.75rem;">
                <p style="font-size: 1.125rem; line-height: 1.75rem; font-weight: 500; color: #475569;">
                    {{ __('e-Sign:') }}
                </p>
                <div style="padding-top: 0.5rem;">
                    <p>
                        {{ __('The below mentioned payment scheudle & terms were accepted & e-Signed by') }}
                        <span style="font-weight: 600; color: #3b82f6;">{{ $consumer->first_name . ' ' . $consumer->last_name }}</span>
                        {{ __('for the settlement of the above mentioned account!') }}
                    </p>
                    <p>{{ __('Date & time on: ') }}
                        <span style="font-weight: 600; color: #3b82f6;">{{ $paymentProfile?->created_at->formatWithTimezone() }}</span>
                    </p>
                    <p>
                        {{ __('Unique e-Sign ID: ') }}
                        <span style="font-weight: 600; color: #3b82f6;">
                            {{ $consumer->id }}-{{ $paymentProfile?->id }}
                        </span>
                    </p>
                </div>
            </div>

            @if ($scheduleTransactions->isNotEmpty())
                <div style="margin-top: 1.75rem; margin-bottom: 1.75rem; height: 1px; background-color: #e2e8f0;"></div>
                <h1 style="text-align: center; font-size: 20px; font-weight: bold;">{{ __('Scheduled Payments') }}</h1>
                <div style="min-width: 100%; overflow-x: auto;">
                    <table style="width: 100%;  text-align: left;">
                        <thead>
                            <tr style="border: 1px solid #e2e8f0; background-color:#f8fafc; color: #1e293b;">
                                <th style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: #e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left;">
                                    {{ __('Scheduled Date') }}
                                </th>
                                <th style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: #e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left;">
                                    {{ __('Payment Method') }}
                                </th>
                                <th style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: #e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left;">
                                    {{ __('Amount') }}
                                </th>
                                <th style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: #e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left;">
                                    {{ __('Status') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($scheduleTransactions as $scheduleTransaction)
                                <tr style="border: 1px solid #e2e8f0;">
                                    <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color:#e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem;">
                                        {{ $scheduleTransaction->schedule_date->format('M d, Y') }}
                                    </td>
                                    <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color:#e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem;">
                                        @if ($scheduleTransaction->paymentProfile?->method === MerchantType::CC)
                                            CARD (xx-{{ $scheduleTransaction->paymentProfile->last4digit}})
                                        @elseif ($scheduleTransaction->paymentProfile?->method === MerchantType::ACH)
                                            ACH (xx-{{ $scheduleTransaction->paymentProfile?->account_number }})
                                        @elseif ($scheduleTransaction->status === TransactionStatus::CANCELLED)
                                            {{ __('Payment made outside') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color:#e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem;">
                                        {{ Number::currency((float) $scheduleTransaction->amount) }}
                                    </td>
                                    <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color:#e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem;">
                                        {{ $scheduleTransaction->status->displayName() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($transactions->isNotEmpty())
                <div style="margin-top: 1.75rem; margin-bottom: 1.75rem; height: 1px; background-color: #e2e8f0;"></div>
                <h1 style="text-align: center; font-size: 20px; font-weight: bold;">{{ __('Processed Payments') }}</h1>
                <div style="min-width: 100%; overflow-x: auto;">
                    <table style="width: 100%;  text-align: left;">
                        <thead>
                            <tr style="border: 1px solid #e2e8f0; background-color:#f8fafc; color: #1e293b;">
                                <th style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: #e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left;">
                                    {{ __('Payment Date') }}
                                </th>
                                <th style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: #e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left;">
                                    {{ __('Payment Method') }}
                                </th>
                                <th style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: #e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left;">
                                    {{ __('Amount') }}
                                </th>
                                <th style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color: #e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem; text-align: left;">
                                    {{ __('Status') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                                <tr style="border: 1px solid #e2e8f0;">
                                    <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color:#e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem;">
                                        @if ($transaction->externalPaymentProfile)
                                            {{ $transaction->externalPaymentProfile->created_at->formatWithTimezone() }}
                                        @else
                                            {{ $transaction->created_at->formatWithTimezone() }}
                                        @endif
                                    </td>
                                    <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color:#e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem;">
                                        @if ($transaction->paymentProfile?->method === MerchantType::CC)
                                            CARD (xx-{{ $transaction->paymentProfile->last4digit }})
                                        @elseif ($transaction->paymentProfile?->method === MerchantType::ACH)
                                            ACH (xx-{{ $transaction->paymentProfile->account_number }})
                                        @elseif ($transaction->externalPaymentProfile?->method === MerchantType::CC)
                                            CARD (xx-{{ $transaction->externalPaymentProfile->last_four_digit }})
                                        @elseif ($transaction->externalPaymentProfile?->method === MerchantType::ACH)
                                            ACH (xx-{{ $transaction->externalPaymentProfile->account_number }})
                                        @endif
                                    </td>
                                    <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color:#e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem;">
                                        {{ Number::currency((float) $transaction->amount) }}
                                    </td>
                                    <td style="white-space: nowrap; font-weight: 600; text-transform: uppercase; border-right-color:#e2e8f0; border-right-style: solid; border-right-width: 1px; padding: 0.75rem;">
                                        {{ $transaction->status->displayName() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div style="margin-top: 1.75rem; margin-bottom: 1.75rem; height: 1px; background-color: #e2e8f0;"></div>
            <div>
                <div style="margin-top: 1rem;">
                    <p style=" font-size: 1.125rem; line-height: 1.75rem; font-weight: 500; color: #475569;">
                        {{ __('Terms & Conditions:') }}
                    </p>
                    <div style="padding-top: 0.5rem;">
                        {!! $contentOfTermsAndConditions !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
