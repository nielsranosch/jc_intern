<?php $comment = $currentRehearsal->hasCommented($user) ? $currentRehearsal->getComment($user) : ''; ?>
<?php $mark = $currentRehearsal->hasCommented($user) ? '<i class="far fa-comment"></i>' : ''; ?>
<div class="col-xs-6 col-sm-3 col-lg-2 names" title="{{ $comment }}">
    {{ $user->first_name . ' ' . $user->last_name }}
    <span class="pull-right">
    @if($user->excusedRehearsal($currentRehearsal->id))
        <i class="fa fa-bed" title="{{ trans('date.excused') }}"></i>
    @endif
        {!! $mark !!}
    </span>
</div>

<div class="col-xs-6 col-sm-3 col-lg-2 sliders">
    <span class="slider-2d" data-function="changePresence" data-change-url="{{ route('attendances.changePresence', ['rehearsal_id' => $currentRehearsal->id, 'user_id' => $user->id]) }}">
        <input type="checkbox" {!! $user->missedRehearsal($currentRehearsal->id) ? '' : 'checked="checked"' !!} id="slider-attending-{{ $user->id }}">
        <label for="slider-attending-{{ $user->id }}">
            <span class="slider slider-attendance"></span>
            <i class="fa fa-times" title="{{ trans('date.missed') }}"></i>
            <i class="fa fa-check" title="{{ trans('date.attended') }}"></i>
        </label>
    </span>
</div>
<div class="clearfix visible-xs-block"></div>
