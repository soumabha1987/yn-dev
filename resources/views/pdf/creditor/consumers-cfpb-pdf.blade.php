@use('Carbon\Carbon')

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{__('CFPB Letter')}}</title>
    <style>
        .font-poppins {
            font-family: Poppins, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
        }
        body {
            margin: 0;
            padding: 0;
            color: #333;
            background: #fff;
        }
        p {
            margin: 0;
            font-size: 12px;
        }
        a {
            font-size: 12px;
        }
        h2 {
            font-size: 17px;
            border-bottom: 3px solid #333;
            margin-bottom: 8px;
            margin-top: 0;
        }
        td {
            font-size: 12px;
            padding-bottom: 3px;
        }
        .link-color {
            color: #000;
        }
        .weight-bolder {
            font-weight: bold;
        }
        .common-width {
            width: 100%;
            position: relative;
        }
        .left-common-part, .right-common-part {
            display: inline-block;
            vertical-align: top;
            width: 48%;
        }

        .right-common-part {
            text-align: start;
            position: absolute;
            right: 0;
        }
        .notice {
            padding: 22px 0;
        }
        .left-section {
            display: inline-block;
            width: 60%;
            vertical-align: top;
        }
        .right-section {
            display: inline-block;
            width: 35%;
            vertical-align: top;
            text-align: start;
        }

        .bottom-container {
            position: relative;
            text-align: center;
            white-space: nowrap;
        }

        .bottom-left-common-part,
        .bottom-middle-last-part,
        .bottom-right-common-part {
            display: inline-block;
            vertical-align: top;
            text-align: left;
            white-space: normal;
        }

        .bottom-left-common-part {
            position: absolute;
            left: 0;
            text-align: start;
            margin-top: 20px;
        }

        .bottom-right-common-part {
            position: absolute;
            right: 0;
            text-align: end;
        }

        .bottom-middle-last-part {
            position: absolute;
            left: 200px;
        }

        .consumer-section {
            page-break-after: always;
        }
        .consumer-section:last-child {
            page-break-after: auto;
        }
    </style>
</head>
<body class="font-poppins">
    @foreach($consumers as $consumer)
        <div class="consumer-section">
            <div style="height: 297px">
                <div class="common-width">
                    <div class="left-section">
                        <p>
                            {{ str($consumer->company->company_name)->title() }}<br>
                            {{ str($consumer->company->address ?? 'N/A')->title() }}<br>
                            {{ $consumer->company->city .', '. $consumer->company->state .' '. $consumer->company->zip}}<br>
                            {{ preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '($1) $2-$3', $consumer->company->owner_phone) }} from {{ $consumer->company->from_time?->formatWithTimezone($consumer->company->timezone->value, 'h:i A') }} to {{ $consumer->company->to_time?->formatWithTimezone($consumer->company->timezone->value, 'h:i A') }} {{ $consumer->company->timezone }}, {{ Carbon::getDays()[$consumer->company->from_day] ?? '' }} to {{ Carbon::getDays()[$consumer->company->to_day] ?? '' }}<br>
                        </p>
                        <p>
                            <a href="{{ $consumer->company->url }}" class="link-color">
                                {{ $consumer->company->url }}
                            </a>
                        </p>
                    </div>
                    <div class="right-section">
                        <span>
                            <p style="float: left;">
                                To:
                            </p>
                            <p style="padding-left: 30px; padding-bottom: 4px;">
                                {{ str($consumer->first_name . ' ' . $consumer->last_name) }}<br>
                                {{ $consumer->address1 }}<br>
                                @if($consumer->address2)
                                    {{ $consumer->address2 }}<br>
                                @endif
                                {{ $consumer->city. ',' .$consumer->state. ' ' .$consumer->zip }}<br>
                            </p>
                            <p class="weight-bolder">
                                Reference: {{ $consumer->reference_number ?? $consumer->member_account_number }}
                            </p>
                        </span>
                    </div>
                    <div class="notice">
                        <p style="font-size: 15px;">
                            <span class="weight-bolder">
                                {{ str($consumer->company->company_name)->title() }} is a debt collector.
                            </span>
                            We are trying to collect a debt that you owe to {{ $consumer->original_account_name }}. We will use any information you give us to help collect the debt.
                        </p>
                    </div>
                </div>
                <div class="common-width">
                    <div class="left-common-part">
                        <h2>Our information shows:</h2>
                        <p style="padding-bottom: 12px;">
                            You had an account with {{ str($consumer->original_account_name)->lower() }} account number {{ $consumer->reference_number ?? $consumer->account_number }}.
                        </p>
                        <table style="border-top: 2px solid #333; border-bottom: 2px solid #333; width:100%;">
                            <tr>
                                <td style="border-bottom: 2px  solid #333;">
                                    As of {{ $consumer->created_at->formatWithTimezone() }}, you owed:
                                </td>
                                <td style="border-bottom: 2px  solid #333;">
                                    {{ Number::currency((float) $consumer->total_balance) }}
                                </td>
                            </tr>
                            <tr>
                                <td style="border-bottom: 2px  solid #333;">
                                    Between {{ $consumer->created_at->formatWithTimezone() }} and today:
                                </td>
                                <td style="border-bottom: 2px  solid #333;"></td>
                            </tr>
                            <tr>
                                <td style="border-bottom: 2px  solid #333;">
                                    You were charged this amount in interest:
                                </td>
                                <td style="border-bottom: 2px  solid #333;">+ $0.00</td>
                            </tr>
                            <tr>
                                <td style="border-bottom: 2px  solid #333;">
                                    You were charged this amount in fees:
                                </td>
                                <td style="border-bottom: 2px  solid #333;">+ $0.00</td>
                            </tr>
                            <tr>
                                <td style="border-bottom: 2px  solid #333;">
                                    You paid or were credited this amount toward the debt:
                                </td>
                                <td style="border-bottom: 2px  solid #333;">- $0.00</td>
                            </tr>
                            <tr style="background: #f9f9f9;">
                                <td class="weight-bolder">
                                    Total amount of the debt now:
                                </td>
                                <td class="weight-bolder">{{ Number::currency((float) $consumer->total_balance) }}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="right-common-part">
                        <div class="instructions">
                            <h2>How can you dispute the debt?</h2>
                            <ul style="padding-left: 18px; margin: 0;">
                                <li>
                                    <p>
                                        <span class="weight-bolder">
                                            Call or write to us by
                                            {{ $consumer->expiry_date ? $consumer->expiry_date->formatWithTimezone() : $consumer->created_at->addDays(30)->formatWithTimezone() }},
                                            to dispute all or part of the debt.
                                        </span>
                                        If you do not, we will assume that our information is correct.
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <span class="weight-bolder">
                                            If you write to us by
                                            {{ $consumer->expiry_date ? $consumer->expiry_date->formatWithTimezone() : $consumer->created_at->addDays(30)->formatWithTimezone() }}
                                        </span>,
                                        we must stop collection on any amount you dispute until we send you information that shows you owe the debt. You may use the form below or write to us without the form. We accept disputes electronically at
                                        <a href="{{ $consumer->company->url }}" class="link-color">{{ $consumer->company->url }}</a>
                                    </p>
                                </li>
                            </ul>
                        </div>

                        <div class="additional-info" style="padding-top: 18px;">
                            <h2>What else can you do?</h2>
                            <ul style="padding-left: 18px; margin: 0;">
                                <li>
                                    <p>
                                        <span class="weight-bolder">
                                            Write to ask for the name and address of the original creditor,
                                        </span>
                                        If you write by {{ $consumer->created_at->addDays(30)->formatWithTimezone() }}, we must stop collection until we send you that information. You may use the form below or write to us without the form. we accept such requests electronically at
                                        <a href="{{ $consumer->company->url }}" class="link-color">{{ $consumer->company->url }}</a>.
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <span class="weight-bolder">
                                            Go to
                                            <a href="www.cfpb.gov/debt-collection" class="link-color">www.cfpb.gov/debt-collection</a>
                                            to learn more about your rights under federal law.
                                        </span> For instance, you have the right to stop or limit how we contact you.
                                    </p>
                                </li>
                                <li>
                                    <p>Contact us about your payment options.</p>
                                </li>
                                <li>
                                    <p>Póngase en contacto con nosotros para solicitar una copia de este formulario en español.</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div style="border-top:1px dashed #333; margin-bottom:20px; margin-top:200px; position: relative;">
                    <img src="images/scissor.png" alt="#icon" style="position: absolute; top: -15px; left: 0; transform: translateX(20%); width: 30px;" />
                </div>
                <div class="bottom-container" style="padding-top: 15px;">
                    <div class="bottom-left-common-part" style="width: 30%; padding-left: 15px; padding-top: 10px;">
                        <div>
                            <p><span class="weight-bolder">{{ __('Mail this form to:') }}</span><br>
                                {{ str($consumer->company->company_name)->title() }}<br>
                                {{ str($consumer->company->address ?? 'N/A')->title() }}<br>
                                {{ $consumer->company->city .', '. $consumer->company->state .' '. $consumer->company->zip}}
                            </p>
                            <p style="padding-top: 50px;"> {{ str($consumer->first_name. ' ' .$consumer->last_name) }} <br>
                                {{ $consumer->address1 }}<br>
                                @if($consumer->address2)
                                    {{ $consumer->address2 }}<br>
                                @endif
                                {{ $consumer->city. ',' .$consumer->state. ' ' .$consumer->zip }}
                            </p>
                        </div>
                    </div>
                    <div class="bottom-middle-last-part">
                        @if($withQrCode)
                            <h2>Respond Electronically</h2>
                            <div style="justify-content: center">
                                <div style="position: absolute; left: 50px; top: 80px">
                                    <a href="{{ route('consumer.login') }}">
                                        <img src="http://api.qrserver.com/v1/create-qr-code/?data={{ route('consumer.login') }}" style="width: 70px; height:70px;">
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="bottom-right-common-part" style="width: 40%;">
                        <h2>Respond by Mail</h2>
                        <p><strong>Check all that apply:</strong></p>
                        <ul style="list-style-type: none; padding-left: 18px; margin: 0; font-size: 12px;">
                            <li>
                                <span style="display: inline-block; vertical-align: middle;">
                                    <input type="checkbox" style="vertical-align: middle;">I want to dispute the debt because I think:
                                </span>
                            </li>
                            <ul style="list-style-type: none; padding-left: 18px;">
                                <li>
                                    <span style="display: inline-block; vertical-align: middle;">
                                        <input type="checkbox" style="vertical-align: middle;">This is not my debt.
                                    </span>
                                </li>
                                <li>
                                    <span style="display: inline-block; vertical-align: middle;">
                                        <input type="checkbox" style="vertical-align: middle;">The amount is wrong.
                                    </span>
                                </li>
                                <li>
                                    <span style="display: inline-block; vertical-align: middle;">
                                        <input type="checkbox" style="vertical-align: middle;">Other (please describe on reverse or attach additional information).
                                    </span>
                                </li>
                            </ul>
                            <li>
                                <span style="display: inline-block; vertical-align: middle;">
                                    <input type="checkbox" style="vertical-align: middle;"> I want you to send me the name and address of the original creditor.
                                </span>
                            </li>
                            <li>
                                <div>
                                    <span style="display: inline-block; vertical-align: middle;">
                                        <input type="checkbox" style="vertical-align: middle;">I enclose this amount: $
                                    </span>
                                    <span style="display: inline-block; vertical-align: middle; border: 2px solid #333; height:8px; padding: 2px; width: 80px"></span>
                                </div>
                            </li>
                        </ul>
                        <p style="margin-bottom: 3px">
                            Make your check payable to {{ $consumer->company->company_name }} include the reference number {{ $consumer->reference_number ?? $consumer->member_account_number }}
                        </p>
                        <p>
                            <span style="display: inline-block; vertical-align: middle;">
                                <input type="checkbox"> Quiero este formulario en español.
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>
