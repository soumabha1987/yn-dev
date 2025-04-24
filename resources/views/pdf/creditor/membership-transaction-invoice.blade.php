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
                <strong>{{__('Invoice No.: ')}} </strong>{{'M'.$membershipTransaction->id}}<br>
                <strong>{{__('Date:')}}</strong> {{ $membershipTransaction->created_at->formatWithTimezone() }}
            </td>
        </tr>
    </table>

    <table style="border-bottom: 2px solid #ddd; width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="padding: 10px;">
                <strong>{{__('Bill To: ')}}</strong> {{ str($membershipTransaction->company->company_name)->title() }}
                <br>
            </td>
            <td style="text-align: right; padding: 10px;">
                <strong>{{ __('Plan Name: ') }}</strong>
                {{ str($membershipTransaction->membership->name)->title(). ' ( '. $membershipTransaction->membership->frequency->displayName(). ' )'}} <br>
                <strong> {{ __('Plan Billing Period: ') }}</strong>
                {{ $membershipTransaction->created_at->formatWithTimezone() . __(' To ') .  $membershipTransaction->plan_end_date->formatWithTimezone() }} <br>
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
                    {{ __('Membership Licensing') }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ Number::currency((float) $membershipTransaction->price) }}
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    1
                </td>
                <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                    {{ Number::currency((float) $membershipTransaction->price) }}
                </td>
            </tr>
        </tbody>
    </table>

    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="font-weight: 600; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 0.75rem;">
                <strong>{{__('Total: ')}}</strong>
                {{ Number::currency((float) $membershipTransaction->price) }}
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px;">
        <p><strong>Billing Inquiries</strong></p>
        <a style="color: #007bff; text-decoration: none;"
           href="mailto:help@younegotiate.com?subject=Billing inquiry for invoice id: {{ 'M'. $membershipTransaction->id }}"
        >
            help@younegotiate.com
        </a>
        <p>{{__('Thank you for doing business with us.')}}</p>
    </div>
</div>
</body>
</html>
