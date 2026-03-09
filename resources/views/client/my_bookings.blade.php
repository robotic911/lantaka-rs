@extends('layouts.client')
    <title>Checkout - Lantaka Reservation System</title>
    <link rel="stylesheet" href="{{ asset('css/client_my_bookings.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@200;300;400;500;600;700;800;900&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    @vite('resources/js/my_booking.js')


@section('content')
        
        <h1 class="page-title">Checkout</h1>

        <div class="checkout-container">

            <section class="cart-items">
                @forelse($processedItems as $item)
                    <div class="cart-item" 
                    onclick="selectItem(
                        '{{ $item['name'] }}', 
                        '{{ $item['total'] }}', 
                        '{{ $item['id'] }}', 
                        '{{ $item['type'] }}', 
                        '{{ $item['check_in_raw'] }}', 
                        '{{ $item['check_out_raw'] }}', 
                        '{{ $item['pax'] }}',
                        '{{ json_encode($item['selected_foods'] ?? []) }}' {{-- ADD THIS LINE --}}
                    )" 
                    style="cursor: pointer; margin-bottom: 15px;">
                        
                        <div class="item-image">                    
                            <img src="{{ $item['img'] ? asset('storage/' . $item['img']) : asset('images/adzu_logo.png') }}" alt="Item">
                        </div>
                        <div class="item-details">
                            <div class="item-header">
                                <h3 class="item-name">{{ $item['name'] }}</h3>
                                <p class="item-price">₱ {{ number_format($item['price'], 2) }}</p>
                            </div>
                            <p class="item-type">{{ ucfirst($item['type']) }}</p>
                            <p class="item-guests">👥 {{ $item['pax'] }} Guests</p>
                            <p class="item-dates">
                                {{ $item['check_in'] }} • {{ $item['check_out'] }}
                                <br>
                                <small>({{ $item['days'] ?? 0 }} Nights)</small>
                            </p>
                             @if(!empty($item['selected_foods']) && count($item['selected_foods']) > 0)
                                <div class="item-food-list" style="margin-top: 8px; font-size: 0.85em; color: #555;">
                                    <strong style="display: block; margin-bottom: 2px;">Selected Foods:</strong>
                                    @foreach($item['selected_foods'] as $food)
                                        <span style="display: block; padding-left: 5px;">• {{ $food['food_name'] }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-tray-container" style="text-align: center; padding: 50px; background: #f9f9f9; border-radius: 15px; border: 2px dashed #ddd;">
                        <div style="font-size: 50px; margin-bottom: 10px;">🛒</div>
                        <h3 style="color: #555; font-family: 'Alexandria', sans-serif;">empty</h3>
                        <p style="color: #6e5757; font-family: 'Arsenal', sans-serif;">You haven't selected any accommodations yet.</p>
                        <a href="{{ route('client.room_venue') }}" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #333; color: white; text-decoration: none; border-radius: 5px;">
                            Find a Room or Venue
                        </a>
                    </div>
                @endforelse
            </section>

            <aside class="checkout-summary">
                <h2 class="summary-title">Checkout Summary</h2>
                <div id="empty-msg" style="padding: 20px; text-align: center; color: #888;">
                    Click on an item to see the summary.
                </div>

                <div id="summary-details" style="display: none;">
                    <form action="{{ route('reservation.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="id" id="form-id">
                        <input type="hidden" name="type" id="form-type">
                        <input type="hidden" name="check_in" id="form-check-in">
                        <input type="hidden" name="check_out" id="form-check-out">
                        <input type="hidden" name="pax" id="form-pax">
                        <input type="hidden" name="total_price" id="form-total-price">
                        <input type="hidden" name="total_amount" id="form-total-amount">

                        <div class="summary-items">
                            <div class="summary-item">
                                <span class="item-label" id="summary-name"></span>
                                <span class="item-amount" id="summary-total"></span>
                            </div>
                        </div>
                        <div id="summary-foods" style="margin-top: 10px; border-top: 1px dashed #ddd; padding-top: 10px;">
                {{-- JavaScript will inject food rows here --}}
            </div>
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
    </main>
    
@endsection