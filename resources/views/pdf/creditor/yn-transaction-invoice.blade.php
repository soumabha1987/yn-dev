<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name') .  '-' . __('Invoice') }}</title>
</head>
<body class="font-poppins" style="font-size: .875rem;">
<div style="display: grid; padding: 20px;">
    <div style="text-align: center; font-size: 24px; margin-bottom: 20px;">
        {{__('Invoice')}}
    </div>
    <table style="border-bottom: 2px solid #ddd; width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="padding: 10px;">
                <img
                    src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('/images/app-logo.png'))) }}"
                    width="200"
                    alt="YouNegotiate"
                >
                <br>
            </td>
            <td style="text-align: right; padding: 10px;">
                <strong>{{__('Invoice No.: ')}} </strong>{{'Y'.$ynTransaction->id}}<br>
                <strong>{{__('Date:')}}</strong> {{ $ynTransaction->created_at->formatWithTimezone() }}
            </td>
        </tr>
    </table>

    <table style="border-bottom: 2px solid #ddd; width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="padding: 10px;">
                <strong>{{__('Bill To: ')}}</strong> {{ str($ynTransaction->company->company_name)->title() }}
                <br>
            </td>
            <td style="text-align: right; padding: 10px;">
                <strong> {{ __('Billing Period: ') }}</strong>
                {{ $ynTransaction->billing_cycle_start->formatWithTimezone() . __(' To ') .  $ynTransaction->billing_cycle_end->formatWithTimezone()}} <br>
            </td>
        </tr>
    </table>

    <table style="width: 100%; margin-bottom: 20px;" cellspacing="0" cellpadding="0">
        <thead>
            <tr style="background-color:#f8fafc; color: #1e293b;">
                <th style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem; text-align: left;">#</th>
                <th style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem; text-align: left;">{{__('Description')}}</th>
                <th style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem; text-align: left;">{{__('Quantity/Amount')}}</th>
                <th style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem; text-align: left;">{{__('Rate')}}</th>
                <th style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem; text-align: left;">{{__('Total')}}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">1</td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ __('YouNegotiate Consumer Payment %') }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ filled($ynTransaction->transactions) ? Number::currency(((float) $ynTransaction->transactions->sum('company_share') + $ynTransaction->transactions->sum('rnn_share')) ?? 0) : '-' }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ $ynTransaction->transactions->first()->revenue_share_percentage ?? '-'}}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ filled($ynTransaction->transactions) ? Number::currency((float) $ynTransaction->transactions->sum('rnn_share') ?? 0) : '-' }}
                </td>
            </tr>
            <tr>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">2</td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ __('Cancelled Consumer Payments') }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ $ynTransaction->scheduleTransaction ? $ynTransaction->scheduleTransaction->amount : '-' }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ $ynTransaction->scheduleTransaction ? $ynTransaction->scheduleTransaction->revenue_share_percentage : '-' }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ $ynTransaction->scheduleTransaction ? $ynTransaction->amount : '-' }}
                </td>
            </tr>
            <tr>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">3</td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ __('Secure EcoMail Transaction Fees') }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ $ynTransaction->eletter_count > 0 ? $ynTransaction->eletter_count : '-' }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ $ynTransaction->eletter_count > 0 ? ($ynTransaction->eletter_cost / $ynTransaction->eletter_count) : '-' }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ $ynTransaction->eletter_count > 0 ? $ynTransaction->eletter_cost : '-' }}
                </td>
            </tr>
            <tr>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">4</td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ __('Secure Email Transaction Fees') }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ $ynTransaction->email_count > 0 ? $ynTransaction->email_count : '-' }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ $ynTransaction->email_count > 0 ? __('Included') : '-' }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    -
                </td>
            </tr>
            <tr>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">5</td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ __('Secure Text Transaction Fees') }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ $ynTransaction->sms_count > 0 ? $ynTransaction->sms_count : '-' }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ $ynTransaction->sms_count > 0 ? __('Included') : '-' }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    -
                </td>
            </tr>
        </tbody>
    </table>

    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="text-align: right; font-weight: 600; text-transform: uppercase; padding: 0.75rem;">
                <strong>{{__('Total: ')}}</strong>
                <span>{{ Number::currency((float) $ynTransaction->amount) }}</span>
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px;">
        <p><strong>Billing Inquiries</strong></p>
        <a style="color: #007bff; text-decoration: none;"
           href="mailto:help@younegotiate.com?subject=Billing inquiry for invoice id: {{ 'Y'. $ynTransaction->id }}"
        >
            help@younegotiate.com
        </a>
        <p>{{__('Thank you for doing business with us.')}}</p>
    </div>
</div>
</body>
</html>
