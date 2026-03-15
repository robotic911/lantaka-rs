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
              @php $lastSoaDate = null; @endphp
              @foreach($reservations as $index => $r)
                  <tr
                    class="soa-table-row"
                    data-group="soa-group-{{ $index }}"
                    data-id="{{ $r['id'] }}"
                    data-type="{{ $r['type'] }}"
                    data-name="{{ $r['name'] }}"
                    data-days="{{ $r['days'] ?? 1 }}"
                    data-price="{{ $r['base_price'] ?? $r['total_price'] }}"
                    data-discount="{{ $r['discount'] ?? 0 }}"
                    data-fee-items='@json($r["additional_fee_items"] ?? [])'
                  >
                  <td>{{ $r['check_in'] }}</td>

                  <td>
                    {{ $r['name'] }}<br>
                    <small>{{ $r['check_in'] }} - {{ $r['check_out'] }}</small>
                  </td>

                  <td>{{ $r['pax'] }}</td>
                  <td>{{ $r['days'] ?? 1 }} day</td>
                  <td>₱ {{ number_format((($r['base_price'] ?? 0) / ($r['days'] ?? 1)), 2) }}</td>
                  <td><strong>₱ {{ number_format($r['base_price'] ?? 0, 2) }}</strong></td>
                </tr>

                @if(!empty($r['additional_fee_items']))
                  @foreach($r['additional_fee_items'] as $fee)
                  @php
                    $feeDate = !empty($fee['date']) ? \Carbon\Carbon::parse($fee['date'])->format('m/d/Y') : '';
                    $showDate = ($feeDate !== '' && $feeDate !== $lastSoaDate) ? $feeDate : '';
                    if ($feeDate !== '') $lastSoaDate = $feeDate;
                  @endphp
                  <tr class="soa-extra-row" data-group="soa-group-{{ $index }}">
                    <td>{{ $showDate }}</td>
                    <td style="padding-left:30px;">
                      + {{ $fee['desc'] }}
                    </td>
                    <td>{{ $fee['qty'] }}</td>
                    <td>pc</td>
                    <td>₱ {{ number_format($fee['amount'], 2) }}</td>
                    <td>₱ {{ number_format($fee['line_total'], 2) }}</td>
                  </tr>
                  @endforeach
                @endif

                @if(!empty($r['discount']) && $r['discount'] > 0)
                <tr class="soa-extra-row" data-group="soa-group-{{ $index }}">
                  <td></td>
                  <td style="padding-left:30px; color:#c0392b;">
                    - Discount
                  </td>
                  <td>1</td>
                  <td>deduction</td>
                  <td style="color:#c0392b;">₱ {{ number_format($r['discount'], 2) }}</td>
                  <td style="color:#c0392b;">₱ {{ number_format($r['discount'], 2) }}</td>
                </tr>
                @endif
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


      <form id="soaExportForm" method="GET" action="{{ route('export.exportSOA', $client->id) }}">
        <input type="hidden" name="selected_items" id="selectedItemsInput">
        <button type="submit" class="soa-export-btn">
          EXPORT STATEMENT OF ACCOUNTS
        </button>
      </form>
    </div>

  </div>
</div>
@endsection