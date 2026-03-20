<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign up - Lantaka Room and Venue Reservation System</title>
  <link rel="stylesheet" href="{{ asset('css/signup.css') }}">
  <style>
    .signup-info-note {
      display: flex; align-items: flex-start; gap: 8px;
      background: #eef3fb; border: 1px solid #c5d5ee;
      border-radius: 8px; padding: 10px 14px;
      font-size: 12.5px; color: #1e3a5f; line-height: 1.5;
      margin-bottom: 4px;
    }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@200;300;400;500;600;700;800;900&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
  <div class="signup-container">
    <div class="background"></div>
    <div class="card-header">
        <div class="logo-section">
          <div class="logo-icon">
            <img src="{{ asset('images/adzu_logo.png') }}" class="logo">
          </div>
          <p class="university-name">Ateneo de Zamboanga University</p>
        </div>
        <h1 class="main-title">Lantaka Room and Venue Reservation System</h1>
        <p class="subtitle"> &lt; Lantaka Online Room & Venue Reservation System /&gt; </p>
      </div>

    <div class="signup-card">
      
      @if ($errors->any())
        <div class="error-container" style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li style="font-size: 0.85rem;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
      @endif

      @if(session('success'))
        <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
            {{ session('success') }}
        </div>
      @endif
      
      <form class="signup-form" method="POST" action="{{ route('register.post') }}" enctype="multipart/form-data">
        @csrf
        <h2 class="form-title">Sign up</h2>

        <div class="form-grid">
          <div class="form-column">
            <div class="form-group">
              <label for="firstName">First Name</label>
              <input type="text" id="firstName" name="firstName" placeholder="Enter First Name" value="{{ old('firstName') }}" required>
            </div>

            <div class="form-group">
              <label for="lastName">Last Name</label>
              <input type="text" id="lastName" name="lastName" placeholder="Enter Last Name" value="{{ old('lastName') }}" required>
            </div>

            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input type="tel" id="phone" name="phone" placeholder="Enter Phone Number" value="{{ old('phone') }}" required>
            </div>

            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" placeholder="Enter Email" value="{{ old('email') }}" required>
            </div>

            <div class="form-group">
              <label for="affiliation">Affiliation</label>
              <select id="affiliation" name="affiliation" required>
                <option value="">Enter Affiliation</option>
                <option value="student" {{ old('affiliation') == 'student' ? 'selected' : '' }}>Student</option>
                <option value="faculty" {{ old('affiliation') == 'faculty' ? 'selected' : '' }}>Faculty</option>
                <option value="student" {{ old('affiliation') == 'organization' ? 'selected' : '' }}>Organization</option>
                <option value="external" {{ old('affiliation') == 'external' ? 'selected' : '' }}>External</option>
              </select>
            </div>
          </div>

          <div class="form-column">
            <div class="form-group">
              <label for="username">Username</label>
              <input type="text" id="username" name="username" placeholder="Enter Username" value="{{ old('username') }}" required>
            </div>

            <div class="signup-info-note">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10" stroke="#1e3a5f" stroke-width="1.8"/><path d="M12 8v4m0 4h.01" stroke="#1e3a5f" stroke-width="1.8" stroke-linecap="round"/></svg>
              <span>No password needed. Once your account is approved, your login credentials will be sent to your registered email.</span>
            </div>

            <div class="form-group">
                <label for="validId">Valid ID</label>
                <div class="file-upload">
                    <label for="validId" class="upload-label" id="drop-zone">
                        <div id="upload-placeholder">
                            <div class="upload-icon">⬇️</div>
                            <p class="upload-text">Upload Image</p>
                            <p class="upload-hint">Click or drag to upload image</p>
                        </div>
                        <img id="image-preview" src="" style="display: none; max-width: 100%; max-height: 100%; border-radius: 5px; object-fit: contain;">
                    </label>
                    <input type="file" id="validId" name="validId" accept="image/*" required style="display: none;">
                </div>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
         <div class="signup-bottom-section">
         <button type="submit" class="submit-btn">Sign Up</button>
          <p class="signup-text">
                Already have an account? <a href="{{ route('login') }}" class="signup-link">Login</a>
         </div>
        
      </form>
    </div>
  </div>

  <script>
    // Toggle password visibility
    // document.getElementById('togglePassword').addEventListener('click', function() {
    //   const passwordInput = document.getElementById('password');
    //   const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    //   passwordInput.setAttribute('type', type);
    // });

    // File upload drag and drop
    const fileInput = document.getElementById('validId');
    const uploadLabel = document.getElementById('drop-zone');
    const placeholder = document.getElementById('upload-placeholder');
    const preview = document.getElementById('image-preview');

    // Function to process and show the image
    function showPreview(file) {
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result; // Set image source to file data
                preview.style.display = 'block'; // Show the image
                placeholder.style.display = 'none'; // Hide the "Upload Image" text
            };

            reader.readAsDataURL(file);
        }
    }

    // 1. Handle selection via click
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            showPreview(this.files[0]);
        }
    });

    // 2. Handle Drag and Drop
    uploadLabel.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadLabel.style.borderColor = '#007bff'; // Visual feedback
    });

    uploadLabel.addEventListener('dragleave', () => {
        uploadLabel.style.borderColor = '#ccc';
    });

    uploadLabel.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadLabel.style.borderColor = '#ccc';
        
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files; // Assign file to input
            showPreview(e.dataTransfer.files[0]);
        }
    });
  </script>
</body>
</html>