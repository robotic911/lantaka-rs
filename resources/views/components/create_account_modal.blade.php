{{-- Create Account Modal (Admin only) — opened via #openCreateAccountBtn --}}

<style>
    #camOverlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.55);
        align-items: center;
        justify-content: center;
        z-index: 2000;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }
    #camOverlay.active { display: flex; }
    #camCard {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        padding: 28px 35px 24px;
        max-width: 860px;
        width: 92%;
        max-height: 92vh;
        overflow-y: auto;
        position: relative;
        animation: camSlide .3s ease-out;
    }
    @keyframes camSlide {
        from { opacity:0; transform:translateY(22px); }
        to   { opacity:1; transform:translateY(0); }
    }
    #camCard::-webkit-scrollbar { width:6px; }
    #camCard::-webkit-scrollbar-track { background:#f5f5f5; border-radius:3px; }
    #camCard::-webkit-scrollbar-thumb { background:#bbb; border-radius:3px; }
    #camClose {
        position: absolute; top:16px; right:18px;
        background:none; border:none; font-size:28px;
        color:#e74c3c; cursor:pointer; line-height:1; padding:0;
    }
    #camClose:hover { opacity:.75; }
    #camCard .cam-title {
        font-size:24px; font-weight:700; color:#333;
        margin-bottom:24px; text-align:center;
    }
    #camGlobalError {
        display:none;
        background:#f8d7da; color:#721c24;
        border:1px solid #f5c6cb; border-radius:6px;
        padding:10px 14px; font-size:13px; margin-bottom:16px;
    }
    .cam-form-grid {
        display:grid; grid-template-columns:1fr 1fr;
        gap:24px; margin-bottom:24px;
    }
    .cam-col { display:flex; flex-direction:column;  justify-content: start; }
    .cam-group { display:flex; flex-direction:column; gap:8px; }
    .cam-group label { font-size:13px; font-weight:600; color:#333; margin:0; }
    .cam-group input,
    .cam-group select {
        padding:12px 14px; border:1px solid #ddd; border-radius:6px;
        font-size:13px; color:#333; font-family:inherit;
        background:#fff; transition:border-color .3s ease;
    }
    .cam-group input::placeholder { color:#999; }
    .cam-group input:focus,
    .cam-group select:focus {
        outline:none; border-color:#2c3e8f;
        box-shadow:0 0 0 3px rgba(44,62,143,.1);
    }
    .cam-group input.cam-invalid,
    .cam-group select.cam-invalid { border-color:#e74c3c; }
    .cam-err { font-size:11.5px; color:#e74c3c; min-height:14px; }
    .cam-pw-wrap { position:relative; display:flex; align-items:center; }
    .cam-pw-wrap input { flex:1; padding-right:38px; }
    .cam-pw-eye {
        position:absolute; right:11px; background:none; border:none;
        cursor:pointer; color:#888; padding:0; line-height:1;
        display:flex; align-items:center; justify-content:center;
    }
    .cam-pw-eye:hover { color:#333; }
    .cam-info-note {
        display:flex; align-items:flex-start; gap:8px;
        background:#eef3fb; border:1px solid #c5d5ee;
        border-radius:8px; padding:10px 14px;
        font-size:12.5px; color:#1e3a5f; line-height:1.5;
    }
    #camStaffNote {
        display:none; align-items:flex-start; gap:8px;
        background:#eef3fb; border:1px solid #c5d5ee;
        border-radius:8px; padding:10px 14px;
        font-size:12.5px; color:#1e3a5f; line-height:1.5;
    }
    .cam-upload-label {
        display:flex; flex-direction:column;
        border:2px dashed #ddd; border-radius:6px;
        align-items:center; justify-content:center;
        min-height:160px; width:100%; cursor:pointer;
        text-align:center; transition:all .3s ease;
        background-color:#f5f5f5; overflow:hidden;
    }
    .cam-upload-label:hover { border-color:#2c3e8f; background-color:rgba(44,62,143,.05); }
    .cam-upload-icon { font-size:28px; margin-bottom:6px; }
    .cam-upload-text { font-size:13px; font-weight:600; color:#333; margin-bottom:3px; }
    .cam-upload-hint { font-size:12px; color:#999; }
    #camCard .cam-submit-btn {
        width:100%; padding:14px; background:#ffd700; color:#333;
        border:none; border-radius:6px; font-size:14px; font-weight:700;
        cursor:pointer; transition:all .3s ease; font-family:inherit;
    }
    #camCard .cam-submit-btn:hover:not(:disabled) {
        background:#ffcd00; transform:translateY(-2px);
        box-shadow:0 8px 16px rgba(255,215,0,.3);
    }
    #camCard .cam-submit-btn:disabled { opacity:.6; cursor:not-allowed; transform:none; }
    @media (max-width: 640px) {
        .cam-form-grid { grid-template-columns:1fr; gap:14px; }
        #camCard { padding:22px 18px 18px; }
    }
</style>

<div id="camOverlay">
    <div id="camCard">
        <button id="camClose" type="button">&times;</button>
        <h2 class="cam-title">Create Account</h2>
        <div id="camGlobalError"></div>

        <form id="camForm" novalidate>
            @csrf
            <div class="cam-form-grid">

                {{-- LEFT COLUMN --}}
                <div class="cam-col">
                    <div class="cam-group">
                        <label for="cam_firstName">First Name</label>
                        <input type="text" id="cam_firstName" name="firstName" placeholder="Enter First Name" autocomplete="off">
                        <span class="cam-err" id="err_firstName"></span>
                    </div>
                    <div class="cam-group">
                        <label for="cam_lastName">Last Name</label>
                        <input type="text" id="cam_lastName" name="lastName" placeholder="Enter Last Name" autocomplete="off">
                        <span class="cam-err" id="err_lastName"></span>
                    </div>
                    <div class="cam-group">
                        <label for="cam_username">Username</label>
                        <input type="text" id="cam_username" name="username" placeholder="Enter Username" autocomplete="off">
                        <span class="cam-err" id="err_username"></span>
                    </div>
                    <div class="cam-group">
                        <label for="cam_phone">Phone Number</label>
                        <input type="tel" id="cam_phone" name="phone" placeholder="Enter Phone Number" autocomplete="off">
                        <span class="cam-err" id="err_phone"></span>
                    </div>
                    <div class="cam-group">
                        <label for="cam_email">Email</label>
                        <input type="email" id="cam_email" name="email" placeholder="Enter Email" autocomplete="off">
                        <span class="cam-err" id="err_email"></span>
                    </div>
                    <div class="cam-group">
                        <label for="cam_affiliation">Affiliation</label>
                        <select id="cam_affiliation" name="affiliation">
                            <option value="">Enter Affiliation</option>
                            <optgroup label="Client — Internal">
                                <option value="student">Student</option>
                                <option value="faculty">Faculty</option>
                                <option value="organization">Organization</option>
                            </optgroup>
                            <optgroup label="Client — External">
                                <option value="external">External</option>
                            </optgroup>
                            <optgroup label="Employee">
                                <option value="staff">Staff / Employee</option>
                            </optgroup>
                        </select>
                        <span class="cam-err" id="err_affiliation"></span>
                    </div>
                </div>{{-- /LEFT --}}

                {{-- RIGHT COLUMN --}}
                <div class="cam-col">
                    <div class="cam-group">
                        <label for="cam_password">Password</label>
                        <div class="cam-pw-wrap">
                            <input type="password" id="cam_password" name="password" placeholder="Enter Password" autocomplete="new-password">
                            <button type="button" class="cam-pw-eye" data-target="cam_password">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        <span class="cam-err" id="err_password"></span>
                    </div>
                    <div class="cam-group">
                        <label for="cam_password_confirmation">Confirm Password</label>
                        <div class="cam-pw-wrap">
                            <input type="password" id="cam_password_confirmation" name="password_confirmation" placeholder="Confirm Password" autocomplete="new-password">
                            <button type="button" class="cam-pw-eye" data-target="cam_password_confirmation">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        <span class="cam-err" id="err_password_confirmation"></span>
                    </div>
                    <div class="cam-info-note">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px">
                            <circle cx="12" cy="12" r="10" stroke="#1e3a5f" stroke-width="1.8"/>
                            <path d="M12 8v4m0 4h.01" stroke="#1e3a5f" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        <span>Accounts created here are <strong>automatically approved</strong> and ready to use immediately.</span>
                    </div>
                    <div id="camStaffNote">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:2px">
                            <circle cx="12" cy="12" r="10" stroke="#1e3a5f" stroke-width="1.8"/>
                            <path d="M12 8v4m0 4h.01" stroke="#1e3a5f" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        <span>Selecting <strong>Staff / Employee</strong> assigns the <strong>employee role</strong>, granting access to the staff dashboard.</span>
                    </div>
                    <div class="cam-group">
                        <label style = "margin-top: 8px;" for="cam_validId">Valid ID</label>
                        <label for="cam_validId" class="cam-upload-label" id="camDropZone">
                            <div id="camUploadPlaceholder">
                                <div class="cam-upload-icon">&#11015;&#65039;</div>
                                <p class="cam-upload-text">Upload Image</p>
                                <p class="cam-upload-hint">Click or drag to upload image</p>
                            </div>
                            <img id="camIdPreview" src="" alt="ID Preview"
                                 style="display:none;max-width:100%;max-height:150px;border-radius:5px;object-fit:contain;padding:8px;">
                        </label>
                        <input type="file" id="cam_validId" name="validId" accept="image/*" style="display:none;">
                        <span class="cam-err" id="err_validId"></span>
                    </div>
                </div>{{-- /RIGHT --}}

            </div>{{-- /.cam-form-grid --}}

            <button type="submit" class="cam-submit-btn" id="camSubmitBtn">
                <span id="camSubmitLabel">Create Account</span>
            </button>
        </form>
    </div>
</div>

<script>
(function () {
    var overlay     = document.getElementById('camOverlay');
    var openBtn     = document.getElementById('openCreateAccountBtn');
    var closeBtn    = document.getElementById('camClose');
    var form        = document.getElementById('camForm');
    var submitBtn   = document.getElementById('camSubmitBtn');
    var submitLbl   = document.getElementById('camSubmitLabel');
    var globalErr   = document.getElementById('camGlobalError');
    var affSel      = document.getElementById('cam_affiliation');
    var staffNote   = document.getElementById('camStaffNote');
    var fileInput   = document.getElementById('cam_validId');
    var dropZone    = document.getElementById('camDropZone');
    var preview     = document.getElementById('camIdPreview');
    var placeholder = document.getElementById('camUploadPlaceholder');

    function openModal() {
        form.reset();
        clearErrors();
        globalErr.style.display   = 'none';
        staffNote.style.display   = 'none';
        preview.style.display     = 'none';
        preview.src               = '';
        placeholder.style.display = 'block';
        overlay.classList.add('active');
    }
    function closeModal() { overlay.classList.remove('active'); }

    if (openBtn) openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });

    affSel.addEventListener('change', function () {
        staffNote.style.display = affSel.value === 'staff' ? 'flex' : 'none';
    });

    document.querySelectorAll('.cam-pw-eye').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var inp  = document.getElementById(btn.dataset.target);
            inp.type = inp.type === 'password' ? 'text' : 'password';
        });
    });

    function showPreview(file) {
        if (!file || !file.type.startsWith('image/')) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display     = 'block';
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
    fileInput.addEventListener('change', function () {
        if (this.files.length > 0) showPreview(this.files[0]);
    });
    dropZone.addEventListener('dragover',  function (e) { e.preventDefault(); dropZone.style.borderColor = '#2c3e8f'; });
    dropZone.addEventListener('dragleave', function ()  { dropZone.style.borderColor = '#ddd'; });
    dropZone.addEventListener('drop',      function (e) {
        e.preventDefault(); dropZone.style.borderColor = '#ddd';
        if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; showPreview(e.dataTransfer.files[0]); }
    });

    function clearErrors() {
        ['firstName','lastName','username','email','phone','affiliation','password','password_confirmation','validId'].forEach(function (f) {
            var el = document.getElementById('err_' + f);
            var inp = document.querySelector('[name="' + f + '"]');
            if (el)  el.textContent = '';
            if (inp) inp.classList.remove('cam-invalid');
        });
    }
    function showErrors(errors) {
        Object.keys(errors).forEach(function (field) {
            var el  = document.getElementById('err_' + field);
            var inp = document.querySelector('[name="' + field + '"]');
            if (el)  el.textContent = errors[field][0];
            if (inp) inp.classList.add('cam-invalid');
        });
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();
        globalErr.style.display = 'none';
        submitBtn.disabled      = true;
        submitLbl.textContent   = 'Creating\u2026';
        try {
            var res  = await fetch('{{ route("employee.accounts.create") }}', {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value, 'Accept': 'application/json' },
                body: new FormData(form),
            });
            var json = await res.json();
            if (res.ok && json.success) {
                closeModal();
                var toast = document.getElementById('emailToaster');
                var icon  = document.getElementById('toasterIcon');
                var text  = document.getElementById('toasterText');
                if (toast) {
                    toast.classList.remove('show','state-sent','state-error');
                    icon.innerHTML   = '&#10003;';
                    text.textContent = json.message;
                    toast.classList.add('show','state-sent');
                    setTimeout(function () { toast.classList.remove('show'); }, 4000);
                }
                setTimeout(function () { window.location.reload(); }, 800);
            } else if (res.status === 422 && json.errors) {
                showErrors(json.errors);
            } else {
                globalErr.textContent   = json.message || 'Something went wrong. Please try again.';
                globalErr.style.display = 'block';
            }
        } catch (err) {
            globalErr.textContent   = 'Network error \u2014 please check your connection.';
            globalErr.style.display = 'block';
        } finally {
            submitBtn.disabled    = false;
            submitLbl.textContent = 'Create Account';
        }
    });
})();
</script>
