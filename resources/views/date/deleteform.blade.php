<?php Form::setModel($event); ?>

<div class="panel-body">
    {!! Form::open(array_merge(["id" => "deleteForm"], $options)) !!}
    <div class="row">
        <div class="col-xs-12 col-md-6">
            {!! Html::button2d(trans('form.delete'), "#", "times", [], ["id" => "deleteButton"]) !!}
        </div>
    </div>
    {!! Form::close() !!}
</div>

<form id="comment-form" class="modal" style="display: none;">
<b>{{ trans('form.confirmDelete') }}</b><br>
{!! Html::button2d(trans('form.yes'), "#", "", [], ["id" => "buttonYes", "style" => "margin-left: 10px;"]) !!}
{!! Html::button2d(trans('form.no'), "#", "", [], ["id" => "buttonNo"]) !!}
</form>

@section('js')
<script>
$(document).ready(function() {
    $("#deleteButton").click(function() {
        $("#comment-form").modal({
            'showClose': false
        });
    });
    // Confirm delete
    $("#buttonYes").click(function() {
        $("#deleteForm").submit();
    });
    // Abort delete
    $("#buttonNo").click(function() {
        $.modal.close();
    });
});
</script>
@endsection