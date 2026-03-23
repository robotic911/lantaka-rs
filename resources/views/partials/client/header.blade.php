<link rel="stylesheet" href="{{asset('css/header.css')}}">
@vite(['resources/js/top_nav.js'])

    <div class="header-container">
    
        <div class="logo-section">
            <img src="{{ asset('images/adzu_logo.png') }}" class="logo">
            <div class="header-text">
                <p class="subtitle-text">Ateneo de Zamboanga University</p>
                <h1 class="header-title">Lantaka Room and Venue Reservation Portal</h1>
                <h1 class="tagline"> &lt;Lantaka Online Room & Venue Reservation System/&gt; </h1>
            </div>
        </div>
     

        <nav class="nav">
            <a href="{{ route('client.room_venue') }}"
               class="nav-link nav-item {{ request()->routeIs('client.room_venue') ? 'active' : '' }}">
               Accommodation
            </a>

            @guest
                <a href="{{ route('login') }}" class="nav-link">Login</a>
            @endguest

            @auth
                <a href="{{ route('client.my_bookings') }}"
                   class="nav-link nav-item {{ request()->routeIs('client.my_bookings') ? 'active' : '' }}">
                   My Booking
                </a>

                <a href="{{ route('client.my_reservations') }}"
                   class="nav-link nav-item {{ request()->routeIs('client.my_reservations') ? 'active' : '' }}">
                   My Reservations
                </a>

                {{-- ── Notification Bell (powered by event_logs) ── --}}
                @php
                    $unreadCount  = \App\Models\EventLog::where('Event_Logs_Notifiable_User_ID', Auth::id())
                                        ->where('Event_Logs_isRead', false)->count();
                    $recentNotifs = \App\Models\EventLog::where('Event_Logs_Notifiable_User_ID', Auth::id())
                                        ->orderByDesc('created_at')->limit(10)->get();
                @endphp
                <div class="notif-wrap" id="notifWrap">
                    <button class="icon-btn notif-bell-btn" id="notifBellBtn" aria-label="Notifications">
                        <img src="{{ asset('images/logo/topnav/notification-bell.svg') }}" alt="Notifications">
                        @if($unreadCount > 0)
                            <span class="notif-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </button>

                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-dropdown-header">
                            <span>Notifications</span>
                            @if($unreadCount > 0)
                                <form method="POST" action="{{ route('client.notifications.readAll') }}" style="margin:0">
                                    @csrf
                                    <button type="submit" class="notif-mark-all">Mark all read</button>
                                </form>
                            @endif
                        </div>
                        <div class="notif-list">
                            @forelse($recentNotifs as $notif)
                                <a href="{{ $notif->Event_Logs_Link ?? '/client/my_reservations' }}"
                                   class="notif-item {{ $notif->Event_Logs_isRead ? 'notif-read' : 'notif-unread' }}"
                                   onclick="markNotifRead({{ $notif->Event_Logs_ID }}, this, event)">
                                    <span class="notif-dot notif-dot--{{ $notif->Event_Logs_Type ?? 'default' }}"></span>
                                    <div class="notif-text">
                                        <p class="notif-title">{{ $notif->Event_Logs_Title ?? ucfirst(str_replace('_', ' ', $notif->Event_Logs_Action)) }}</p>
                                        <p class="notif-msg">{{ Str::limit($notif->Event_Logs_Message, 70) }}</p>
                                        <p class="notif-time">{{ $notif->created_at->diffForHumans() }}</p>
                                    </div>
                                </a>
                            @empty
                                <p class="notif-empty">You have no notifications yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <style>
                /* ── Notification bell wrapper ── */
                .notif-wrap { position: relative; }

                .notif-bell-btn { position: relative; }

                .notif-badge {
                    position: absolute;
                    top: -4px; right: -4px;
                    background: #dc2626;
                    color: #fff;
                    font-size: 10px;
                    font-weight: 700;
                    min-width: 17px;
                    height: 17px;
                    border-radius: 9px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 0 3px;
                    line-height: 1;
                    pointer-events: none;
                }

                /* ── Dropdown panel ── */
                .notif-dropdown {
                    display: none;
                    position: absolute;
                    top: calc(100% + 10px);
                    right: 0;
                    width: 340px;
                    background: #fff;
                    border-radius: 12px;
                    box-shadow: 0 8px 30px rgba(0,0,0,.14);
                    border: 1px solid #e9eaec;
                    z-index: 2000;
                    overflow: hidden;
                }
                .notif-dropdown.open { display: block; }

                .notif-dropdown-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 14px 16px 10px;
                    border-bottom: 1px solid #f0f0f0;
                    font-size: 13px;
                    font-weight: 700;
                    color: #1f2937;
                }

                .notif-mark-all {
                    background: none;
                    border: none;
                    font-size: 11px;
                    color: #1e3a8a;
                    cursor: pointer;
                    font-weight: 600;
                    padding: 0;
                }
                .notif-mark-all:hover { text-decoration: underline; }

                .notif-list {
                    max-height: 340px;
                    overflow-y: auto;
                }
                .notif-list::-webkit-scrollbar { width: 4px; }
                .notif-list::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }

                .notif-item {
                    display: flex;
                    align-items: flex-start;
                    gap: 10px;
                    padding: 12px 16px;
                    border-bottom: 1px solid #f5f5f5;
                    text-decoration: none;
                    transition: background .12s;
                    cursor: pointer;
                }
                .notif-item:last-child { border-bottom: none; }
                .notif-item:hover { background: #f8f9fb; }
                .notif-unread { background: #eff6ff; }
                .notif-read   { background: #fff; }

                .notif-dot {
                    width: 9px; height: 9px;
                    border-radius: 50%;
                    flex-shrink: 0;
                    margin-top: 5px;
                }
                .notif-dot--confirmed   { background: #34d399; }
                .notif-dot--checked-in  { background: #065f46; }
                .notif-dot--checked-out { background: #f59e0b; }
                .notif-dot--completed   { background: #f59e0b; }
                .notif-dot--cancelled   { background: #ef4444; }
                .notif-dot--rejected    { background: #ef4444; }
                .notif-dot--default     { background: #9ca3af; }

                .notif-text { flex: 1; min-width: 0; }
                .notif-title { font-size: 13px; font-weight: 700; color: #1f2937; margin: 0 0 2px; }
                .notif-msg   { font-size: 12px; color: #6b7280; margin: 0 0 3px; line-height: 1.4; }
                .notif-time  { font-size: 11px; color: #9ca3af; margin: 0; }

                .notif-empty { font-size: 13px; color: #9ca3af; text-align: center; padding: 24px 16px; margin: 0; }
                </style>

                <script>
                (function () {
                    const btn      = document.getElementById('notifBellBtn');
                    const dropdown = document.getElementById('notifDropdown');
                    if (!btn || !dropdown) return;

                    btn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        dropdown.classList.toggle('open');
                    });

                    document.addEventListener('click', function (e) {
                        if (!dropdown.contains(e.target) && e.target !== btn) {
                            dropdown.classList.remove('open');
                        }
                    });
                })();

                function markNotifRead(id, linkEl, e) {
                    e.preventDefault();
                    const href = linkEl.getAttribute('href');
                    fetch(`/client/notifications/${id}/read`, {
                        method:  'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                        },
                    }).finally(() => { window.location.href = href; });
                }
                </script>

                <div class="user-profile" id="open-modal">
                    <div class="user-avatar">
                        <img src="{{ asset(path: 'images/logo/topnav/user-avatar.svg') }}" alt="reservations">
                    </div>
                    <div class="user-info">
                        <p class="user-name">{{ Auth::user()->Account_Username ?? 'Client' }}</p>
                    </div>
                </div>

                <div class="user-profile-modal">
                    <a href="{{ route('client.account') }}" class="modal-link">
                        <button class="btn-view-account">View your account</button>
                    </a>
                    <form class="modal-form" action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-logout">Logout</button>
                    </form>
                </div>
            @endauth

        </nav>
    </div>
