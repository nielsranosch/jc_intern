@extends('layouts.app')

@section('title'){{ trans('user.index_title') }}@endsection

@section('content')
    <div class="row" id="{{ trans('nav.users') }}">
        <div class="col-xs-12">
            <h1>{{ trans('user.index_title') }}</h1>

            <div class="row">
                <div class="col-xs-12 col-md-6">
                    {!! Form::textInput2d('search') !!}
                </div>
                @if(Auth::user()->isAdmin())
                <div class="col-xs-12 col-md-6">
                    <br>
                    <a href="{{ route('users.create') }}" title="{{ trans('user.add_user') }}" class="btn btn-2d">
                        <i class="fa fa-plus"></i>&nbsp;{{ trans('user.add_user') }}
                    </a>
                </div>
                @endif
            </div>
            <br>

            {{-- Output role 'Musikalische Leitung' first. --}}
            <div class="row" id="{{  trans('nav.musical_leader') }}">
                <div class="col-xs-12">
                    <div class="panel panel-2d">
                        <div class="panel-heading">{{ trans('nav.musical_leader') }}</div>

                        <div class="panel-body">
                            <div class="col-xs-12">
                                <div class="panel panel-2d">
                                    <div class="panel-heading">&nbsp;
                                    </div>
                                    @include('user.table', ['users' => $musical_leader])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @foreach($voices as $voice)
                <div class="row" id="{{ trans('nav.users') }}-{{ $voice->name }}">
                    <div class="col-xs-12">
                        <div class="panel panel-2d">
                            <div class="panel-heading">
                                {{ $voice->name }}
                                @if(Auth::user()->isAdmin())
                                    {!! Html::addButton(trans('user.add_user'), route('users.create', ['voice' => $voice->id])) !!}
                                @endif
                            </div>

                            <div class="panel-body">
                                @foreach($voice->children as $sub_voice)
                                    <div class="col-xs-12">
                                        <div class="panel panel-2d">
                                            <div class="panel-heading">
                                                {{ $sub_voice->name }}
                                                @if(Auth::user()->isAdmin())
                                                    {!! Html::addButton(trans('user.add_user'), route('users.create', ['voice' => $sub_voice->id])) !!}
                                                @endif
                                            </div>

                                            @include('user.table', ['users' => \App\Models\User::getUsersOfVoice($sub_voice->id, false, true)])
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @if(Auth::user()->isAdmin())
    <div class="row">
        <div class="col-xs-12">
            <div class="row" id="{{ str_replace(' ', '-', trans('user.alumni')) }}">
                <div class="col-xs-12">
                    <div class="panel panel-2d panel-2d-grey">
                        <div class="panel-heading">{{ trans('user.alumni') }}</div>
                        <div class="panel-body">
                            @foreach($old_users->groupBy('voice_id') as $users_in_voice)
                                <div class="col-xs-12">
                                    <div class="panel panel-2d">
                                        <div class="panel-heading">
                                            {{ $users_in_voice->first()->voice->name }}
                                        </div>

                                        @include('user.table', ['users' => $users_in_voice])
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@section('navlist')
    <ul class="nav">
        <li>
            <a href="#{{ trans('nav.users') }}">{{ trans('nav.users') }}</a>
            <ul class="nav">
                <li><a href="#{{ trans('nav.musical_leader') }}">{{ trans('nav.musical_leader') }}</a></li>

                @foreach($voices as $voice)
                    <li><a href="#{{ trans('nav.users') }}-{{ $voice->name }}">{{ $voice->name }}</a></li>
                @endforeach
            </ul>
        </li>
        @if(Auth::user()->isAdmin())
        <li><a href="#{{ str_replace(' ', '-', trans('user.alumni')) }}">{{ trans('user.alumni') }}</a></li>
        @endif
    </ul>
@endsection

@section('js')
    <script type="text/javascript">
        function filterRowsByName(rows, name) {
            if (name.length === 0 || !(name.trim())) {
                $(rows).show().parents('table, .panel').show();
                return;
            }

            var names = name.toLowerCase().split(' ');
            var firstname = '';
            var lastname = '';
            var match = false;

            // Hide all rows.
            $(rows).hide().parents('table, .panel').hide();

            // Get all rows which first- and lastnames match all! of the search terms and show them.
            $(rows).filter(function () {
                firstname = $(this).find('.first-name').text().toLowerCase();
                lastname = $(this).find('.last-name').text().toLowerCase();

                match = false;
                $(names).each(function (index, value) {
                    match = (firstname.indexOf(value) >= 0) || (lastname.indexOf(value) >= 0);
                    if (!match) return false;
                });

                return match;
            }).show().parents('table, .panel').show();
        }

        $(document).ready(function () {
            // Make the search react to inputs (with timeout delay).
            $('#search').bind('input propertychange', function (event) {
                // If it's the propertychange event, make sure it's the value that changed.
                if (window.event && event.type == 'propertychange' && event.propertyName != 'value')
                    return;

                var input = $(this).val();

                // Clear any previously set timer before setting a fresh one
                window.clearTimeout($(this).data('timeout'));
                $(this).data('timeout', setTimeout(function () {
                    filterRowsByName($('.user-row'), input);
                }, 500));
            });
        });
    </script>
@endsection