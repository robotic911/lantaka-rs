@props(['accommodations'])

<div class="cards-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">

    @foreach($accommodations as $item)
        <div class="card">
          
          <div class="card-image">
             <img src="{{ $item->image ? media_url($item->image) : 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=500' }}" alt="{{ $item->display_name }}">
          </div>

          <div class="card-content">
            <p class="card-type">{{ $item->category }}</p>
            <h3 class="card-title">{{ $item->display_name }}</h3>
            
            <div class="card-details">
              <span class="detail-item">👤 {{ $item->capacity }} Guests</span>
              <span class="detail-item">₱ {{ number_format($item->external_price, 2) }}</span>
            </div>

            <a href="{{ route('client.show', ['category' => $item->category, 'id' => $item->id]) }}" 
               class="book-btn" 
               style="text-decoration:none; text-align:center; display:block; margin-top:10px;">
                View Details
            </a>

          </div>
        </div>
    @endforeach

</div>