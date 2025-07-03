{{-- 
@template
title: Base PDF Layout
description: Shared layout for all PDF templates with A4 optimization
author: System
version: 1.0
tags: base, layout, pdf
paper_size: a4
orientation: portrait
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Document')</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: white;
        }

        /* A4 page dimensions: 595px × 842px at 72 DPI */
        .page {
            width: 595px;
            min-height: 842px;
            margin: 0 auto;
            padding: 10px;
            background: white;
            box-sizing: border-box;
        }

        /* Page break handling */
        .page-break {
            page-break-after: always;
        }

        .page-break-before {
            page-break-before: always;
        }

        .no-break {
            page-break-inside: avoid;
        }

        /* Header styles */
        .document-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e5e5;
        }

        .document-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .document-subtitle {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 15px;
        }

        .document-meta {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
        }

        .meta-group {
            flex: 1;
            min-width: 200px;
        }

        .meta-label {
            font-weight: bold;
            font-size: 11px;
            color: #34495e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .meta-value {
            font-size: 13px;
            color: #2c3e50;
        }

        /* Content area */
        .document-content {
            margin: 30px 0;
        }

        /* Footer styles */
        .document-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            font-size: 10px;
            color: #7f8c8d;
            text-align: center;
        }

        /* Table styles */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .table th,
        .table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 11px;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-borderless td,
        .table-borderless th {
            border: none;
        }

        .table-sm th,
        .table-sm td {
            padding: 4px 8px;
            font-size: 11px;
        }

        /* Text alignment utilities */
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Color utilities */
        .text-primary { color: #007bff; }
        .text-secondary { color: #6c757d; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .text-warning { color: #ffc107; }
        .text-info { color: #17a2b8; }
        .text-muted { color: #6c757d; }

        /* Background utilities */
        .bg-light { background-color: #f8f9fa; }
        .bg-primary { background-color: #007bff; color: white; }
        .bg-secondary { background-color: #6c757d; color: white; }

        /* Spacing utilities */
        .mb-1 { margin-bottom: 8px; }
        .mb-2 { margin-bottom: 16px; }
        .mb-3 { margin-bottom: 24px; }
        .mb-4 { margin-bottom: 32px; }

        .mt-1 { margin-top: 8px; }
        .mt-2 { margin-top: 16px; }
        .mt-3 { margin-top: 24px; }
        .mt-4 { margin-top: 32px; }

        .p-1 { padding: 8px; }
        .p-2 { padding: 16px; }
        .p-3 { padding: 24px; }

        /* Font utilities */
        .fw-bold { font-weight: bold; }
        .fw-normal { font-weight: normal; }
        .small { font-size: 11px; }

        /* Layout utilities */
        .d-flex {
            display: flex;
        }

        .flex-column {
            flex-direction: column;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .align-items-center {
            align-items: center;
        }

        .w-100 { width: 100%; }

        /* Grid system */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }

        .col {
            flex: 1;
            padding: 0 10px;
        }

        .col-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 10px;
        }

        .col-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
            padding: 0 10px;
        }

        .col-8 {
            flex: 0 0 66.666667%;
            max-width: 66.666667%;
            padding: 0 10px;
        }

        /* Print optimizations */
        @media print {
            body {
                font-size: 12px;
                line-height: 1.3;
            }

            .page {
                width: 100%;
                height: 100vh;
                margin: 0;
                padding: 20px;
                box-shadow: none;
            }

            .no-print {
                display: none !important;
            }

            /* Ensure content fits on page */
            h1, h2, h3, h4, h5, h6 {
                page-break-after: avoid;
            }

            .table {
                page-break-inside: auto;
            }

            .table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .table thead {
                display: table-header-group;
            }

            .table tfoot {
                display: table-footer-group;
            }
        }

        @page {
            margin: 0;
            size: A4;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="page">
        <!-- Document Header -->
        <header class="document-header">
            @yield('header')
        </header>

        <!-- Document Content -->
        <main class="document-content">
            @yield('content')
        </main>

        <!-- Document Footer -->
        <footer class="document-footer">
            @yield('footer')
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
