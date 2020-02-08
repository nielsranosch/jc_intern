@extends('layouts.app')

@section('title'){{ trans('date.index_title') }}@endsection

@section('content')
    <div class="row" id="{{ trans('date.index_title') }}">
        <div class="col-xs-12">
            <h1>{{ trans('date.index_title') }}</h1>

            <div class="row">
                <div class="col-xs-12">
                    <div class="panel panel-2d">
                        <div class="panel-heading">
                            {{ trans('date.index_title') }}

                            @if (Auth::user()->isAdmin('gig') || Auth::user()->isAdmin('rehearsal'))
                                <div class="pull-right">
                                    {!! Html::addButton(trans('date.add_date'), '#', ['dropdown-toggle'], ['data-toggle' => 'dropdown', 'aria-haspopup' => 'true', 'aria-expanded' => 'false']) !!}

                                    <ul class="dropdown-menu">
                                        @if (Auth::user()->isAdmin('rehearsal'))
                                            <li>
                                                <a href="{{ route('rehearsals.create') }}">{{ trans('nav.rehearsal_create') }}</a>
                                            </li>
                                        @endif
                                        @if (Auth::user()->isAdmin('gig'))
                                            <li>
                                                <a href="{{ route('gigs.create') }}">{{ trans('nav.gig_create') }}</a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <div class="panel-body" id="list-dates">
                            <form id="comment-form" class="modal" style="display: none;">
                                {!! Form::textInput2d('comment', null, ['placeholder' => trans('form.comment') ]) !!}
                                {!! Form::submitInput2d(trans('form.submit')) !!}
                            </form>
                            @include('date.settings_bar', [
                                'view_type'         => 'list',
                                'override_types'    => $override_types,
                                'override_statuses' => $override_statuses,
                                'override_show_all' => $override_show_all,
                                'date_types'        => $date_types,
                                'date_statuses'     => $date_statuses,
                                'view_types'        => $view_types
                            ])
                            @each('date.list.row', $dates, 'date')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js') {{-- TODO: Refactor these functions to one or more dedicated js-file(s), reducing very similar code in listAttendances and home --}}
    <script type="text/javascript">

        /**
         * Handles changes to a slider-element
         *
         * This method is called from the main.js via the "data-function" attribute on the switch for attendance.
         *
         * @param sliderElement
         */
        function changeAttendanceSlider (sliderElement) {
            if ($(sliderElement).hasClass('inactive')) return false;
            $(sliderElement).addClass('inactive');

            // Do we need to excuse the user or is she attending?
            // If the slider's checkbox is "checked" we have to excuse her.
            var currentlyAttending = $(sliderElement).find('input[type="checkbox"]').prop('checked');
            var url = '';
            var attendance = '';

            if (currentlyAttending) {
                url = $(sliderElement).data('excuse-url');
                attendance = 'no';
            } else {
                url = $(sliderElement).data('attend-url');
                attendance = 'yes';
            }
            saveAttendance(url, null, function() {
                    changeEventDisplayState(sliderElement, attendance, true);
                    $(sliderElement).removeClass('inactive');
                });
        }

        /**
         * Function calls API via POST. Calls success_callback on success.
         *
         * @param url
         * @param attendance
         * @param comment
         * @param success_callback
         */
        function saveAttendance(url, comment, success_callback) {
            var data = {_token: '{{ csrf_token() }}'};

            if (undefined !== comment && null !== comment) {
                data['comment'] = comment;
            }

            // Request the url via post, include csrf-token and comment.
            $.post(url, data, function (reply) {
                // Success?
                if (reply.success) {
                    // Notify user.
                    $.notify(reply.message, 'success');
                    if (typeof success_callback === "function") {
                        success_callback();
                    }
                } else {
                    // Warn user.
                    $.notify(reply.message, 'danger');
                }
            },
            'json').fail(function(xhr, status, error) {
                if (undefined !== comment && null !== comment) {
                    $.notify('{{ trans('date.attendance_not_saved') }}', 'danger');
                } else {
                    $.notify('{{ trans('date.comment_not_saved') }}', 'danger');
                }

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
                    } catch(err) {
                        // Unknwon error
                        $.notify('{{ trans('date.ajax_failed') }}' + status + ' ' + error, 'danger');
                        $.notify(err.name + ': ' + err.message, 'danger');
                    }
                } else {
                    // Unknwon error
                    $.notify('{{ trans('date.ajax_failed') }}' + status + ' ' + error, 'danger');
                }
            });
        }

        /**
         * Do all the optical changes to the button(s) and the event the button was in
         *
         * @param button
         * @param attendance
         * @param slider
         */
        function changeEventDisplayState(button, attendance, slider) {
            if (true === slider) {
                $(button).find('input[type="checkbox"]').prop('checked', 'yes' === attendance);
            } else {
                $(button).siblings().addClass('btn-unpressed');
                $(button).siblings().removeClass('btn-pressed');

                $(button).addClass('btn-pressed');
                $(button).removeClass('btn-unpressed');
            }

            var eventNode = $(button).parents('.event');
            var containerNode = $(eventNode).parent('.list-item');
            var filters = $(containerNode).data('filters');
            var titleNote = $(eventNode).find('.title .not-going-note');

            switch(attendance) {
                case 'yes':
                    filters = $(filters).not(['not-going', 'maybe-going', 'unanswered']);
                    filters.push('going');
                    $(eventNode).addClass('event-going');
                    $(eventNode).removeClass('event-not-going');
                    $(eventNode).removeClass('event-maybe-going');
                    $(eventNode).removeClass('event-unanswered');
                    $(titleNote).hide();
                    break;
                case 'maybe':
                    filters = $(filters).not(['not-going', 'going', 'unanswered']);
                    filters.push('maybe');
                    $(eventNode).addClass('event-maybe-going');
                    $(eventNode).removeClass('event-not-going');
                    $(eventNode).removeClass('event-going');
                    $(eventNode).removeClass('event-unanswered');
                    $(titleNote).hide();
                    break;
                case 'no':
                    filters = $(filters).not(['going', 'maybe-going', 'unanswered']);
                    filters.push('not-going');
                    $(eventNode).addClass('event-not-going');
                    $(eventNode).removeClass('event-going');
                    $(eventNode).removeClass('event-maybe-going');
                    $(eventNode).removeClass('event-unanswered');
                    $(titleNote).show();
                    break;
            }

            $(containerNode).data('filters', filters.toArray());
        }


        $(document).ready(function () {
            var comment_form = $('#comment-form');

            // On submission of the form in the modal.
            comment_form.submit(function (event) {
                event.preventDefault();

                // TODO: Save comment to your_comment section and data-current-comment of appropriate buttons
                saveAttendance($(this).attr('action'),
                    $('#comment').val()
                );

                $.modal.close();
            });

            comment_form.on($.modal.OPEN, function () {
                $('#comment').focus();
            }).on($.modal.CLOSE, function () {
                $('#comment').val('');
            });

            // On click of a gig attendance button.
            $('.button-set-attendances > a.btn').click(function (event) {
                event.preventDefault();
                var button = this;

                saveAttendance(
                    $(this).data('url'),
                    null,
                    function() {
                        changeEventDisplayState(button, $(button).data('attendance'), false);
                    }
                );

                if ($(this).data('attendance') === 'maybe') {
                    var current_comment = $.trim($(this).data('current-comment'));
                    if (current_comment.length !== 0) {
                        $('#comment').val(current_comment);
                    }

                    // Display modal to put in an excuse. Because I like to be an a-hole, this modal cannot be closed without submitting. One can, however submit an empty string, because I'm not 100% a dick.
                    comment_form.attr('action', $(this).data('comment-url'))
                        .modal({'escapeClose': false,
                            'clickClose': false,
                            'showClose': false});
                }
            });

            $('.comment-btn-container > a.comment-btn').click(function (event) {
                event.preventDefault();
                var current_comment = $.trim($(this).data('current-comment'));
                if (current_comment.length !== 0) {
                    $('#comment').val(current_comment);
                }
                comment_form.attr('action', $(this).data('comment-url')).modal();


            });
        });
    </script>
@endsection
