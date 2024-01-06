@component('mail::message')
# Delete Account Request

Mr {{ $mailData['name'] }} send request to delete account.

@component('mail::table')
| User Id | Email         | Mobile No  |
| ------- |:-------------:| -----------:|
| <div style="text-align: center;">{{ $mailData['user_id'] }}</div> | <div style="text-align: center;">{{ $mailData['email'] }}</div> | <div style="text-align: center;">{{ $mailData['mobile'] }}</div> |
@endcomponent


Thanks,<br>
{{ config('app.name') }} Team
@endcomponent
