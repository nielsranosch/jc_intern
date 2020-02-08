@extends('layouts.app')

@section('title'){{ trans('sheet.sheet_distribute_title', ['label' => $sheet->label]) }}@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <h1>{{ trans('sheet.sheet_distribute_title', ['label' => $sheet->label]) }}</h1>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="panel panel-2d">
                <div class="panel-heading">
                    <div class="panel-title pull-left">
                        {{ trans('sheet.select_users') }}
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-body">
                    {{ Form::open([
                        'action' => ['SheetController@processDistribute', $sheet->id],
                        'id' => 'sheet-distribute-form',
                    ]) }}
                    <div class="row">
                        <div class="col-xs-5 users">
                            <select style="overflow-y: auto" size="{{ $users->count() }}" name="users[]" id="userlist" class="form-control form-control-2d" multiple="multiple">
                                @foreach($users->sortBy('first_name') as $id => $user)
                                    <option value="{{ $id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xs-3 voices">
                            <div class="btn-group-vertical">
                                <button type="button" id="voice-choir" class="btn btn-group-lg btn-2d">{{ trans('sheet.choir') }}</button>
                                @foreach ($parentVoices as $parentVoice)
                                    <?php $childVoices = $parentVoice->children; ?>
                                    <?php $childIds    = Illuminate\Support\Arr::flatten($parentVoice->children()->get(['id'])->toArray()); ?>
                                    <button type="button" id="voice-id-{{ $parentVoice->id }}" data-parent-of="{{ join(',', $childIds) }}" class="parent-voice btn btn-2d">{{ $parentVoice->name }}</button>
                                        @foreach ($childVoices as $childVoice)
                                            <button type="button" id="voice-id-{{ $childVoice->id }}" data-users="{{  join(',', Illuminate\Support\Arr::flatten(App\User::current()->ofVoice($childVoice->id)->get(['id'])->toArray())) }}" class="btn btn-sm btn-2d child-voice">{{ $childVoice->name }}</button>
                                        @endforeach
                                    @endforeach
                                <button type="button" id="voice-deselect" class="btn btn-group-lg btn-2d">{{ trans('sheet.deselect') }}</button>
                            </div>
                        </div>
                        <div class="col-xs-3 submit">
                            <br>
                            <button type="submit" class="btn btn-2d">
                                {{ trans('sheet.submit_distribution') }}
                            </button>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')

    <script>
        $(document).ready(function () {

            $('#voice-choir').click(function(){
                $('select#userlist option').attr('selected', true);
            });

            $('.child-voice').click(function(){
                $(this).attr('data-users').split(',').forEach(function(id){
                    $('select#userlist option[value="'+id+'"]').attr('selected', true);
                });
            });

            $('.parent-voice').click(function(){
                $(this).attr('data-parent-of').split(',').forEach(function(id){
                    $('button#voice-id-'+id).click();
                });
            });

            $('#voice-deselect').click(function(){
                $('select#userlist option').removeProp('selected');
            });

        });
    </script>


@endsection