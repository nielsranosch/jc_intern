<div class="row list-item" data-filters='["{{ implode('", "', $date->getApplicableFilters()) }}"]'>
    <div class="col-xs-12 context-box-2d event event-{{ implode(" event-", $date->getApplicableFilters()) }}">
        <div class="row">
            <div class="col-xs-12 col-sm-9 col-lg-10">
                <h4 class="title">
                    {{ $date->getTitle() }}
                    <span class="not-going-note" style="{{ in_array('not-going', $date->getApplicableFilters())  ? 'display: inline;' : 'display: none;' }};">
                        &ndash; {{ trans('date.not_attending') }}
                    </span>
                    @if($date->hasPlace())
                        <br>
                        {{ $date->place }}
                    @endif
                </h4>

                @if($date->hasPlace())
                    <p class="place">
                        <a href="{{'https://www.google.com/maps/search/'}}@urlescape($date->place)/" title="{{ trans('date.address_search') }}" rel="noopener noreferrer"  target="_blank" class="pull-right text-large">
                            {{ trans('date.goto_maps') }} <i class="far fa-map"></i>
                        </a>
                    </p>
                @endif

                <p class="date">
                    @if($date->isAllDay())
                        {{ $date->getStart()->formatLocalized('%A, den %d.%m.%Y') }}
                    @else
                        @if(0 === $date->getEnd()->diffInDays($date->getStart()))
                            {{ $date->getStart()->formatLocalized('%A, den %d.%m.%Y') }}
                            <br>
                            {{ $date->getStart()->formatLocalized('%H:%M') . ' - ' . $date->getEnd()->formatLocalized('%H:%M') }}
                        @else
                            {{ $date->getStart()->formatLocalized('%A, den %d.%m.%Y %H:%M') }}
                            <br>
                            bis&nbsp;{{ $date->getEnd()->formatLocalized('%A, den %d.%m.%Y %H:%M') }}
                        @endif
                    @endif
                </p>
                <p class="date_descr">
                    {{ $date->description }}
                </p>
                @if($date->hasCommented())
                    <p class="date_comment">
                        <em>{{ trans('date.your_comment') }} {{ $date->getComment() }}</em>
                    </p>
                @endif
                @if($date->isWeighted())
                    <p class="date_weight"><strong>{{ trans('date.weight', ['weight' => localize_number($date->weight)]) }}</strong></p>
                @endif
            </div>
            <div class="col-xs-12 col-sm-3 col-lg-2 event-controls">
                @if($date->needsAnswer())
                    @if($date->hasBinaryAnswer() && $date->hasAnswered())
                        <div class="row">
                            <div class="col-xs-12">
                                <span class="slider-2d"
                                      data-function="changeAttendanceSlider"
                                      data-attend-url="{{ route('attendances.changeOwnAttendance', ['events_name' => $date->getShortNamePlural(), 'event_id' => $date->getId(), 'shorthand' => 'attend']) }}"
                                      data-excuse-url="{{ route('attendances.changeOwnAttendance', ['events_name' => $date->getShortNamePlural(), 'event_id' => $date->getId(), 'shorthand' => 'excuse']) }}">
                                    <input type="checkbox" {!! $date->isAttending() === 'yes' ? ' checked="checked"' : '' !!} id="slider-attending-{{ $date->getShortName() }}-{{ $date->getId() }}">
                                    <label for="slider-attending-{{ $date->getShortName() }}-{{ $date->getId() }}">
                                        <span class="slider"></span>
                                        <i class="far fa-calendar-times" title="{{ trans('date.excuse') }}"></i>
                                        <i class="far fa-calendar-check" title="{{ trans('date.attend') }}"></i>
                                    </label>
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="row">
                            <div class="col-xs-12">
                                <span class="button-set-2d button-set-attendances">
                                    <a href="#"
                                       class="btn btn-2d btn-no {{ $date->hasAnswered() && $date->isAttending() == 'no' ? 'btn-pressed' : 'btn-unpressed' }}"
                                       data-url="{{ route('attendances.changeOwnAttendance', ['events_name' => $date->getShortNamePlural(), 'event_id' => $date->getId(), 'shorthand' => 'excuse'])}}"
                                       data-attendance="no"
                                       title="{{ trans('date.excuse') }}">
                                        <i class="far fa-calendar-times"></i>
                                    </a>
                                    @if(!$date->hasBinaryAnswer())
                                        <a href="#"
                                           class="btn btn-2d btn-maybe {{ $date->hasAnswered() && $date->isAttending() == 'maybe' ? 'btn-pressed' : 'btn-unpressed' }}"
                                           data-url="{{ route('attendances.changeOwnAttendance', ['events_name' => $date->getShortNamePlural(), 'event_id' => $date->getId(), 'shorthand' => 'maybe'])}}"
                                           data-comment-url="{{ route('attendances.changeOwnAttendance', ['events_name' => $date->getShortNamePlural(), 'event_id' => $date->getId(), 'shorthand' => 'change'])}}"
                                           data-attendance="maybe"
                                           @if($date->hasCommented())
                                           data-current-comment="{{$date->getComment()}}"
                                           @endif
                                           title="{{ trans('date.maybe') }}">
                                            <i class="fas fa-question"></i>
                                        </a>
                                    @endif
                                    <a href="#"
                                       class="btn btn-2d btn-yes {{ $date->hasAnswered() && $date->isAttending() == 'yes' ? 'btn-pressed' : 'btn-unpressed' }}"
                                       data-url="{{ route('attendances.changeOwnAttendance', ['events_name' => $date->getShortNamePlural(), 'event_id' => $date->getId(), 'shorthand' => 'attend'])}}"
                                       data-attendance="yes"
                                       title="{{ trans('date.attend') }}">
                                        <i class="far fa-calendar-check"></i>
                                    </a>
                                </span>
                            </div>
                        </div>
                    @endif
                        <div class="row">
                            <div class="col-xs-12">
                                <span class="comment-btn-container button-set-2d">
                                    <a href="#" data-comment-url="{{ route('attendances.changeOwnAttendance', ['events_name' => $date->getShortNamePlural(), 'event_id' => $date->getId(), 'shorthand' => 'change'])}}"
                                       @if($date->hasCommented())
                                       data-current-comment="{{$date->getComment()}}"
                                       @endif
                                       class="btn btn-2d comment-btn"
                                       title="{{ trans('form.add_comment') }}">
                                        <i class="far fa-comment"></i>
                                    </a>
                                    @if(Auth::user()->isAdmin($date->getShortName()))
                                        @if($date->needsAnswer())
                                            <a href="{{ route($date->getShortNamePlural() . '.listAttendances', ['id' => $date->id]) }}"
                                               class="btn btn-2d"
                                               title="{{ trans('date.view_attendances') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @endif
                                        @if(isset($date->getEventOptions()['url']))
                                            <a href="{{ $date->getEventOptions()['url'] }}"
                                               class="btn btn-2d"
                                               title="{{ trans('form.edit') }}">
                                            <i class="fa fa-pencil-alt"></i>
                                        </a>
                                        @endif
                                    @endif
                                </span>
                            </div>
                        </div>
                @endif
            </div>
        </div>
    </div>
</div>
