@extends('layouts.app')

@section('title'){{ trans('date.gig_show_title') }}@endsection

@section('additional_js_files')
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDj7PNEdex5u2osEGzmZNlbz0p2bLmeVxU&libraries=places"></script>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <h1>{{ trans('date.gig_show_title') }}</h1>

            <div class="row">
                <div class="col-xs-12">
                    <div class="panel panel-2d">
                        <div class="panel-heading">{{ trans('date.gig_show_title') }}</div>

                        @include('date.gig.form', ['options' => ['url' => route('gigs.update', ['gig' => $gig->id]), 'method' => 'PUT'], 'gig' => $gig])
                        @include('date.deleteform', ['options' => ['url' => route('gigs.destroy', ['gig' => $gig->id]), 'method' => 'DELETE'], 'event' => $gig])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script type="text/javascript">
        autocomplete = new google.maps.places.Autocomplete(document.getElementsByName('place').item(0), {
            types: ['address']
        });
    </script>
@endsection