<x-mail::message>
# Transaction sync report

Here's what happened on your bank accounts today.

A total of <b>{{ $results->countTransactions() }} transaction(s)</b> were fetched.

@if(sizeof($fails) > 0)
---

## ERRORS

| Account | Error |
| - | - |
@foreach($fails as $result)
| {{ $result->account->name }} | {{ $result->exception->getMessage() }} |
@endforeach

---
@endif

@if(sizeof($successes) > 0)
---

## NEW TRANSACTIONS

| Account | Date | Description | Amount |
| - | - | - | -: |
@foreach($successes as $result)
@foreach($result->transactions as $transaction)
| {{ $result->account->name }} | {{ $transaction->value_date->format("Y-m-d") }} | {{ $transaction->description }} | {{ $transaction->humanAmount }} |
@endforeach
@endforeach

---
@endif

</x-mail::message>
