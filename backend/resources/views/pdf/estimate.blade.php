@extends('pdf.layouts.base')

@section('title', 'Estimate')

@section('content')
<div class="estimate-container">
    <!-- Header Section -->
    <div class="header">
        <div class="company-details">
            <h1>{{ $data['business']['name'] ?? 'Company Name' }}</h1>
            <p>{!! nl2br(e($data['business']['address'] ?? 'Company Address')) !!}</p>
            @if(isset($data['business']['contact']))
            <p>Contact: {{ $data['business']['contact'] }}</p>
            @endif
            @if(isset($data['business']['gstin']))
            <p>GSTIN: {{ $data['business']['gstin'] }}</p>
            @endif
        </div>
        
        <div class="document-info">
            <h2>{{ $data['documentType'] ?? 'ESTIMATE' }}</h2>
            <table class="info-table">
                <tr>
                    <td><strong>Number:</strong></td>
                    <td>{{ $data['estimateNumber'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td>{{ isset($data['date']) ? date('d M Y', strtotime($data['date'])) : date('d M Y') }}</td>
                </tr>
                @if(isset($data['validUntil']))
                <tr>
                    <td><strong>Valid Until:</strong></td>
                    <td>{{ date('d M Y', strtotime($data['validUntil'])) }}</td>
                </tr>
                @elseif(isset($data['validityDays']))
                <tr>
                    <td><strong>Validity:</strong></td>
                    <td>{{ $data['validityDays'] }} days</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Customer Section -->
    <div class="customer-section">
        <div class="customer-details">
            <h3>Bill To:</h3>
            <div class="address-box">
                {!! nl2br(e($data['customer']['billTo'] ?? '')) !!}
            </div>
        </div>
        <div class="customer-notes">
            <h3>Notes:</h3>
            <div class="notes-box">
                {!! nl2br(e($data['customer']['notes'] ?? '')) !!}
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Item Description</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Unit Price</th>
                    <th>Tax</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($data['items']) && is_array($data['items']))
                    @foreach($data['items'] as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item['product']['name'] ?? '' }}</strong>
                            @if(isset($item['product']['description']))
                            <p class="item-description">{{ $item['product']['description'] }}</p>
                            @endif
                        </td>
                        <td>{{ $item['quantity'] ?? '' }}</td>
                        <td>{{ $item['unit'] ?? $item['product']['unit'] ?? '' }}</td>
                        <td>{{ number_format($item['price'], 2) }}</td>
                        <td>{{ isset($item['tax']) ? number_format($item['tax'], 2) . ' (' . ($item['taxPercentage'] ?? $item['product']['taxPercentage'] ?? 0) . '%)' : '-' }}</td>
                        <td>{{ number_format($item['total'], 2) }}</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <!-- Totals Section -->
    <div class="totals-section">
        <table class="totals-table">
            @if(isset($data['switches']['showSubtotal']) && $data['switches']['showSubtotal'])
            <tr>
                <td>Subtotal:</td>
                <td>{{ number_format($data['subtotal'], 2) }}</td>
            </tr>
            @endif
            
            @if(isset($data['switches']['showGst']) && $data['switches']['showGst'])
            <tr>
                <td>GST:</td>
                <td>{{ number_format($data['totalTax'], 2) }}</td>
            </tr>
            @endif
            
            @if(isset($data['switches']['showTotal']) && $data['switches']['showTotal'])
            <tr class="grand-total">
                <td>Grand Total:</td>
                <td>{{ number_format($data['grandTotal'], 2) }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Terms and Conditions -->
    @if(isset($data['termsAndConditions']))
    <div class="terms-section">
        <h3>Terms and Conditions</h3>
        <div class="terms-content">
            {!! nl2br(e($data['termsAndConditions'])) !!}
        </div>
    </div>
    @endif

    <!-- Bank Details -->
    @if(isset($data['bankDetails']) && $data['bankDetails'])
    <div class="bank-section">
        <h3>Bank Details</h3>
        <div class="bank-content">
            {!! nl2br(e($data['bankDetails'])) !!}
        </div>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div class="signature-section">
            <div class="customer-signature">
                <p>Customer Signature</p>
                <div class="signature-line"></div>
            </div>
            
            <div class="company-signature">
                <p>For {{ $data['business']['name'] ?? 'Company Name' }}</p>
                <div class="signature-line"></div>
                <p>Authorized Signatory</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .estimate-container {
        padding: 20px;
        font-family: 'Helvetica', 'Arial', sans-serif;
    }
    
    .header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
    }
    
    .company-details {
        flex: 2;
    }
    
    .company-details h1 {
        font-size: 24px;
        margin-bottom: 5px;
    }
    
    .document-info {
        flex: 1;
        text-align: right;
    }
    
    .document-info h2 {
        font-size: 20px;
        margin-bottom: 10px;
        color: #444;
    }
    
    .info-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .info-table td {
        padding: 3px;
    }
    
    .customer-section {
        display: flex;
        margin-bottom: 20px;
        gap: 20px;
    }
    
    .customer-details, .customer-notes {
        flex: 1;
    }
    
    .address-box, .notes-box {
        border: 1px solid #ddd;
        padding: 10px;
        min-height: 80px;
        background-color: #fafafa;
    }
    
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    
    .items-table th, .items-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    
    .items-table th {
        background-color: #f2f2f2;
        font-weight: bold;
    }
    
    .item-description {
        font-size: 12px;
        color: #666;
        margin: 2px 0 0 0;
    }
    
    .totals-section {
        margin-bottom: 30px;
    }
    
    .totals-table {
        width: 300px;
        margin-left: auto;
        border-collapse: collapse;
    }
    
    .totals-table td {
        padding: 5px;
        border-bottom: 1px solid #eee;
    }
    
    .totals-table td:first-child {
        text-align: left;
        font-weight: bold;
    }
    
    .totals-table td:last-child {
        text-align: right;
    }
    
    .grand-total {
        font-weight: bold;
        font-size: 16px;
    }
    
    .terms-section, .bank-section {
        margin-bottom: 20px;
    }
    
    .terms-content, .bank-content {
        border: 1px solid #eee;
        padding: 10px;
        background-color: #fafafa;
        font-size: 12px;
    }
    
    .footer {
        margin-top: 40px;
    }
    
    .signature-section {
        display: flex;
        justify-content: space-between;
    }
    
    .customer-signature, .company-signature {
        text-align: center;
        width: 200px;
    }
    
    .signature-line {
        border-top: 1px solid #000;
        margin: 40px 0 5px 0;
    }
    
    @page {
        margin: 40px;
    }
</style>
@endsection
