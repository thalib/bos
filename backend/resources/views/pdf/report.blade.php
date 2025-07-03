{{-- 
Template: Business Report PDF
Category: Report
Required Fields: report, data, period
Description: Professional business report with charts, statistics, and analysis
--}}

@extends('pdf.layouts.base')

@section('title', $report['title'])

@section('header')
    <div class="row">
        <div class="col-8">
            <h1 class="h2 mb-1">{{ $report['title'] }}</h1>
            <p class="text-muted mb-0">{{ $report['subtitle'] ?? 'Business Report' }}</p>
        </div>
        <div class="col-4 text-end">
            <div class="report-meta">
                <div class="text-muted small">
                    <strong>Period:</strong> {{ $period['start'] }} - {{ $period['end'] }}<br>
                    <strong>Generated:</strong> {{ $report['generated_at'] ?? now()->format('M d, Y') }}<br>
                    @if(isset($report['version']))
                        <strong>Version:</strong> {{ $report['version'] }}
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    {{-- Executive Summary --}}
    @if(isset($report['summary']))
        <div class="section mb-4">
            <h3 class="text-primary border-bottom pb-2">Executive Summary</h3>
            <p>{{ $report['summary'] }}</p>
        </div>
    @endif

    {{-- Key Metrics --}}
    @if(isset($data['metrics']))
        <div class="section mb-4">
            <h3 class="text-primary border-bottom pb-2">Key Metrics</h3>
            <div class="row">
                @foreach($data['metrics'] as $metric)
                    <div class="col-3 text-center mb-3">
                        <div class="metric-card p-3 border rounded">
                            <div class="metric-value h4 text-primary mb-1">{{ $metric['value'] }}</div>
                            <div class="metric-label small text-muted">{{ $metric['label'] }}</div>
                            @if(isset($metric['change']))
                                <div class="metric-change small {{ $metric['change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $metric['change'] >= 0 ? '+' : '' }}{{ $metric['change'] }}%
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Data Tables --}}
    @if(isset($data['tables']))
        @foreach($data['tables'] as $table)
            <div class="section mb-4">
                <h4 class="text-primary">{{ $table['title'] }}</h4>
                @if(isset($table['description']))
                    <p class="small text-muted">{{ $table['description'] }}</p>
                @endif
                
                <table class="table table-striped table-bordered">
                    <thead class="table-primary">
                        <tr>
                            @foreach($table['headers'] as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($table['rows'] as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                    @if(isset($table['totals']))
                        <tfoot class="table-secondary">
                            <tr>
                                @foreach($table['totals'] as $total)
                                    <td><strong>{{ $total }}</strong></td>
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endforeach
    @endif

    {{-- Charts Section --}}
    @if(isset($data['charts']))
        <div class="section mb-4">
            <h3 class="text-primary border-bottom pb-2">Charts & Visualizations</h3>
            @foreach($data['charts'] as $chart)
                <div class="chart-placeholder p-4 mb-3 border rounded text-center">
                    <h5>{{ $chart['title'] }}</h5>
                    <div class="text-muted">
                        {{ $chart['type'] ?? 'Chart' }} - {{ $chart['description'] ?? 'Data visualization' }}
                    </div>
                    @if(isset($chart['data_points']))
                        <div class="mt-2 small">
                            Data Points: {{ count($chart['data_points']) }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Analysis Section --}}
    @if(isset($data['analysis']))
        <div class="section mb-4">
            <h3 class="text-primary border-bottom pb-2">Analysis</h3>
            @foreach($data['analysis'] as $section)
                <div class="analysis-section mb-3">
                    <h5>{{ $section['title'] }}</h5>
                    <p>{{ $section['content'] }}</p>
                    
                    @if(isset($section['highlights']))
                        <ul class="list-unstyled">
                            @foreach($section['highlights'] as $highlight)
                                <li class="mb-1">
                                    <span class="text-primary">•</span> {{ $highlight }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Recommendations --}}
    @if(isset($data['recommendations']))
        <div class="section mb-4">
            <h3 class="text-primary border-bottom pb-2">Recommendations</h3>
            <ol>
                @foreach($data['recommendations'] as $recommendation)
                    <li class="mb-2">
                        <strong>{{ $recommendation['title'] }}</strong>
                        @if(isset($recommendation['description']))
                            <br><span class="text-muted">{{ $recommendation['description'] }}</span>
                        @endif
                        @if(isset($recommendation['priority']))
                            <br><small class="badge bg-{{ $recommendation['priority'] === 'high' ? 'danger' : ($recommendation['priority'] === 'medium' ? 'warning' : 'info') }}">
                                {{ ucfirst($recommendation['priority']) }} Priority
                            </small>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
@endsection

@section('footer')
    <div class="row small text-muted">
        <div class="col-6">
            @if(isset($report['confidential']) && $report['confidential'])
                <strong class="text-danger">CONFIDENTIAL</strong>
            @endif
        </div>
        <div class="col-6 text-end">
            @if(isset($report['prepared_by']))
                Prepared by: {{ $report['prepared_by'] }}
            @endif
        </div>
    </div>
@endsection
