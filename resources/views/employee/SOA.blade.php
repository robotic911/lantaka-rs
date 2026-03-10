@extends('layouts.employee')
<link rel="stylesheet" href="{{ asset('css/SOA.css') }}">
@vite('resources/js/SOA.js')

@section('content')
  <div class="soa-container">
    <h1 class="soa-title">Generation of SOA</h1>
    
    <div class="soa-main-content">
      <div class="soa-left-section">
        <div class="soa-form-group">
          <label class="soa-form-label">To</label>
          <input type="text" class="soa-form-input" value="{{ $client->name }}" readonly>
          <label class="soa-form-label">Date:</label>
          <input type="date" class="soa-form-input" value="{{ now()->format('Y-m-d') }}">
        </div>

        <div class="soa-particulars-section">
          <h3 class="soa-particulars-title">Select Particulars:</h3>
          
          <div class="soa-table-wrapper">
            <table class="soa-particulars-table">
              <thead>
                <tr class="soa-table-header">
                  <th class="soa-col-accommodation">ACCOMMODATION</th>
                  <th class="soa-col-checkin">CHECK-IN</th>
                  <th class="soa-col-checkout">CHECK-OUT</th>
                  <th class="soa-col-pax">NO. OF PAX</th>
                </tr>
              </thead>
              <tbody class="soa-table-body">
                @foreach($reservations as $index => $r)

                <tr class="soa-table-row" data-soa-id="{{ $index }}">
                    <td class="soa-accommodation-cell">{{ $r['name'] }}</td>
                    <td class="soa-date-cell">{{ $r['check_in'] }}</td>
                    <td class="soa-date-cell">{{ $r['check_out'] }}</td>
                    <td class="soa-pax-cell">{{ $r['pax'] }}</td>
                </tr>

                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="soa-right-section">
        <h3 class="soa-preview-title">Preview</h3>
        
        <div class="soa-preview-list" id="soaPreviewList">
        </div>

        <button class="soa-export-btn">EXPORT STATEMENT OF ACCOUNTS</button>
      </div>
    </div>
  </div>
@endsection