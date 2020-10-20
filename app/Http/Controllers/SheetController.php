<?php

namespace App\Http\Controllers;

use App\Models\Sheet;
use App\Models\User;
use App\Models\Voice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request; // as InputRequest;
use Illuminate\Http\Request as HttpRequest;

class SheetController extends Controller
{
    protected $validation = [
    ];

    public function __construct() {
        $this->middleware('auth');
        $this->middleware('admin:gig');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $sheets = Sheet::all();
        $users  = User::all(['id', 'first_name', 'last_name']);
        return view('sheet.index', ['sheets' => $sheets, 'users' => $users]);
    }

    public function create(){
        return view('sheet.create');
    }

    public function store(HttpRequest $request){
        $this->validate($request, Sheet::$rules);
        $sheet = Sheet::create($request->only(['label', 'amount']));
        $sheet->save();
        return redirect()->action('SheetController@distribute', ['id' => $sheet->id]);

    }

    public function edit($id){
        $sheet = Sheet::findOrFail($id);
        return view('sheet.edit', ['sheet' => $sheet]);
    }

    public function destroy($id){
        $sheet = Sheet::findOrFail($id);
        //TODO: Nope nope nope.
        DB::delete('delete from sheet_user where sheet_id = :id', ['id' => $sheet->id]);
        $sheet->delete();
        return response()->redirectToAction('SheetController@index');
    }

    public function distribute($id){
        $sheet = Sheet::findOrFail($id);
        $parentVoices = Voice::getParentVoices();
        $users = User::all();
        return view('sheet.distribute', ['sheet' => $sheet, 'parentVoices' => $parentVoices, 'users' => $users]);
    }

    public function processDistribute($id){
        $sheet = Sheet::findOrFail($id);
        if (!Request::has('users')){
            return response()->redirectToAction('SheetController@distribute', ['id' => $sheet->id])->withErrors(['no input']);
        }
        $userIds = Request::input('users');
        $users = User::findMany($userIds);
        if ($users->count() == 0){
            return response()->redirectToAction('SheetController@distribute', ['id' => $sheet->id])->withErrors(['no users']);
        }

        foreach ($users as $user){
            $number = $sheet->getNextFreeNumber();
            $user->sheets()->attach($sheet->id, ['status' => Sheet::STATUS_BORROWED, 'number' => $number]);
        }
        return response()->redirectToAction('SheetController@show', ['id' => $sheet->id]);
    }

    public function show($id){
        $sheet = Sheet::findOrFail($id);

        $boughtRaw = $sheet->bought()->orderBy('user_id')->get();
        $bought = [];

        foreach($boughtRaw as $user){
            if (!array_key_exists($user->id, $bought)) {
                $bought[$user->id] = ['name' => $user->first_name . ' ' . $user->last_name, 'numbers' => [$user->pivot->number]];
            } else {
                $bought[$user->id]['numbers'][] = $user->pivot->number;
            }
        }

        $lostRaw = $sheet->lost()->orderBy('user_id')->get();
        $lost = [];

        foreach($lostRaw as $user){
            if (!array_key_exists($user->id, $lost)) {
                $lost[$user->id] = ['name' => $user->first_name . ' ' . $user->last_name, 'numbers' => [$user->pivot->number]];
            } else {
                $lost[$user->id]['numbers'][] = $user->pivot->number;
            }
        }


        $borrowedRaw = $sheet->borrowed()->orderBy('user_id')->get();
        $borrowed = [];

        foreach($borrowedRaw as $user){
            if (!array_key_exists($user->id, $borrowed)){
                $borrowed[$user->id] = ['name' => $user->first_name . ' ' . $user->last_name, 'numbers'  => [$user->pivot->number]];
            } else {
                $borrowed[$user->id]['numbers'][] = $user->pivot->number;
            }
        }
        return view('sheet.show', ['sheet' => $sheet, 'borrowed' => $borrowed, 'lost' => $lost, 'bought' => $bought]);
    }

    public function ajaxUpdate($id){
        if (!Request::has('borrowed')){
            return response()->json(['error' => 'wrong_input', 'message' => trans('form.error_arbitrary')], 500);
        }

        $names = explode(" ", Request::input('borrowed'));

        if (count($names)!=2){
            return response()->json(['error' => 'user_not_found', 'message' => trans('form.error_user_not_found', ['name' => Request::input('borrowed')])], 500);
        }

        $firstname = $names[0];
        $lastname  = $names[1];

        $user  = User::whereFirstName($firstname)->whereLastName($lastname)->first();

        if (!$user){
            return response()->json(['error' => 'user_not_found', 'message' => trans('form.error_user_not_found', ['name' => Request::input('borrowed')])], 500);
        }

        $sheet = Sheet::find($id);
        if (!$sheet){
            return response()->json(['error' => 'sheet_not_found', 'message' => trans('form.error_sheet_not_found', ['id' => $id])], 500);
        }

        $next  = $sheet->getNextFreeNumber();

        $sheet->users()->save($user, ['number' => $next, 'status' => Sheet::STATUS_BORROWED]);

        return response()->json(['message' => trans('sheet.created_with_number', ['name' => $firstname, 'number' => $next]), 'number' => $next, 'sheetId' => $sheet->id]);
    }

    public function sheetsPerUser($id){
        $user  = User::findOrFail($id);
        $sheets = $user->sheets;

        $boughtRaw = $user->bought;
        $bought = [];

        foreach($boughtRaw as $sheet){
            if (!array_key_exists($sheet->id, $bought)) {
                $bought[$sheet->id] = ['name' => $sheet->label, 'numbers' => [$sheet->pivot->number]];
            } else {
                $bought[$sheet->id]['numbers'][] = $sheet->pivot->number;
            }
        }

        $lostRaw = $user->lost;
        $lost = [];

        foreach($lostRaw as $sheet){
            if (!array_key_exists($sheet->id, $lost)) {
                $lost[$sheet->id] = ['name' => $sheet->label, 'numbers' => [$sheet->pivot->number]];
            } else {
                $lost[$sheet->id]['numbers'][] = $sheet->pivot->number;
            }
        }


        $borrowedRaw = $user->borrowed;
        $borrowed = [];

        foreach($borrowedRaw as $sheet){
            if (!array_key_exists($sheet->id, $borrowed)){
                $borrowed[$sheet->id] = ['name' => $sheet->label, 'numbers'  => [$sheet->pivot->number]];
            } else {
                $borrowed[$sheet->id]['numbers'][] = $sheet->pivot->number;
            }
        }

        return view('sheet.sheetsPerUser', ['user' => $user, 'sheets' => $sheets, 'borrowed' => $borrowed, 'lost' => $lost, 'bought' => $bought]);
    }

    public function sheetUser($id, $number){
        $sheet = Sheet::findOrFail($id);
        $user = $sheet->users()->wherePivot('number', '=', $number)->first();
        if (!$user){
            return view('sheet.new_sheet_user');
        }

        $usersRaw = User::all(['id', 'first_name', 'last_name']);
        $usersArray = [];

        foreach ($usersRaw as $u){
            $usersArray[$u->id] = $u->name;
        }

        $statuses = [Sheet::STATUS_BORROWED, Sheet::STATUS_BOUGHT, Sheet::STATUS_LOST];

        return view('sheet.sheet_user', ['sheet' => $sheet, 'user' => $user, 'usersArray' => $usersArray, 'statuses' => $statuses, 'number' => $user->pivot->number]);
    }

    public function sheetUserUpdate($id, $number){
        $sheet = Sheet::find($id);
        if (!$sheet){
            return response()->json(['error' => 'wrong_input', 'message' => trans('form.error_arbitrary')], 500);
        }
        $oldUser = $sheet->users()->wherePivot('number', '=', $number)->first();
        if (!$oldUser){
            return response()->json(['error' => 'wrong_input', 'message' => trans('form.error_number'), ['number' => $number]], 500);
        }

        $oldStatus = $oldUser->pivot->status;
        $oldNumber = $oldUser->pivot->number;

        if (!Request::has('userid') || !Request::has('status') || !Request::has('number')){
            return response()->json(['error' => 'wrong_input', 'message' => trans('form.error_arbitrary')], 500);
        }

        $status = Request::input('status');
        if (!in_array($status, Sheet::$statuses)){
            return response()->json(['error' => 'invalid_status', 'message' => trans('form.error_status', ['status' => $status])], 500);
        }

        $number = Request::input('number');
        if ($number != $oldNumber && $sheet->numberExists($number, $oldNumber)){
            return response()->json(['error' => 'double_number', 'message' => trans('form.error_double_number', ['number' => $number])], 500);
        }


        $userid = Request::input('userid');
        $user  = User::find($userid);

        if (!$user){
            return response()->json(['error' => 'user_not_found', 'message' => trans('form.error_user_not_found', ['name' => 'id: ' . $userid])], 500);
        }

        if ($status != $oldStatus || $number != $oldNumber || $user != $oldUser){
            DB::update('update sheet_user set status = :status, number = :number, user_id = :userid where id = :pivot', ['status' => $status, 'number' => $number, 'userid' => $user->id, 'pivot' => $oldUser->pivot->id]);
        }
        return redirect()->action('SheetController@sheetUser', ['id' => $sheet->id, 'number' => $number])->with('message_success', trans('sheet.sheet_user_update_success'));
    }

    public function returnSheet($id, $number){
        $sheet = Sheet::find($id);
        if (!$sheet){
            return response()->json(['error' => 'wrong_input', 'message' => trans('form.error_arbitrary')], 500);
        }
        $user = $sheet->users()->wherePivot('number', '=', $number)->first();
        if (!$user){
            return response()->json(['error' => 'wrong_input', 'message' => trans('form.error_number'), ['number' => $number]], 500);
        }

        DB::delete('delete from sheet_user where id = :pivot', [ 'pivot' => $user->pivot->id]);

        return redirect()->action('SheetController@show', ['id' => $sheet->id]);

    }
}
