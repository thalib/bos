{{-- 
Template: Invoice PDF
Category: Invoice
Required Fields: company, invoice, customer, items, totals
Description: Professional invoice template with company branding and itemized billing
--}}

@extends('pdf.layouts.base')

@section('title', 'Invoice #' . $invoice['number'])

@section('header')
    <div class="row">
        <div class="col-6">
            <h1 class="h2 mb-1">{{ $company['name'] }}</h1>
            <div class="text-muted small">
                {{ $company['address'] }}<br>
                {{ $company['city'] }}, {{ $company['state'] }} {{ $company['zip'] }}<br>
                @if(isset($company['phone']))
                    Phone: {{ $company['phone'] }}<br>
                @endif
                @if(isset($company['email']))
                    Email: {{ $company['email'] }}
                @endif
            </div>
        </div>
        <div class="col-6 text-end">
            <h2 class="h1 text-primary mb-2">INVOICE</h2>
            <div class="invoice-meta">
                <strong>Invoice #: {{ $invoice['number'] }}</strong><br>
                <span class="text-muted">Date: {{ $invoice['date'] }}</span><br>
                @if(isset($invoice['due_date']))
                    <span class="text-muted">Due: {{ $invoice['due_date'] }}</span>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('content')
    {{-- Customer Information --}}
    <div class="row mb-4">
        <div class="col-6">
            <h4 class="text-primary border-bottom pb-1">Bill To:</h4>
            <div class="customer-info">
                <strong>{{ $customer['name'] }}</strong><br>
                @if(isset($customer['company']))
                    {{ $customer['company'] }}<br>
                @endif
                {{ $customer['address'] }}<br>
                {{ $customer['city'] }}, {{ $customer['state'] }} {{ $customer['zip'] }}<br>
                @if(isset($customer['email']))
                    {{ $customer['email'] }}
                @endif
            </div>
        </div>
        <div class="col-6">
            @if(isset($invoice['po_number']) || isset($invoice['terms']))
                <div class="invoice-details">
                    @if(isset($invoice['po_number']))
                        <div class="mb-1">
                            <span class="text-muted">PO Number:</span>
                            <strong>{{ $invoice['po_number'] }}</strong>
                        </div>
                    @endif
                    @if(isset($invoice['terms']))
                        <div class="mb-1">
                            <span class="text-muted">Terms:</span>
                            <strong>{{ $invoice['terms'] }}</strong>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Invoice Items --}}
    <div class="invoice-items mb-4">
        <table class="table table-bordered">
            <thead class="table-primary">
                <tr>
                    <th>Description</th>
                    <th class="text-center" width="10%">Qty</th>
                    <th class="text-end" width="15%">Rate</th>
                    <th class="text-end" width="15%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item['description'] }}</strong>
                            @if(isset($item['details']))
                                <br><small class="text-muted">{{ $item['details'] }}</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $item['quantity'] }}</td>
                        <td class="text-end">${{ number_format($item['rate'], 2) }}</td>
                        <td class="text-end">${{ number_format($item['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Totals Section --}}
    <div class="row">
        <div class="col-6">
            @if(isset($invoice['notes']))
                <div class="notes">
                    <h5 class="text-primary">Notes:</h5>
                    <p class="small">{{ $invoice['notes'] }}</p>
                </div>
            @endif
        </div>
        <div class="col-6">
            <div class="totals-table">
                <table class="table table-sm">
                    <tr>
                        <td class="text-end"><strong>Subtotal:</strong></td>
                        <td class="text-end" width="25%">${{ number_format($totals['subtotal'], 2) }}</td>
                    </tr>
                    @if(isset($totals['discount']) && $totals['discount'] > 0)
                        <tr>
                            <td class="text-end">Discount:</td>
                            <td class="text-end">-${{ number_format($totals['discount'], 2) }}</td>
                        </tr>
                    @endif
                    @if(isset($totals['tax']) && $totals['tax'] > 0)
                        <tr>
                            <td class="text-end">
                                Tax @if(isset($totals['tax_rate']))({{ $totals['tax_rate'] }}%)@endif:
                            </td>
                            <td class="text-end">${{ number_format($totals['tax'], 2) }}</td>
                        </tr>
                    @endif
                    <tr class="table-primary">
                        <td class="text-end"><strong>Total:</strong></td>
                        <td class="text-end"><strong>${{ number_format($totals['total'], 2) }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('footer')
    <div class="row small text-muted">
        <div class="col-6">
            @if(isset($company['website']))
                Website: {{ $company['website'] }}
            @endif
        </div>
        <div class="col-6 text-end">
            @if(isset($invoice['payment_instructions']))
                {{ $invoice['payment_instructions'] }}
            @endif
        </div>
    </div>
@endsection
