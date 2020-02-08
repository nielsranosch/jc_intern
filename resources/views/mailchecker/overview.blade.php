@extends('layouts.app')

@section('content')
    <div class="col-md-12">
        <h1>{{ trans('mailchecker.title') }}</h1>
        <div class="panel panel-2d">
            <div class="panel-heading">{{ $mailbox_name }}
                <div class="pull-right">
                    {!! Html::button(trans('mailchecker.to_mailbox'), config("mailchecker.webmail"), 'external-link-alt', [], ["target" => "_blank", "rel" => "noopener noreferrer"]) !!}
                </div>
            </div>
            @if($mailbox_folders === "NO_IMAP_CONNECTION")
                <p>{{ trans("mailchecker.no_imap") }}</p>
            @else
                <div class="panel-body">
                    <div class="row">
                        @foreach($mailbox_folders as $name => $value)
                            <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="panel-heading  panel-heading-info">{{ trans("mailchecker." . $name) === "mailchecker." . $name ? $name : trans("mailchecker." . $name) }}</div>
                                <div class="panel-element panel-element-background-icon panel-element-info">
                                    <div class="panel-element-body">
                                        <p>{{ trans("mailchecker.mailbox_numbers", ["total" => $value["total"], "unread" => $value["unread"]]) }}
                                            @if(null !== $value["newest_message"])
                                                </p><p>
                                            {{ trans("mailchecker.latest_message_from", ["from" => str_shorten($value["newest_message"]["from"], 30, "...")]) }}
                                            <br />
                                            {{ trans("mailchecker.latest_message_date", ["date" => $value["newest_message"]["date"]->format('d.m.Y')]) }}
                                            {{ trans("mailchecker.latest_message_time", ["time" => $value["newest_message"]["date"]->format('H:i')]) }}
                                            <br />
                                            {{ trans("mailchecker.latest_message_subject", ["subject" => str_shorten($value["newest_message"]["subject"], 30, '...')]) }}
                                            <br />
                                            {{ trans("mailchecker.latest_message_folder", ["folder" => $value["newest_message"]["folder"]]) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
