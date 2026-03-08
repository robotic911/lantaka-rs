@extends('layouts.employee')
<link rel="stylesheet" href="{{ asset('css/SOA.css') }}">
@vite('resources/js/SOA.js')
@section('content')
  <div class="soa-container">
    <h1 class="soa-title">Generation of SOA</h1>
    
    <div class="soa-main-content">
      <!-- LEFT COLUMN -->
      <div class="soa-left-section">
        <div class="soa-form-group">
          <label class="soa-form-label">To</label>
          <input type="text" class="soa-form-input">
          <label class="soa-form-label">Date:</label>
          <input type="date" class="soa-form-input">
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
                <tr class="soa-table-row" data-soa-id="1">
                  <td class="soa-accommodation-cell">Room: 101</td>
                  <td class="soa-date-cell">03/23/2026</td>
                  <td class="soa-date-cell">03/24/2026</td>
                  <td class="soa-pax-cell">1</td>
                </tr>

                <tr class="soa-table-row" data-soa-id="2">
                  <td class="soa-accommodation-cell">Room: 101</td>
                  <td class="soa-date-cell">03/27/2026</td>
                  <td class="soa-date-cell">03/28/2026</td>
                  <td class="soa-pax-cell">1</td>
                </tr>

                <tr class="soa-table-row" data-soa-id="2">
                  <td class="soa-accommodation-cell">Room: 101</td>
                  <td class="soa-date-cell">03/27/2026</td>
                  <td class="soa-date-cell">03/28/2026</td>
                  <td class="soa-pax-cell">1</td>
                </tr>
                <tr class="soa-table-row" data-soa-id="2">
                  <td class="soa-accommodation-cell">Room: 101</td>
                  <td class="soa-date-cell">03/27/2026</td>
                  <td class="soa-date-cell">03/28/2026</td>
                  <td class="soa-pax-cell">1</td>
                </tr>
                <tr class="soa-table-row" data-soa-id="2">
                  <td class="soa-accommodation-cell">Room: 101</td>
                  <td class="soa-date-cell">03/27/2026</td>
                  <td class="soa-date-cell">03/28/2026</td>
                  <td class="soa-pax-cell">1</td>
                </tr>
                <tr class="soa-table-row" data-soa-id="2">
                  <td class="soa-accommodation-cell">Room: 101</td>
                  <td class="soa-date-cell">03/27/2026</td>
                  <td class="soa-date-cell">03/28/2026</td>
                  <td class="soa-pax-cell">1</td>
                </tr>

                
                <tr class="soa-table-row" data-soa-id="3">
                  <td class="soa-accommodation-cell">Room: Room Test</td>
                  <td class="soa-date-cell">03/14/2026</td>
                  <td class="soa-date-cell">03/14/2026</td>
                  <td class="soa-pax-cell">2</td>
                </tr>

                <tr class="soa-table-row soa-row-selected" data-soa-id="4">
                  <td class="soa-accommodation-cell">Room: Room Test</td>
                  <td class="soa-date-cell">03/05/2026</td>
                  <td class="soa-date-cell">03/05/2026</td>
                  <td class="soa-pax-cell">2</td>
                </tr>

                <tr class="soa-table-row" data-soa-id="5">
                  <td class="soa-accommodation-cell">Room: 101</td>
                  <td class="soa-date-cell">03/23/2026</td>
                  <td class="soa-date-cell">03/24/2026</td>
                  <td class="soa-pax-cell">1</td>
                </tr>

                <tr class="soa-table-row soa-row-selected" data-soa-id="6">
                  <td class="soa-accommodation-cell">Room: 101</td>
                  <td class="soa-date-cell">03/27/2026</td>
                  <td class="soa-date-cell">03/28/2026</td>
                  <td class="soa-pax-cell">1</td>
                </tr>

                <tr class="soa-table-row" data-soa-id="7">
                  <td class="soa-accommodation-cell">Room: Room Test</td>
                  <td class="soa-date-cell">03/14/2026</td>
                  <td class="soa-date-cell">03/14/2026</td>
                  <td class="soa-pax-cell">2</td>
                </tr>

                <tr class="soa-table-row" data-soa-id="8">
                  <td class="soa-accommodation-cell">Room: Room Test</td>
                  <td class="soa-date-cell">03/05/2026</td>
                  <td class="soa-date-cell">03/05/2026</td>
                  <td class="soa-pax-cell">2</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="soa-right-section">
        <h3 class="soa-preview-title">Preview</h3>
        
        <div class="soa-preview-list">
          <div class="soa-preview-item">
            <div class="soa-preview-row">
              <span class="soa-preview-room">Room Test</span>
              <span class="soa-preview-duration">1 days/nights</span>
              <span class="soa-preview-price">₱ 25,000</span>
            </div>
          </div>

          <div class="soa-preview-item">
            <div class="soa-preview-row">
              <span class="soa-preview-room">Room 101</span>
              <span class="soa-preview-duration">2 day/night</span>
              <span class="soa-preview-price">₱ 17,000</span>
            </div>
          </div>
        </div>

        <button class="soa-export-btn">EXPORT STATEMENT OF ACCOUNTS</button>
      </div>
    </div>
  </div>
@endsection