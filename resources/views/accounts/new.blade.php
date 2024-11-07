
<h1>Add new account</h1>

@foreach($institutionsByCountry as $country => $institutions)
<details>
    <summary>
        <h2 style="display: inline-block">{{ $country }}</h2>
    </summary>

    <div>
    @foreach($institutions as $institution)
        <p>
            <a href="{{ route("nordigen.new", ["institutionId" => $institution["id"]]) }}">
                <img
                    loading="lazy"
                    src="{{ $institution["logo"] }}"
                    alt="{{ $institution["name"] }} logo"
                    height="32px"
                    width="32px"
                />

                {{ $institution["name"] }} ({{ $institution["bic"] }})
            </a>
        </p>
    @endforeach
    </div>
</details>
@endforeach
