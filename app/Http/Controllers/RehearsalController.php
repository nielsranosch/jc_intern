<?php

namespace App\Http\Controllers;

use App\Models\Rehearsal;
use App\Models\RehearsalAttendance;
use App\Models\User;
use App\Models\Voice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RehearsalController extends EventController {
    protected $validation = [
        'title'     => 'required|string|max:100',
        'description' => 'string|max:255',
        'start'     => 'required|date',
        'end'       => 'required|date|after:start',
        'place'     => 'required|string',
        'voice_id'  => 'required|integer|min:0',
        'weight'    => 'required|numeric|min:0|max:10',
        'mandatory' => 'required|boolean',
        'repeat'    => 'boolean',
        'end_repeat'=> 'required_if:repeat,1|after:start',
        'interval'  => 'required_if:repeat,1',
    ];

    public function __construct() {
        parent::__construct();

        $this->middleware('admin:rehearsal');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        $voices = [];
        // If user rights are restricted only give her voice back.
        if (\Auth::user()->adminOnlyOwnVoice('rehearsal')) {
            $voices[\Auth::user()->voice->id] = \Auth::user()->voice->name;
        } else {
            $voices = Voice::all()->pluck('name', 'id')->toArray();
        }

        return view('date.rehearsal.create', ['voices' => $voices]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $this->validate($request, $this->validation);

        $data = $this->prepareDates($request->all());

        // Create new rehearsal with data.
        $rehearsal = Rehearsal::create($data);

        // Create attendances for all users, if gig is mandatory.
        if ($rehearsal->mandatory) {
            $this->createAttendances($rehearsal, \Config::get('enums.attendances')['yes']);
        }

        if ($data['repeat']) {
            $start = new Carbon($data['start']);
            $end   = new Carbon($data['end']);

            $endRep= (new Carbon($data['end_repeat']))->addDay();

            while ($start->lt($endRep)) {
                if ($data['interval'] == 'weekly') {
                    $start = $start->addWeek();
                    $end = $end->addWeek();
                } else if ($data['interval'] == 'daily') {
                    $start = $start->addDay();
                    $end = $end->addDay();
                } else {
                    $start = $start->addMonth();
                    $end = $end->addMonth();
                }

                if ($end->lt($endRep)) {
                    $data['start'] = $start->toDateTimeString();
                    $data['end'] = $end->toDateTimeString();

                    $rehearsal = Rehearsal::create($data);

                    if ($rehearsal->mandatory) {
                        $this->createAttendances($rehearsal, \Config::get('enums.attendances')['yes']);
                    }
                } else {
                    break;
                }
            }
        }

        $request->session()->flash('message_success', trans('date.success'));

        return redirect()->route('dates.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        return $this->edit($id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $rehearsal = Rehearsal::find($id);

        $voices = [];
        // If user rights are restricted only give her voice back.
        if (\Auth::user()->adminOnlyOwnVoice('rehearsal')) {
            $voices[\Auth::user()->voice->id] = \Auth::user()->voice->name;
        } else {
            $voices = Voice::all()->pluck('name', 'id')->toArray();
        }

        if (null === $rehearsal) {
            return back()->withErrors(trans('date.rehearsal_not_found'));
        }

        return view('date.rehearsal.show', ['rehearsal' => $rehearsal, 'voices' => $voices]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $rehearsal = Rehearsal::find($id);

        if (null === $rehearsal) {
            return redirect()->route('dates.index')->withErrors([trans('date.not_found')]);
        }
        
        $this->validate($request, $this->validation);

        $data = $this->prepareDates($request->all());

        $rehearsal->update($data);
        $rehearsal->save();

        $request->session()->flash('message_success', trans('date.success'));

        return redirect()->route('dates.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     *
     * @throws \Exception
     */
    public function destroy($id) {
        $rehearsal = Rehearsal::find($id);

        if (null === $rehearsal) {
            return redirect()->route('dates.index')->withErrors([trans('date.not_found')]);
        }

        
        try {
            $rehearsal->delete();
        } catch (\Exception $exception) {
            return redirect()->route('dates.index')->withErrors(
                [
                    trans('date.delete_error', ['message' => $exception->getMessage()])
                ]
            );
        }
        //$rehearsal->delete();

        \Session::flash('message_success', trans('date.delete_success'));

        return redirect()->route('dates.index');
    }

    /**
     * Create all attendances in the database for an event. Set to the given attendance status.
     *
     * Already existing attendances will be overridden.
     *
     * @param $rehearsal
     * @param null $attendance Using 1 ('maybe') is discouraged because it might not be enabled for the event.
     * @param null|bool $missed
     * @param null|array $users generate attendances only for specific users. null: for all users. empty array: no users
     */
    public static function createAttendances($rehearsal, $attendance = null, $users = null, $missed = true) {
        if (!is_array($users)) {
            if (null === $users) {
                $users = User::all();
            } elseif ($users instanceof User) {
                // A single user has been given
                $users = [$users];
            }
        }

        //TODO: Optimize! This loop can be refactored into a single SQL-query.
        foreach ($users as $user) {
            RehearsalAttendance::updateOrCreate(['user_id' => $user->id, 'rehearsal_id' => $rehearsal->id], ['attendance' => $attendance, 'missed' => $missed]);
        }
    }
}
