@if(isset($user))
    <?php Form::setModel($user); ?>
    <?php $newUser = false; ?>
@else
    <?php $newUser = true; ?>
    @endif
{!! Form::open($options) !!}
<div class="panel panel-2d">
    <div class="panel-heading">
        {{ trans('user.data') }}
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-xs-12 col-md-6">
                {!! Form::textInput2d('first_name') !!}
                {!! Form::textInput2d('last_name') !!}
                {!! Form::textInput2d('email') !!}
                {!! Form::dateInput2d('birthday') !!}
            </div>
            <div class="col-xs-12 col-md-6">
                {!! Form::textInput2d('phone') !!}
                {!! Form::textInput2d('address_street') !!}
                {!! Form::textInput2d('address_zip') !!}
                {!! Form::textInput2d('address_city') !!}
            </div>
            <div class="col-xs-12 col-md-6">
                {!! Form::selectInput2d('voice_id', $voice_choice, isset($voice) ? $voice : null) !!}
                @if(isset($user))
                    @if(Auth::user()->isAdmin())
                        {!! Form::checkboxInput2d('sheets_deposit_returned') !!}
                    @endif
                <p>{!! trans('user.last_echo', ['semester' => App\Models\Semester::find($user->last_echo)->label]) !!}</p>
                @endif
            </div>
            <div class="col-xs-12 col-md-6">
                @if(isset($random_password))
                    {!! Form::textInput2d('password', $random_password) !!}
                @else
                    {!! Form::passwordInput2d('password') !!}
                    @if(isset($user) && $user->id === Auth::user()->id)
                        {!! Form::passwordInput2d('password_confirmation') !!}
                    @endif
                @endif
                @if(empty($user))
                    <p>{{ trans('user.password_note') }}</p>
                @endif
                @if(isset($random_password))
                    <p>{{ trans('user.generated_password', ['password' => $random_password]) }}</p>
                @endif
            </div>
            @if(isset($user) && Auth::user()->id === $user->id)
                <div class="col-xs-12 col-md-6">
                    <p>{{ trans('user.share_private_data_text') }}</p>
                    <p>{{ trans('user.share_private_data_prompt') }}</p>
                    {!! Form::radioInput2d('share_private_data', trans('user.hide_private_data'), 0, !$user->share_private_data) !!}
                    {!! Form::radioInput2d('share_private_data', trans('user.show_private_data'), 1, $user->share_private_data) !!}
                </div>
            @endif
            <div class="clearfix"></div>
            <div class="col-xs-12 col-md-6">
                <span class="center-block">
                    {!! Form::submitInput2d() !!}
                </span>
            </div>
        </div>
    </div>
</div>
{!! Form::close() !!}