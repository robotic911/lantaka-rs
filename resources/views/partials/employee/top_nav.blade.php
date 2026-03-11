<link rel="stylesheet" href="{{ asset('css/employee_top_nav.css') }}">     
@vite(['resources/js/top_nav.js'])
            <div class="header-left">
                <button class="menu-toggle">☰</button>
            </div>
            <div class="header-right">
                <button class="icon-btn">
                  <img src="{{ asset('images/logo/topnav/notification-bell.svg') }}" alt="reservations">
                </button>
                <div class="user-profile" id="open-modal">
                    <div class="user-avatar">
                      <img src="{{ asset(path: 'images/logo/topnav/user-avatar.svg') }}" alt="reservations">
                    </div>
                    <div class="user-info">
                        <p class="user-name">Welcome, {{ Auth::user()->name }}!</p>
                        
                        <p class="user-role">{{ ucfirst(Auth::user()->role) }}</p>
                    </div>
                </div>

                <div class="user-profile-modal">
                  <a href="#" class="modal-link">
                    <button class="btn-view-account">View your account</button>
                  </a>
                  <form class="modal-form" action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-logout">Logout</button>
                  </form>
                </div>
            </div>
