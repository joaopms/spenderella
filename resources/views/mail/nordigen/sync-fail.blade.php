<x-mail::message>
# Error syncing transactions

@if($exception)
**{{ $exception->getMessage() }}**

@foreach(explode("\n", $exception->getTraceAsString()) as $line)
    {{ $line }}
@endforeach
@else
No error message was captured
@endif
</x-mail::message>