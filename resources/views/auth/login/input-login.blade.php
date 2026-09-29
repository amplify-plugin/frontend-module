<div class="form-group">
    <div class="input-group">
        <label class="sr-only" for="login-id">Login ID</label>
        <span class="input-group-addon">
            <i class="icon-user"></i>
        </span>
        <input id="login-id"
               type="text"
               name="login_id"
               class="form-control @error('login_id') is-invalid @enderror"
               placeholder="{{ __('Login ID') }}"
               autocomplete="username"
               tabindex="1"
               maxlength="255"
               required>
    </div>
    <span id="login-id-error" class="invalid-feedback d-block">
        @error('login_id') {{ $message }} @enderror
    </span>
</div>
