<div class="row">
    <div class="col-md-12">
        {!! \Form::rText('contact_name', trans('Name'), null, true, ['placeholder' => trans('Name')]) !!}
    </div>
    <div class="col-md-6">
        <input type="hidden" name="required[]" value="customer_account_number">
        {!! \Form::rText('customer_account_number', trans('Customer Code'), null, true, [
            'placeholder' => trans('Enter valid customer code...'),
        ]) !!}
    </div>
    <div class="col-md-6">
        {!! \Form::rEmail('contact_email', trans('Email'), null, true, ['placeholder' => trans('Email Address')]) !!}
        <small class="text-muted small">
            ({{ trans('Your E-Mail Address will serve as your User ID when you Login') }})
        </small>
    </div>
    <div class="col-md-6">
        {!! \Form::rTel('contact_phone_number', trans('Phone'), null, true, [
            'placeholder' => trans('Enter Contact Phone Number'),
            'tabindex' => 6,
        ]) !!}
    </div>
    <div class="col-md-6">
        {!! \Form::rPassword('contact_password', trans('Password'), true, [
            'placeholder' => trans('Enter Password'),
            'minlength' => $minPasswordLength(),
            'maxlength' => '255',
            'tabindex' => 8,
        ]) !!}
    </div>
    <div class="col-md-6">
        {!! \Form::rPassword('contact_password_confirmation', trans('Retype Password'), true, [
            'placeholder' => trans('Enter Password'),
            'minlength' => $minPasswordLength(),
            'maxlength' => '255',
            'tabindex' => 9,
        ]) !!}
    </div>
</div>
<div class="row">
    {{ $slot }}
</div>
<div class="row">
    <div class="col-md-6">
        @if ($newsletterSubscription)
            {!! \Form::rCheckbox('contact_newsletter', '', [
                'yes' => config('app.name') . ' ' . trans('newsletter subscription.'),
            ]) !!}
        @endif
    </div>
    <div class="col-md-6">
        @if ($acceptTermsConfirmation)
            <input type="hidden" name="required[]" value="contact_accept_term" />
            {!! \Form::rCheckbox(
                'contact_accept_term',
                '',
                ['yes' => 'I accept the ' . config('app.name') . ' ' . trans('Terms and Conditions.')],
                [],
                true,
            ) !!}
        @endif
    </div>
    @if ($captchaVerification)
        <div class="col-md-12">
            <x-captcha :display="$captchaVerification" id="captcha-container-account" field-name="contact_captcha"
                :reload-captcha="$active" />
        </div>
    @endif
</div>
<div class="d-flex justify-content-md-end justify-content-center">
    <button type="submit" class="btn btn-{{ $submitButtonColor }}">
        {{ $submitButtonLabel }}
    </button>
</div>
