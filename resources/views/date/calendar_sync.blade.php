@extends('layouts.app')

@section('content')

    <h1>{{ trans('date.calendar_sync_header') }}</h1>
    <div id="calendar_sync_preamble">{{ trans('date.calendar_sync_preamble') }}</div>

    {!! Form::open(['id' => 'calendar_sync_form', 'url' => '#']) !!}
    @foreach($date_types as $type)
        {!! Form::checkboxInput2d($type, true, ['class' => 'ical_link_generator']) !!}
    @endforeach

    <div id="calendar_sync_intermezzo">{{ trans('date.calendar_sync_intermezzo') }}</div>

    {!! Form::radioInput2d('method', trans('date.calendar_sync_manual'), 'manual', true, ['class' => 'ical_link_generator']) !!}
    {!! Form::radioInput2d('method', trans('date.calendar_sync_google'), 'google', false, ['class' => 'ical_link_generator']) !!}
    {!! Form::radioInput2d('method', trans('date.calendar_sync_webcal'), 'webcal', false, ['class' => 'ical_link_generator']) !!}

    <!-- {!! Form::submitInput2d(trans('date.calendar_sync_submit'), ['id' => 'calendar_sync_submit']) !!} -->

    {!! Form::close() !!}

    <div id="calendar_sync_end">
        <p id="calendar_sync_text">{{ trans('date.subscribe_manually') }}</p>
        <a id="calendar_sync_link" href="{{ generate_calendar_url(Auth::user()) }}">{{ generate_calendar_url(Auth::user()) }}</a>
        <p id="calendar_sync_conclusion">{{ trans('date.calendar_sync_conclusion') }}</p>
    </div>

@endsection

@section('js')
    <script type="text/javascript">
        $(document).ready(function () {
            var methods = {
                'manual': "{{ trans('date.subscribe_manually') }}",
                'google': "{{ trans('date.subscribe_google') }}",
                'webcal': "{{ trans('date.subscribe_webcal') }}"
            };

            <?php $temp_link = \Config::get('app.domain') . route('dates.renderIcal', [
                    'user_id' => Auth::getUser()->id,
                    'key' => Auth::user()->pseudo_password,
                    'req_key' => str_random(3),
                ], false); ?>

            var links = {
                'EMPTY': {
                    'manual': '{!! generate_calendar_url(Auth::user()) !!}',
                    'google': '{!! 'https://calendar.google.com/calendar/r/settings/addbyurl?cpub=false&cid=' . urlencode(generate_calendar_url(Auth::user(), 'http://', null)) !!}',
                    'webcal': '{!! generate_calendar_url(Auth::user(), 'webcal://') !!}'
                }
            };

            var temp_name = [];
            @foreach(power_set($date_types) as $subset)
                temp_name = {!! json_encode($subset, JSON_UNESCAPED_SLASHES ) !!};
                temp_name = temp_name.sort().join('-'); {{-- Let the browser sort because some browsers are weird and we need it to be consistent    --}}

                links[temp_name] = {
                    'manual': '{!! generate_calendar_url(Auth::user(), null, $subset) !!}',
                    'google': '{!! 'https://calendar.google.com/calendar/r/settings/addbyurl?cpub=false&cid=' . urlencode(generate_calendar_url(Auth::user(), 'http://', $subset)) !!}',
                    'webcal': '{!! generate_calendar_url(Auth::user(), 'webcal://', $subset) !!}'
                };
            @endforeach

            var text_element = $('#calendar_sync_text');
            var link_element = $('#calendar_sync_link');

            function generate_content() {
                text_element.text('loading');

                var method = $('input[name=method]:checked', '#calendar_sync_form').val();
                var date_types = [];
                $('input[type=checkbox]:checked', '#calendar_sync_form').each(function() {
                    date_types.push($(this).attr("name"));
                });
                date_types = date_types.sort().join('-');

                if ("" === date_types) {
                    date_types = "EMPTY";
                }

                link_element.text(links[date_types][method]);
                link_element.attr('href', links[date_types][method]);
                text_element.text(methods[method]);
            }


            $('.ical_link_generator').change(function() {
                generate_content();
            });

            $('#calendar_sync_form').submit(function() {
                generate_content();
                return false;
            });
        });
    </script>
@endsection