@extends('layouts.client')
<title>Confirm Change Request - Lantaka Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

@section('content')
@php
    $checkIn  = \Carbon\Carbon::parse($bookingData['check_in']  ?? now())->format('F d, Y');
    $checkOut = \Carbon\Carbon::parse($bookingData['check_out'] ?? now())->format('F d, Y');
    $pax      = $bookingData['pax']     ?? '—';
    $purpose  = ucfirst($bookingData['purpose'] ?? '—');
    $resName  = $bookingData['res_name'] ?? ucfirst($bookingData['type'] ?? '') . ' ' . ($bookingData['accommodation_id'] ?? '');
@endphp

<style>
    .crc-wrap {
        max-width: 560px;
        margin: 48px auto;
        padding: 0 16px;
        font-family: 'Alexandria', sans-serif;
    }
    .crc-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,.10);
        padding: 36px 32px 28px;
    }
    .crc-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: #fff3cd;
        margin: 0 auto 20px;
        font-size: 28px;
    }
    .crc-title {
        text-align: center;
        font-size: 1.35rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 6px;
    }
    .crc-subtitle {
        text-align: center;
        font-size: .9rem;
        color: #6b7280;
        margin-bottom: 28px;
    }
    .crc-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 10px 0;
        border-bottom: 1px solid #f1f1f1;
        font-size: .95rem;
    }
    .crc-row:last-of-type { border-bottom: none; }
    .crc-label {
        color: #6b7280;
        font-weight: 500;
        flex: 0 0 140px;
    }
    .crc-value {
        color: #111827;
        font-weight: 600;
        text-align: right;
    }
    .crc-note {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: .85rem;
        color: #92400e;
        margin: 24px 0 28px;
        line-height: 1.5;
    }
    .crc-actions {
        display: flex;
        gap: 12px;
    }
    .crc-btn-back {
        flex: 1;
        padding: 12px;
        border-radius: 8px;
        border: 1.5px solid #d1d5db;
        background: #fff;
        color: #374151;
        font-size: .95rem;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: background .15s;
    }
    .crc-btn-back:hover { background: #f9fafb; }
    .crc-btn-confirm {
        flex: 2;
        padding: 12px;
        border-radius: 8px;
        border: none;
        background: #1a1a2e;
        color: #fff;
        font-size: .95rem;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        transition: background .15s;
    }
    .crc-btn-confirm:hover { background: #2d2d52; }
</style>

<div class="crc-wrap">
    <div class="crc-card">
        <div class="crc-icon">📋</div>
        <p class="crc-title">Confirm Request for Changes</p>
        <p class="crc-subtitle">Review the new details below before submitting your request.</p>

        <div class="crc-row">
            <span class="crc-label">Accommodation</span>
            <span class="crc-value">{{ $resName }}</span>
        </div>
        <div class="crc-row">
            <span class="crc-label">Check-in</span>
            <span class="crc-value">{{ $checkIn }}</span>
        </div>
        <div class="crc-row">
            <span class="crc-label">Check-out</span>
            <span class="crc-value">{{ $checkOut }}</span>
        </div>
        @if(($bookingData['type'] ?? '') === 'venue')
        <div class="crc-row">
            <span class="crc-label">No. of Pax</span>
            <span class="crc-value">{{ $pax }}</span>
        </div>
        @endif
        <div class="crc-row">
            <span class="crc-label">Purpose</span>
            <span class="crc-value">{{ $purpose }}</span>
        </div>

        <div class="crc-note">
            ⚠️ This is a <strong>request</strong> — your reservation will not change until an admin or staff member approves it.
        </div>

        <div class="crc-actions">
            <button type="button" class="crc-btn-back" onclick="window.history.back();">Go Back</button>

            <form action="{{ route('client.reservations.storeChangeRequest') }}" method="POST" style="flex:2; display:contents;">
                @csrf
                <input type="hidden" name="accommodation_id" value="{{ $bookingData['accommodation_id'] ?? '' }}">
                <input type="hidden" name="res_name"         value="{{ $bookingData['res_name']         ?? '' }}">
                <input type="hidden" name="type"             value="{{ $bookingData['type']             ?? '' }}">
                <input type="hidden" name="check_in"         value="{{ $bookingData['check_in']         ?? '' }}">
                <input type="hidden" name="check_out"        value="{{ $bookingData['check_out']        ?? '' }}">
                <input type="hidden" name="pax"              value="{{ $bookingData['pax']              ?? '' }}">
                <input type="hidden" name="purpose"          value="{{ $bookingData['purpose']          ?? '' }}">
                <input type="hidden" name="change_request"   value="1">

                <button type="submit" class="crc-btn-confirm">Submit Change Request</button>
            </form>
        </div>
    </div>
</div>
@endsection
