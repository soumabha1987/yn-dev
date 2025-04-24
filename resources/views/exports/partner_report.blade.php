<table>
    <tbody>
        <tr>
            <th colspan="4">{{ __('YouNegotiate Monthly Partner Commission Report') }}</th>
        </tr>
        <tr>
            <th colspan="2">{{ __('Sent On Date') }}</th>
            <td colspan="2">{{ now()->format('M d, Y') }}</td>
        </tr>
        <tr>
            <th colspan="2">{{ __('Calendar Month Report Period') }}</th>
            <td>{{ __('Month: ') . now()->format('M') }}</td>
            <td>{{ __('Year: ') . now()->format('Y') }}</td>
        </tr>
        <tr>
            <th colspan="2">{{ __('YouNegotiate Partner Name') }}</th>
            <td colspan="2">{{ $partner->name }}</td>
        </tr>
        <tr>
            <th colspan="2">{{ __('Partner Revenue Share') }}</th>
            <td colspan="2">{{ $partner->revenue_share }}%</td>
        </tr>
        <tr></tr>
        <tr>
            <th>{{ __('Member Name') }}</th>
            <th>{{ __('Date Joined') }}</th>
            <th>{{ __('YN Monthly Revenue') }}</th>
            <th>{{ __('Partnership Monthly Rev Share') }}</th>
        </tr>
        @foreach($partner->companies as $company)
            <tr>
                <td>{{ $company->company_name }}</td>
                <td>{{ $company->created_at->formatWithTimezone() }}</td>
                <td>{{ Number::currency((float) ($company->total_yn_transactions_amount + $company->total_membership_transactions_amount)) }}</td>
                <td>{{ Number::currency((float) ($company->total_yn_transaction_partner_revenue + $company->total_membership_transactions_partner_revenue)) }}</td>
            </tr>
        @endforeach
        <tr></tr>
        <tr>
            <td></td>
            <th>{{ __('Total:') }}</th>
            <td>{{ Number::currency((float) $totalAmount) }}</td>
            <td>{{ Number::currency((float) $partnerTotalAmount) }}</td>
        </tr>
    </tbody>
</table>

