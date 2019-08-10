<?php Form::setModel($event); ?>

<div class="panel-body">
    {!! Form::open($options) !!}
    <div class="row">
        <div class="col-xs-12 col-md-6">
            {!! Form::submitDelete2d() !!}
        </div>
    </div>
    {!! Form::close() !!}
</div>