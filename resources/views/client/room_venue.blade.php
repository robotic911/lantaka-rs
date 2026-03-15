@extends('layouts.client')
  <title>Book Now - Lantaka Room and Venue Reservation Portal</title>
  <link rel="stylesheet" href="{{asset('css/client_room_venue.css')}}">
  <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@700;800&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

@section('content')
    <section class="hero">
      <h2 class="hero-title">Purposeful spaces.</h2>
      <p class="hero-subtitle">Where Faith, Fellowship, and Formation Come Together.</p>
    </section>

    <section class="filters-section">
      <form action="{{ route('client.index') }}" method="GET" id="filterForm">
        <div class="filter-tabs">
            <input type="hidden" name="type" id="typeInput" value="{{ request('type', 'All') }}">
            
            <button type="button" class="tab-btn {{ request('type', 'All') == 'All' ? 'active' : '' }}" onclick="filterTab('All')">All</button>
            <button type="button" class="tab-btn {{ request('type') == 'Rooms' ? 'active' : '' }}" onclick="filterTab('Rooms')">Rooms</button>
            <button type="button" class="tab-btn {{ request('type') == 'Venue' ? 'active' : '' }}" onclick="filterTab('Venue')">Venue</button>
        </div>

        <div class="filter-dropdowns">
          <select name="capacity" class="dropdown" onchange="this.form.submit()">
            <option value="">Capacity</option>
            <option value="2" {{ request('capacity') == '2' ? 'selected' : '' }}>2 Guests</option>
            <option value="4" {{ request('capacity') == '4' ? 'selected' : '' }}>4 Guests</option>
            <option value="50+" {{ request('capacity') == '50+' ? 'selected' : '' }}>50+ Guests</option>
          </select>
          
          
        </div>
      </form>
    </section>

    <script>
      function filterTab(type) {
          document.getElementById('typeInput').value = type;
          document.getElementById('filterForm').submit();
      }
    </script>

    <section class="accommodations">
      
        @if(isset($all_accommodations) && $all_accommodations->isNotEmpty())
            
            @foreach($all_accommodations as $item)
              <a href="{{ route('client.show', parameters: ['category' => $item->category, 'id' => $item->id]) }}" 
              class="book-btn">
                <div class="accommodations-grid">
                    <div class="card">
                        <!-- <div class="card-image">
                            <img src="{{ $item->image ? asset('storage/' . $item->image) : asset('images/adzu_logo.png') }}" 
                                alt="{{ $item->display_name }}">
                        </div> -->

                        <div class="card-content">
                            <div>
                                <p class="card-type">{{ $item->category }}</p>
                                <h3 class="card-title">{{ $item->display_name }}</h3>
                                
                                <div class="card-details">
                                    <span class="detail-item">👤 {{ $item->capacity }} Guests</span>
                                    @if (isset(Auth()->user()->usertype))
                                        @if ( Auth()->user()->usertype == 'Internal')
                                          <span class="detail-item">₱ {{ number_format($item->price, 2) }}</span>
                                        @else
                                          <span class="detail-item">₱ {{ number_format($item->external_price, 2) }}</span>
                                      @endif
                                    @else
                                    <span class="detail-item">₱ {{ number_format($item->external_price, 2) }}</span>
                                    @endif
                                  
                                </div>
                            </div>            
                        </div>
                    </div>
                </div>
              </a>
            @endforeach
                
        @else
            <p style="grid-column: 1 / -1; text-align: center;">No rooms or venues found.</p>
        @endif
    </section>
@endsection