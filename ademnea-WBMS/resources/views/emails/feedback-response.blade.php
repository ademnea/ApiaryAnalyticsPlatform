<x-mail::message>
# Response to your feedback

Dear {{ $feedback->full_name }},

Our team has reviewed your feedback **"{{ $feedback->subject }}"** and would like to respond:

> {{ $feedback->admin_response }}

Thank you for getting in touch with us.

{{ config('app.name') }}
</x-mail::message>
