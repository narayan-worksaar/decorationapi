@component('mail::message')
# Sign up mail


@component('mail::panel')
Company / Name: {{ $mailData['name'] }}{{ $mailData['company_name'] }} <br>
Email: {{ $mailData['email'] }}
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
