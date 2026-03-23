<link rel="stylesheet" href="{{ asset('css/employee_top_nav.css') }}">
@vite(['resources/js/top_nav.js'])
            <div class="header-left">
                <button class="menu-toggle">☰</button>
            </div>
            <div class="header-right">
                @php
                    // Employee bell shows total un-reviewed audit entries from today
                    $empUnread = \App\Models\EventLog::whereNull('Event_Logs_Notifiable_User_ID')
                        ->whereDate('created_at', today())->count();
                @endphp
                <a href="{{ route('employee.eventlogs') }}" class="icon-btn" id="bellBtn" style="position:relative;text-decoration:none;" title="View Action Logs">
                    <img src="{{ asset('images/logo/topnav/notification-bell.svg') }}" alt="Action Logs">
                    @if($empUnread > 0)
                        <span id="bellBadge" style="
                            position:absolute; top:-3px; right:-3px;
                            background:#dc2626; color:#fff;
                            font-size:10px; font-weight:700;
                            min-width:16px; height:16px;
                            border-radius:8px; display:flex;
                            align-items:center; justify-content:center;
                            padding:0 3px; pointer-events:none; line-height:1;
                        ">{{ $empUnread > 9 ? '9+' : $empUnread }}</span>
                    @endif
                </a>
                <script>
                  (function () {
                    var btn = document.getElementById('bellBtn');
                    if (btn) {
                      btn.addEventListener('click', function () {
                        var badge = document.getElementById('bellBadge');
                        if (badge) badge.style.display = 'none';
                      });
                    }
                  })();
                </script>
                <div class="user-profile" id="open-modal">
                    <div class="user-avatar">
                      <img src="{{ asset(path: 'images/logo/topnav/user-avatar.svg') }}" alt="reservations">
                    </div>
                    <div class="user-info">
                        <p class="user-name">Welcome, {{ Auth::user()->Account_Name }}!</p>

                        <p class="user-role">{{ ucfirst(Auth::user()->Account_Role) }}</p>
                    </div>
                </div>

                <div class="user-profile-modal">
                  <!--  <a href="#" class="modal-link">
                          <button class="btn-view-account">View your account</button>
                        </a>
                  -->
                  <form class="modal-form" action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-logout">Logout</button>
                  </form>
                </div>
            </div>
