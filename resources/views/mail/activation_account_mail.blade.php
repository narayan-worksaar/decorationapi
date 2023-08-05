@component('mail::message')
<style>
    .center {
        text-align: center;
    }
    .image-container {
        display: block;
        margin: 0 auto;
    }
</style>
<b>Dear {{ $mailData['name'] }} / {{ $mailData['company_name'] }}</b>
<div class="center">
    <b>Congratulation</b><br><br>
    <b>Your account has been successfully activated!</b>

<center>You can log in to your account and book your services.</center>

<br>
<div class="image-container">
    <img src="{{ asset('public/storage/images/righttick.jpeg') }}" alt="Right Tick" style="width: 50px; height: 50px;">
</div>

Thanks,<br>
{{ config('app.name') }}
</div>
@endcomponent
