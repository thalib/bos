{{-- 
Template: Receipt PDF
Category: Receipt
Required Fields: receipt, business, customer, items, payment
Description: Simple receipt template for transactions and purchases
--}}

@extends('pdf.layouts.base')

@section('title', 'Receipt #' . $receipt['number'])

@section('header')
    <div class="text-center border-bottom pb-3 mb-3">
        <h2 class="h3 mb-1">{{ $business['name'] }}</h2>
        <div class="text-muted small">
            {{ $business['address'] }}<br>
            {{ $business['city'] }}, {{ $business['state'] }} {{ $business['zip'] }}<br>
            @if(isset($business['phone']))
                Phone: {{ $business['phone'] }}
            @endif
        </div>
    </div>
    
    <div class="text-center mb-3">
        <h1 class="h4 text-primary">RECEIPT</h1>
        <div class="receipt-meta">
            <strong>Receipt #: {{ $receipt['number'] }}</strong><br>
            <span class="text-muted">{{ $receipt['date'] }}</span><br>
            @if(isset($receipt['time']))
                <span class="text-muted">{{ $receipt['time'] }}</span>
            @endif
        </div>
    </div>
@endsection

@section('content')
    {{-- Customer Information --}}
    @if(isset($customer))
        <div class="customer-section mb-3 p-2 bg-light rounded">
            <div class="row">
                <div class="col-6">
                    <strong>Customer:</strong><br>
                    {{ $customer['name'] }}<br>
                    @if(isset($customer['email']))
                        {{ $customer['email'] }}
                    @endif
                </div>
                <div class="col-6">
                    @if(isset($customer['phone']))
                        <strong>Phone:</strong><br>
                        {{ $customer['phone'] }}
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Transaction Items --}}
    <div class="items-section mb-4">
        <table class="table table-sm">
            <thead>
                <tr class="border-bottom">
                    <th>Item</th>
                    <th class="text-center" width="10%">Qty</th>
                    <th class="text-end" width="20%">Price</th>
                    <th class="text-end" width="20%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>
                            {{ $item['description'] ?? $item['name'] ?? 'Item' }}
                            @if(isset($item['details']))
                                <br><small class="text-muted">{{ $item['details'] }}</small>
                            @endif
                            @if(isset($item['sku']))
                                <br><small class="text-muted">SKU: {{ $item['sku'] }}</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $item['quantity'] }}</td>
                        <td class="text-end">${{ number_format($item['price'], 2) }}</td>
                        <td class="text-end">${{ number_format($item['amount'] ?? $item['total'] ?? ($item['price'] * $item['quantity']), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pricing Breakdown --}}
    <div class="totals-section">
        <div class="border-top pt-2">
            <table class="table table-sm table-borderless">
                <tr>
                    <td class="text-end"><strong>Subtotal:</strong></td>
                    <td class="text-end" width="25%">${{ number_format($totals['subtotal'] ?? $payment['subtotal'] ?? 0, 2) }}</td>
                </tr>
                @if(isset($totals['discount']) && $totals['discount'] > 0)
                    <tr>
                        <td class="text-end">Discount:</td>
                        <td class="text-end">-${{ number_format($totals['discount'] ?? $payment['discount'], 2) }}</td>
                    </tr>
                @endif
                @if(isset($totals['tax_amount']) && $totals['tax_amount'] > 0)
                    <tr>
                        <td class="text-end">Tax:</td>
                        <td class="text-end">${{ number_format($totals['tax_amount'] ?? $payment['tax'], 2) }}</td>
                    </tr>
                @endif
                @if(isset($payment['tip']) && $payment['tip'] > 0)
                    <tr>
                        <td class="text-end">Tip:</td>
                        <td class="text-end">${{ number_format($payment['tip'], 2) }}</td>
                    </tr>
                @endif
                <tr class="border-top">
                    <td class="text-end"><strong>Total:</strong></td>
                    <td class="text-end"><strong>${{ number_format($totals['total'] ?? $payment['total'] ?? 0, 2) }}</strong></td>
                </tr>
                @if(isset($totals['amount_paid']) && $totals['amount_paid'] > 0)
                    <tr>
                        <td class="text-end">Amount Paid:</td>
                        <td class="text-end">${{ number_format($totals['amount_paid'], 2) }}</td>
                    </tr>
                    @if(isset($totals['amount_due']) && $totals['amount_due'] > 0)
                        <tr>
                            <td class="text-end"><strong>Balance Due:</strong></td>
                            <td class="text-end"><strong>${{ number_format($totals['amount_due'], 2) }}</strong></td>
                        </tr>
                    @endif
                @endif
            </table>
        </div>
    </div>

    {{-- Payment Information --}}
    <div class="payment-section mt-4 p-2 bg-light rounded">
        <div class="row">
            <div class="col-6">
                <strong>Payment Method:</strong><br>
                {{ $payment['method'] ?? 'Payment' }}
                @if(isset($payment['card_last4']))
                    <br>Card ending in {{ $payment['card_last4'] }}
                @elseif(isset($payment['card_last_four']))
                    <br>Card ending in {{ $payment['card_last_four'] }}
                @endif
            </div>
            <div class="col-6">
                @if(isset($payment['transaction_id']))
                    <strong>Transaction ID:</strong><br>
                    {{ $payment['transaction_id'] }}
                @endif
                @if(isset($payment['authorization']))
                    <br><strong>Auth:</strong> {{ $payment['authorization'] }}
                @endif
                @if(isset($payment['status']))
                    <br><strong>Status:</strong> {{ $payment['status'] }}
                @endif
            </div>
        </div>
    </div>

    {{-- Additional Information --}}
    @if(isset($receipt['notes']) || isset($receipt['return_policy']))
        <div class="additional-info mt-4">
            @if(isset($notes) || isset($receipt['notes']))
                <div class="mb-2">
                    <strong>Notes:</strong><br>
                    <small>{{ $notes ?? $receipt['notes'] }}</small>
                </div>
            @endif
            
            @if(isset($terms) || isset($receipt['return_policy']))
                <div class="return-policy small text-muted">
                    <strong>{{ isset($terms) ? 'Terms & Conditions:' : 'Return Policy:' }}</strong><br>
                    {{ $terms ?? $receipt['return_policy'] }}
                </div>
            @endif
        </div>
    @endif
@endsection

@section('footer')
    <div class="text-center small text-muted border-top pt-2">
        <div>Thank you for your business!</div>
        @if(isset($business['website']))
            <div>Visit us at {{ $business['website'] }}</div>
        @endif
        @if(isset($receipt['survey_url']))
            <div>Share your feedback: {{ $receipt['survey_url'] }}</div>
        @endif
    </div>
@endsection
