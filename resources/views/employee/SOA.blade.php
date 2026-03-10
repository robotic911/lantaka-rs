@extends('layouts.employee')
<link rel="stylesheet" href="{{ asset('css/SOA.css') }}">
@vite('resources/js/SOA.js')

@section('content')
<div class="soa-container">
  <h1 class="soa-title">Generation of SOA</h1>

  <div class="soa-main-content">

    <!-- LEFT = PREVIEW -->
    

    <!-- RIGHT = TABLE -->
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
          <table class="soa-particulars-table soa-official-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Particulars</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Rate</th>
                <th>Amount</th>
              </tr>
            </thead>

            <tbody>
              @foreach($reservations as $index => $r)
              <tr
                class="soa-table-row"
                data-name="{{ $r['name'] }}"
                data-days="{{ $r['days'] ?? 1 }}"
                data-price="{{ $r['total_price'] }}"
              >
                <td>{{ $r['check_in'] }}</td>
                <td>
                  {{ $r['name'] }}<br>
                  <small>{{ $r['check_in'] }} - {{ $r['check_out'] }}</small>
                </td>
                <td>{{ $r['pax'] }}</td>
                <td>{{ $r['days'] ?? 1 }} day</td>
                <td>₱ {{ number_format(($r['total_price'] / ($r['days'] ?? 1)), 2) }}</td>
                <td><strong>₱ {{ number_format($r['total_price'], 2) }}</strong></td>
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


      <button class="soa-export-btn">
        <a href="{{ route('export.exportSOA', $client->id) }}" class="soa-export-btn" style="text-decoration: none;">
          EXPORT STATEMENT OF ACCOUNTS
        </a>
      </button>
    </div>

  </div>
</div>
@endsection