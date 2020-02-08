@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <h1>{{ trans('home.dashboard') }}</h1>
            <div class="panel panel-2d">
                <div class="panel-heading">{{ trans('home.welcome_title', ['name' => $user->first_name ]) }}</div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="panel-heading  panel-heading-{{ $unanswered_panel['state'] }}">{{ trans('home.unanswered_heading') }}</div>
                            <div class="panel-element panel-element-background-icon panel-element-{{ $unanswered_panel['state'] }}">
                                <div class="panel-element-body">
                                    @if(0 === $unanswered_panel['count']['total'])
                                        <div class="panel-element-main panel-element-main-number ">&nbsp;</div>
                                        <p>{{ trans('home.unanswered_body_success') }}</p>
                                        <a href="{{ route('dates.index', ['view_type' => 'list']) }}">{{ trans('home.to_dates') }}</a>
                                    @else
                                        <div class="panel-element-main panel-element-main-number ">{{ $unanswered_panel['count']['total'] }}</div>
                                        @if(0 === $unanswered_panel['count']['unanswered']) {{-- Only 'Maybe's --}}
                                            <p>{{ trans('home.unanswered_body_maybe', $unanswered_panel['count']) }}</p>
                                            <a href="{{ route('dates.index', ['view_type' => 'list', 'hideByType' => ['birthday'], 'hideByStatus' => invert_date_statuses(['unanswered', 'maybe-going'])]) }}">{{ trans('home.show') }}</a><br>
                                            <a href="{{ route('dates.index', ['view_type' => 'list', 'showAll' => 'true']) }}">{{ trans('home.to_dates') }}</a>
                                        @elseif(0 === $unanswered_panel['count']['maybe']) {{-- Only 'Unanswered's --}}
                                            <p>{{ trans('home.unanswered_body_unanswered', $unanswered_panel['count']) }}</p>
                                            <a href="{{ route('dates.index', ['view_type' => 'list', 'hideByType' => ['birthday'], 'hideByStatus' => invert_date_statuses(['unanswered', 'maybe-going'])]) }}">{{ trans('home.show') }}</a><br>
                                            <a href="{{ route('dates.index', ['view_type' => 'list', 'showAll' => 'true']) }}">{{ trans('home.to_dates') }}</a>
                                        @else
                                            <p>{{ trans('home.unanswered_body', $unanswered_panel['count']) }}</p>
                                            <a href="{{ route('dates.index', ['view_type' => 'list', 'hideByType' => ['birthday'], 'hideByStatus' => invert_date_statuses(['unanswered'])]) }}">{{ trans('home.to_unanswered') }}</a><br>
                                            <a href="{{ route('dates.index', ['view_type' => 'list', 'hideByType' => ['birthday'], 'hideByStatus' => invert_date_statuses(['unanswered', 'maybe-going'])]) }}">{{ trans('home.to_unanswered_maybe') }}</a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3" title="{{ trans('home.next_gigs_addendum') }}">
                            <div class="panel-heading  panel-heading-{{ $next_gigs_panel['state'] }}">{{ trans('home.next_gigs_heading') }}</div>
                            <div class="panel-element panel-element-{{ $next_gigs_panel['state'] }}">
                                <div class="panel-element-body">
                                    <div class="panel-element-main panel-element-main-content ">{{ isset($next_gigs_panel['data'][0]) ? $next_gigs_panel['data'][0]->getStart()->diffForHumans() : '' }}</div>
                                    {{ trans('home.next_gigs_body') }}
                                    <ul>
                                        @foreach($next_gigs_panel['data'] as $gig)
                                            <li><strong>{{ $gig->getStart()->formatLocalized('%a., %d. %b.') }}</strong>
                                                @if(false === $gig->isAllDay())
                                                    {{ trans('home.at_time') }} {{ $gig->getStart()->formatLocalized('%H:%M') }}:
                                                @endif
                                                {{ str_shorten($gig->title, 10, '...')}}
                                                @if(true === $gig->hasPlace())
                                                    <br>
                                                    <a href="{{'https://www.google.com/maps/search/'}}@urlescape($gig->place)/" style="padding:0;" title="{{ trans('date.address_search') }}" rel="noopener noreferrer"  target="_blank">({{ str_shorten($gig->place, 10, '...') }}  <i class="far fa-map"></i>)</a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ route('dates.index', ['view_type' => 'list', 'hideByType' => invert_date_types(['gig'])]) }}">{{ trans('home.to_gigs') }}</a>
                                </div>
                            </div>
                        </div><div class="clearfix visible-sm-block"></div>
                        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="panel-heading  panel-heading-{{ $next_rehearsals_panel['state'] }}">{{ trans('home.next_rehearsals_heading') }}</div>
                            <div class="panel-element panel-element-{{ $next_rehearsals_panel['state'] }}">
                                <div class="panel-element-body">{{-- TODO: change something if next rehearsal is all_day --}}
                                    <div class="panel-element-main panel-element-main-content ">{{ isset($next_rehearsals_panel['data'][0]) ? $next_rehearsals_panel['data'][0]->getStart()->diffForHumans() : '' }}</div>
                                    {{ trans('home.next_rehearsals_body') }}
                                    <ul>
                                        @foreach($next_rehearsals_panel['data'] as $rehearsal)
                                            <li><strong>{{ $rehearsal->getStart()->formatLocalized('%a., %d. %b.') }}</strong>
                                                @if(false === $rehearsal->isAllDay())
                                                    {{ trans('home.at_time') }} {{ $rehearsal->getStart()->formatLocalized('%H:%M') }}:
                                                @endif
                                                {{ str_shorten($rehearsal->title, 10, '...') }}
                                                @if(true === $rehearsal->hasPlace())
                                                    <br>
                                                    <a href="{{'https://www.google.com/maps/search/'}}@urlescape($rehearsal->place)/" style="padding:0;" title="{{ trans('date.address_search') }}" rel="noopener noreferrer" target="_blank">({{ str_shorten($rehearsal->place, 10, '...') }}  <i class="far fa-map"></i>)</a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                <a href="{{ route('dates.index', ['view_type' => 'list', 'hideByType' => invert_date_types(['rehearsal'])]) }}">{{ trans('home.to_rehearsals') }}</a>
                                </div>
                            </div>
                        </div><div class="clearfix visible-md-block"></div>
                        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="panel-heading  panel-heading-{{ $missed_rehearsals_panel['state'] }}">{{ trans('home.missed_rehearsals_heading') }}</div>
                            <div class="panel-element panel-element-{{ $missed_rehearsals_panel['state'] }}">
                                <div class="panel-element-main panel-element-body">
                                    <div class="panel-element-main panel-element-main-number ">{{ localize_number($missed_rehearsals_panel['count']['total']) }}</div>
                                    @if(0 == $missed_rehearsals_panel['count']['total'])
                                        <p>{{ trans('home.missed_rehearsals_body_success') }}</p>
                                    @else
                                        <p>{{ trans('home.missed_rehearsals_body', array_map('localize_number', $missed_rehearsals_panel['count'])) }}</p>
                                        @if($missed_rehearsals_panel['data']['over_limit'])
                                            <p>{{ trans('home.over_limit') }}</p>
                                        @endif
                                    @endif
                                    <a href="{{ route('dates.index', ['view_type' => 'list', 'hideByType' => invert_date_types(['rehearsal'])]) }}">{{ trans('home.to_future_rehearsals') }}</a>
                                </div>
                            </div>
                        </div><div class="clearfix visible-lg-block"></div><div class="clearfix visible-sm-block"></div>
                        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="panel-heading  panel-heading-{{ $next_birthdays_panel['state'] }}">{{ trans('home.next_birthdays_heading') }}</div>
                            <div class="panel-element panel-element-{{ $next_birthdays_panel['state'] }}">
                                <div class="panel-element-body">
                                    <!--<div class="panel-element-main panel-element-main-number ">{{ $next_birthdays_panel['count'] }}</div> -->
                                        {{ trans('home.upcoming_birthdays_body', [
                                            'count' => $next_birthdays_panel['count'],
                                            'startdate' => $next_birthdays_panel['data']['consideration_dates']['start_date']->formatLocalized('%d.%m.'),
                                            'enddate' => $next_birthdays_panel['data']['consideration_dates']['end_date']->formatLocalized('%d.%m.')]) }}
                                        <ul>
                                        @foreach($next_birthdays_panel['data']['upcoming_birthdays'] as $birthday)
                                            <li>{{ trans('home.birthday_name', ['name' => $birthday->getUser()->first_name]) }}
                                                <?php $diff = $today->diffInDays($birthday->getStart(), false) ?>
                                                @if($diff === 0)
                                                    <strong>{{ trans('home.today') }}</strong>
                                                @elseif($diff === 1)
                                                    <strong>{{ trans('home.tomorrow') }}</strong>
                                                    @elseif($diff === -1)
                                                    {{ trans('home.yesterday') }}
                                                    @elseif($diff < 0)
                                                    <em>{{ trans('home.past_in_days', ['days' => abs($diff)]) }}</em>
                                                    @else
                                                    {{ trans('home.future_in_days', ['days' => abs($diff)]) }}
                                                @endif
                                            </li>
                                        @endforeach
                                        </ul>
                                    <a href="{{ route('dates.index', ['view_type' => 'list', 'hideByType' => invert_date_types(['birthday'])]) }}">{{ trans('home.to_birthdays') }}</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="panel-heading  panel-heading-info">{{ trans('home.cloudshare_heading') }}</div>
                            <div class="panel-element panel-element-info">
                                <div class="panel-element-body">
                                    <p>{{ trans('home.cloudshare_body') }}</p>
                                    <a href="{{ route('fileAccess.accessFiles', ['type' => 'users', 'id' => 1]) }}" target="_blank" rel="noopener noreferrer" class="btn btn-2d btn-clear-below">{{ trans('home.cloudshare_button1') }}</a>
                                    <br>
                                    <a href="{{ route('fileAccess.accessFiles', ['type' => 'users', 'id' => 2]) }}" target="_blank" rel="noopener noreferrer" class="btn btn-2d btn-clear-below">{{ trans('home.cloudshare_button2') }}</a>
                                    <br>
                                    <a href="{{ route('fileAccess.accessFiles', ['type' => 'users', 'id' => 3]) }}" target="_blank" rel="noopener noreferrer" class="btn btn-2d btn-success btn-clear-below">{{ trans('home.cloudshare_button3') }}</a>

                                    @if(Auth::user()->isAdmin())
                                        <p>{{ trans('home.cloudshare_admin_body') }}</p>
                                        <a href="{{ route('fileAccess.accessFiles', ['type' => 'admins', 'id' => 1]) }}" target="_blank" class="btn btn-2d btn-error">{{ trans('home.cloudshare_adminbutton') }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if($echo_needed)
                            <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3" id="echo-needed-panel">
                                <div class="panel-heading  panel-heading-error">{{ trans('home.echo_needed') }}</div>
                                <div class="panel-element">
                                    <div class="panel-element-body">
                                        <p>{{ trans('home.echo_needed_body') }}</p>
                                        <br>
                                        <p>{{ trans('home.echo_semester') }}</p>
                                        <a href="#"
                                           class="btn btn-2d btn-post" {{-- TODO: This would be much better as POST !!! --}}
                                           data-url="{{ route('users.updateSemester', $user->id) }}"
                                           data-callback-success="hideEchoNeededPanel">
                                            {{ trans('home.echo_semester_button', ['semester' => $echo_semester->label])}}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if(Auth::user()->isAdmin())
                            <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="panel-heading  panel-heading-{{ $admin_missed_rehearsals_panel['state'] }}">{{ trans('home.admin_missed_rehearsals_heading') }}</div>
                                <div class="panel-element panel-element-{{ $admin_missed_rehearsals_panel['state'] }}">
                                    <div class="panel-element-body">
                                        <div class="panel-element-main panel-element-main-number ">{{ $admin_missed_rehearsals_panel['count'] }}</div>
                                        {{ trans('home.admin_missed_rehearsals_body', ['count' => $admin_missed_rehearsals_panel['count']])}}
                                        <ul>
                                            @foreach($admin_missed_rehearsals_panel['data'] as $user)
                                                <li>{{ $user->first_name }} {{ $user->last_name }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if(Auth::user()->isAdmin())
                            <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="panel-heading  panel-heading-{{ $admin_mails_panel['state'] }}">{{ trans('home.admin_mails_heading') }}</div>
                                <div class="panel-element panel-element-{{ $admin_mails_panel['state'] }}">
                                    <div class="panel-element-body">
                                        @if($admin_mails_panel["data"] === "NO_IMAP_CONNECTION")
                                            <div class="panel-element-main">{{ trans("mailchecker.no_imap") }}</div>
                                        @else
                                            <p>{!! trans("home.admin_mails_body", ["url" => config("mailchecker.webmail")]) !!}</p>
                                                @foreach($admin_mails_panel['data'] as $key => $value)
                                                <ul><li>{{ trans("mailchecker." . $key) === "mailchecker." . $key ? $key : trans("mailchecker." . $key) }}</li></ul>
                                                    <p>{{ trans("mailchecker.mailbox_numbers", ["total" => $value["total"], "unread" => $value["unread"]]) }}
                                                        @if(null !== $value["newest_message"])
                                                            <br />
                                                            {{ trans("mailchecker.latest_message_from", ["from" => str_shorten($value["newest_message"]["from"], 10, "...")]) }}
                                                            {{ trans("mailchecker.latest_message_date", ["date" => $value["newest_message"]["date"]->format('d.m.Y')]) }}
                                                            {{ trans("mailchecker.latest_message_subject", ["subject" => str_shorten($value["newest_message"]["subject"], 10, '...')]) }}
                                                        @endif
                                                    </p>
                                                @endforeach
                                            </div>
                                        @endif
                                    <p><a href="{{ route("mailchecker.overview") }}">{{ trans("home.to_mailbox_overview") }}</a></p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js') {{-- TODO: Refactor these functions to one or more dedicated js-file(s), reducing very similar code in listAttendances and list --}}
    <script type="text/javascript">
        function hideEchoNeededPanel() {
            $("#echo-needed-panel").hide(500, function() {
                $(this).remove()
            });
        }

        $(document).ready(function () {
            // Override link functions for POST-links.
            $("a.btn-post").click(function (event) {
                event.preventDefault();

                // Get the callback which can be set if the POST returns successfully.
                var callback_success = null;

                if(undefined !== $(this).data("callback-success")) {
                    callback_success = $(this).data("callback-success");

                    if (typeof window[callback_success] === "function") {
                        callback_success = window[callback_success];
                    }

                    if (typeof callback_success !== "function") {
                        callback_success = null;
                    }
                }

                // Request the url via post, include csrf-token and comment.
                $.post($(this).data("url"), {
                    _token: "{{ csrf_token() }}"
                }, function (data) {
                    // Success?
                    if (data.success) {
                        // Notify user.
                        $.notify(data.message, "success");

                        if (null !== callback_success) {
                            callback_success();
                        }
                    } else {
                        // Warn user.
                        $.notify(data.message, "danger");
                    }
                },
                "json").fail(function(xhr, status, error) {
                    $.notify('{{ trans('date.attendance_not_saved') }}', 'danger');

                    // TODO: maybe status codes are better here? However status messages should be consistent among browsers and they are passed by jquery
                    if (error === 'Unauthorized' || error === 'No Reason Phrase') {
                        // Unauthorized (401): Session expired or user logged out in another tab
                        // No Reason Phrase (419): XSRF-Token verification failed or a problem with authorization
                        location.reload(true);
                    } else if (error === 'Unprocessable Entity') {
                        // Validation failed (422)
                        try {
                            $.each(xhr.responseJSON, function (key, value) {
                                $.each(value, function (index, message) {
                                    $.notify(message, 'danger');
                                });
                            });
                        } catch (err) {
                            // Unknwon error
                            $.notify('{{ trans('date.ajax_failed') }}' + status + ' ' + error, 'danger');
                            $.notify(err.name + ': ' + err.message, 'danger');
                        }
                    } else {
                        // Unknwon error
                        $.notify('{{ trans('date.ajax_failed') }}' + status + ' ' + error, 'danger');
                    }
                });
            });
        });
    </script>
@endsection