<x-mail::message>
# {{ $alertSubject }}

{!! nl2br(e($body)) !!}

{{ config('app.name') }} — IoT Condition Monitoring
</x-mail::message>
