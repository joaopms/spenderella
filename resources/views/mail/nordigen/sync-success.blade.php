<x-mail::message>
# Transaction sync report

Here's what happened on your bank accounts today.

A total of <b>{{ $numTransactions }} transaction(s)</b> were registered.

@if(count($fails) > 0)
---

## ERRORS

| Account | Error |
| - | - |
@foreach($fails as $result)
| {{ $result->getAccount()->name }} | {{ $result->exception_message }} |
@endforeach

---
@endif

@if(count($successes) > 0)
---

## NEW TRANSACTIONS

| Account | Date | Description | Amount |
| - | - | - | -: |
@foreach($successes as $result)
@foreach($result->getTransactions() as $transaction)
| {{ $result->getAccount()->name }} | {{ $transaction->value_date->format("Y-m-d") }} | {{ $transaction->description }} | {{ $transaction->humanAmount }} |
@endforeach
@endforeach

---
@endif

</x-mail::message>
