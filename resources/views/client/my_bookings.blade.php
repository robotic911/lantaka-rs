@extends('layouts.client')

<title>Checkout - Lantaka Reservation System</title>
<link rel="stylesheet" href="{{ asset('css/client_my_bookings.css') }}">
<style>
    .cart-item-actions {
        display: flex;
        gap: 8px;
        margin-top: 14px;
    }
    .cart-action-btn {
        padding: 6px 16px;
        font-size: 0.78rem;
        font-weight: 600;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        letter-spacing: 0.3px;
        transition: opacity 0.15s;
    }
    .cart-action-btn:hover { opacity: 0.8; }
    .cart-remove-btn { background: #fee2e2; color: #b91c1c; }
    .cart-edit-btn   { background: #e0f2fe; color: #0369a1; }
</style>
<link
    href="https://fonts.googleapis.com/css2?family=Alexandria:wght@200;300;400;500;600;700;800;900&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap"
    rel="stylesheet">
@vite('resources/js/my_booking.js')

@section('content')
    <div>
        <h1 class="page-title">Checkout</h1>
    </div>

    <div class="checkout-container" style="display: flex;">
        <section class="cart-items" style="flex: 3;">
            @forelse($processedItems as $item)

                <div class="cart-item"
                        data-name="{{ $item['name'] }}"
                        data-total="{{ $item['base_total'] }}"
                        data-base="{{ $item['price'] }}"
                        data-id="{{ $item['id'] }}"
                        data-type="{{ $item['type'] }}"
                        data-in="{{ $item['check_in_raw'] }}"
                        data-out="{{ $item['check_out_raw'] }}"
                        data-pax="{{ $item['pax'] }}"
                        data-food='@json($item['selected_foods'] ?? [])'
                        data-food-enabled='@json($item['food_enabled'] ?? [])'
                        data-meal-enabled='@json($item['meal_enabled'] ?? [])'
                        data-food-selections='@json($item['food_selections'] ?? [])'
                        data-purpose="{{ $item['purpose'] ?? '' }}"
                        
                    style="cursor: pointer;">

                    <div class="item-image">
                        <img src="{{ $item['img'] ? asset('storage/' . $item['img']) : asset('images/adzu_logo.png') }}"
                            alt="{{ $item['name'] }}">
                    </div>

                    <div class="item-details">
                        <div class="item-header">
                            <h3 class="item-name">{{ $item['name'] }}</h3>
                            <p class="item-price">₱ {{ number_format($item['price'], 2) }}</p>
                        </div>

                        <p class="item-type">{{ ucfirst($item['type']) }}</p>

                        <p class="item-guests">👥 {{ $item['pax'] }} Guests</p>
                        <p class="item-dates">
                            {{ $item['check_in'] }} -> {{ $item['check_out'] }}
                            @if($item['type'] == 'room')
                                <br>
                                <small>({{ $item['days'] ?? 0 }} Night{{ ($item['days'] ?? 0) > 1 ? 's' : '' }})</small>
                            @endif
                        </p>

                        @if(
                            $item['type'] === 'venue' &&
                            !empty($item['food_selections']) &&
                            isset($item['selected_foods']) &&
                            $item['selected_foods']->isNotEmpty()
                        )
                            @php

                                $foodsByDate = [];
                                foreach ($item['food_selections'] as $date => $meals) {
                                    if (($item['food_enabled'][$date] ?? '1') != '1') continue;
                                    $foodsByDate[$date] = [];
                                    foreach ($meals as $mealType => $categories) {
                                        if (!is_array($categories)) continue;
                                        foreach ($categories as $category => $foodId) {
                                            if (empty($foodId)) continue;
                                            $food = $item['selected_foods']->firstWhere('Food_ID', (int) $foodId);
                                            if ($food) $foodsByDate[$date][] = $food->Food_Name;
                                        }
                                    }
                                }
                            @endphp

                            <div class="item-food-list" style="margin-top: 14px;">
                                <p style="font-size:0.78rem; font-weight:600; color:#555; margin-bottom:8px;">🍽 Selected Foods by Date</p>
                                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                                    @foreach($foodsByDate as $date => $foods)
                                        @if(!empty($foods))
                                        <div style="background:#f8f8f8; border:1px solid #e5e7eb; border-radius:8px; padding:8px 12px; min-width:130px;">
                                            <p style="font-size:0.72rem; font-weight:700; color:#374151; margin-bottom:6px; border-bottom:1px solid #e5e7eb; padding-bottom:4px;">
                                                {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                                            </p>
                                            @foreach($foods as $foodName)
                                                <p style="font-size:0.72rem; color:#6b7280; margin:2px 0;">• {{ $foodName }}</p>
                                            @endforeach
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        {{-- Remove / Edit actions --}}
                        <div class="cart-item-actions">
                            <form action="{{ route('checkout.remove') }}" method="POST" class="cart-action-form">
                                @csrf
                                <input type="hidden" name="key" value="{{ $item['key'] }}">
                                <button type="submit" class="cart-action-btn cart-remove-btn" onclick="return confirm('Remove this item from your cart?')">🗑 Remove</button>
                            </form>
                            <form action="{{ route('checkout.edit') }}" method="POST" class="cart-action-form">
                                @csrf
                                <input type="hidden" name="key" value="{{ $item['key'] }}">
                                <button type="submit" class="cart-action-btn cart-edit-btn">✏️ Edit</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p>No items available for checkout.</p>
            @endforelse
        </section>

        <aside class="checkout-summary" style="flex: 1;">
            <h2 class="summary-title">Checkout Summary</h2>
            <div id="empty-msg" style="padding: 20px; text-align: center; color: #888;">
                Click on an item to see the summary.
            </div>

            <div id="summary-details" style="display: none;">
                <form action="{{ route('reservation.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="selected_items" id="selected-items-input">

                    <div class="summary-items" id="summary-items"></div>
                    <div id="summary-foods" style="margin-top: 10px; border-top: 1px dashed #ddd; padding-top: 10px;"></div>

                    <div class="summary-divider"></div>
                    <div class="total-section">
                        <span class="total-label">Total Payable</span>
                        <span class="total-amount" id="summary-grand-total"></span>
                    </div>
                    <button type="submit" class="confirm-btn">CONFIRM RESERVATION</button>
                </form>
            </div>
        </aside>
    </div>
@endsection
