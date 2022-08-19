@extends('layouts.app')

@section('content')
    <div class="card">
        <form action="" method="get">
            <input type="number" name="distance" value="{{ request('distance') }}" placeholder="Distanza in Km" />
            <input type="text" class="form-control" name="city" id="city" placeholder="Indirizzo da cercare" autocomplete="off" value="{{ request('city') }}">
            <input type="submit" value="Cerca" />
            <a href="{{ route('search') }}">Reset filtri</a>
            <a href="{{ route('searchAffitto') }}">Vai agli affitti</a>
        </form>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="d-flex no-block align-items-center mb-4">
                <h4 class="card-title">@lang("Apartments")</h4>
                <div class="ms-auto">
                  <div class="btn-group">
                  </div>
                 </div>
            </div>
            <table class="table table-responsive-sm table-striped table-bordered datatables" id="mainTable">
                <thead>
                    <tr>
                        <td>@lang('Name')</td>
                        <td>@lang('Price')</td>
                        <td>@lang('Square mt')</td>
                        <td>@lang('City')</td>
                        <td>@lang('Floor')</td>
                        <td>@lang('Garden')</td>
                        <td>@lang('Garage')</td>
                        <td>@lang('Condo fees')</td>
                        <td>@lang('Updated at')</td>
                        @if(request()->has('city'))
                        <td>Distance</td>
                        @endif
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="d-flex no-block align-items-center mb-4">
                <h4 class="card-title">@lang("Out of AVG") € {{ number_format($avgPrice, 2, ',', '.') }}</h4>
                <div class="ms-auto">
                  <div class="btn-group">
                  </div>
                 </div>
            </div>
            <table class="table table-responsive-sm table-striped table-bordered datatables" id="avgTable">
                <thead>
                    <tr>
                        <td>@lang('Name')</td>
                        <td>@lang('Price')</td>
                        <td>@lang('Square mt')</td>
                        <td>@lang('City')</td>
                        <td>@lang('Floor')</td>
                        <td>@lang('Garden')</td>
                        <td>@lang('Garage')</td>
                        <td>@lang('Condo fees')</td>
                        <td>@lang('Updated at')</td>
                        @if(request()->has('city'))
                        <td>Distance</td>
                        @endif
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="d-flex no-block align-items-center mb-4">
                <h4 class="card-title">@lang("Stats")</h4>
                <div class="ms-auto">
                  <div class="btn-group">
                  </div>
                 </div>
            </div>
            <table class="table table-responsive-sm table-striped table-bordered datatables" id="statsTable">
                <thead>
                    <tr>
                        <td>@lang('City')</td>
                        <td>@lang('Prezzo medio')</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\City::all() as $city)
                        @php
                            $avg = \App\Models\Apartment::where('city_id', $city->id)->where('contract', 'vendita')->avg('price') / \App\Models\Apartment::where('city_id', $city->id)->where('contract', 'vendita')->avg('square_mt');
                        @endphp
                        <tr>
                            <td>{{ $city->name }}</td>
                            <td data-sort="{{ $avg }}">€/mq {{ number_format($avg, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('after_scripts')
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places"></script>
    <script>
        google.maps.event.addDomListener(window, 'load', initialize);
        @php
            $url = route("search", ['distance' => request()->distance, 'city' => request()->city]);
            $urlAvg = route("searchAvg", ['distance' => request()->distance, 'city' => request()->city]);
        @endphp
        
        function initialize() {
            var table = $('#mainTable').DataTable({
                serverSide: true,
                processing: true,
                ajax: '{!! $url !!}',
                columns: [
                    {data: 'ad_headline'},
                    {data: 'price'},
                    {data: 'square_mt'},
                    {data: 'city_name'},
                    {data: 'floor'},
                    {data: 'garden'},
                    {data: 'garage'},
                    {data: 'condo_fees'},
                    {data: 'updated_at'},
                    @if(request()->has('city'))
                    {data: 'distance'},
                    @endif
                ],
                columnDefs: [
                    //{targets: [-1], orderable: false}
                ]
            });

            var avgTable = $('#avgTable').DataTable({
                serverSide: true,
                processing: true,
                ajax: '{!! $urlAvg !!}',
                columns: [
                    {data: 'ad_headline'},
                    {data: 'price'},
                    {data: 'square_mt'},
                    {data: 'city_name'},
                    {data: 'floor'},
                    {data: 'garden'},
                    {data: 'garage'},
                    {data: 'condo_fees'},
                    {data: 'updated_at'},
                    @if(request()->has('city'))
                    {data: 'distance'},
                    @endif
                ],
                columnDefs: [
                    //{targets: [-1], orderable: false}
                ]
            });

            var statsTable = $('#statsTable').DataTable({
                order: [[1, 'asc']],
            });

            var input = document.getElementById('city');
            var autocomplete = new google.maps.places.Autocomplete(input,  {
                types: ['geocode']
            });
            autocomplete.setComponentRestrictions({'country': ['it']});

            autocomplete.addListener('place_changed', function () {
                var place = autocomplete.getPlace();

                var relevantComponents = place.address_components.filter((component) => {
                    return component.types.includes("administrative_area_level_2")
                })

                if (relevantComponents.length) {
                    var location = relevantComponents[0]

                    var addressData = {
                        address: place.formatted_address,
                        location: location.short_name,
                        lat: place.geometry['location'].lat(),
                        lng: place.geometry['location'].lng(),
                    }
                }
            });
        }
    </script>
@endpush