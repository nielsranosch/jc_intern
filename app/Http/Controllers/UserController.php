<?php

namespace App\Http\Controllers;

use App\Models\Gig;
use App\Models\Rehearsal;
use App\Models\Semester;
use App\Models\Voice;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request; // as HttpRequest;

use App\Models\User;
use Illuminate\Support\Facades\Request as InputRequest;
use Illuminate\Support\Str;

class UserController extends Controller {
    protected $validation = [
        'first_name'=> 'required|alpha_dash_space|max:255',
        'last_name' => 'required|alpha_dash_space|max:255',
        'email'     => 'required|email|max:191|unique:users,email', // InnoDB (MySQL's engine) can handle VARCHARs only up to 191 when UNIQUE is selected.
        'voice_id'  => 'required|integer|min:0|exists:voices,id',
        'birthday'  => 'date|after:1900-01-01',
        'address_zip'   => 'integer',
        'sheets_deposit_returned' => 'boolean',
        'share_private_data' => 'boolean',
    ];

    protected $password_validation = [
        'password'    => 'required|min:8|custom_complexity:3',
    ];

    protected $password_validation_own_update = [
        'password'    => 'required|min:8|custom_complexity:3|confirmed',
    ];

    public function __construct() {
        $this->middleware('auth');

        $this->middleware(
            'adminOrOwn', [
                'except' => ['index']
            ]
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return view('user.index', [
            'musical_leader' => User::getMusicalLeader(),
            'voices' => Voice::getParentVoices(),
            //TODO: rework with many-to-many between users and voices
            'old_users' => User::orderBy('voice_id')->past(true)->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        $voice = null;

        if (InputRequest::has('voice')) {
            $voice = InputRequest::input('voice');

            $voiceModel = Voice::find($voice);
            while (null !== $voiceModel && !$voiceModel->child_group) {
                $voiceModel = $voiceModel->children()->first();
                $voice = $voiceModel->id;
            }
        }

        $voice_choice = Voice::getChildVoices()->pluck('name', 'id')->toArray();
        $voice_choice[1] = trans('user.no_voice');

        // Generate a random password until one satisfies our conditions
        $random_password = Str::random(10);
        $v = \Validator::make(['password' => $random_password], $this->password_validation);
        for ($i = 0; $i < 30; $i++) { // max 30 times just in case
            if (!$v->passes()) {
                $random_password = Str::random(10);
                $v = \Validator::make(['password' => $random_password], $this->password_validation);
            } else {
                break;
            }
        }

        return view('user.create', [
            'voice' => $voice,
            'voice_choice' => $voice_choice,
            'random_password' => $random_password
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $this->validate(
            $request,
            array_merge($this->validation, $this->password_validation)
        );

        $data = array_merge($request->all(),
            [
                'last_echo' => Semester::current()->id,
            ]
        );

        $data['password'] = bcrypt(\Arr::pull($data, 'password'));
        $pseudo_password = Str::random(222);

        // Generate a pseudo_id which is unique
        for ($length = 20; $length <= 255; $length++) {
            $pseudo_id = Str::random($length);
            if (User::where('pseudo_id', '=', $pseudo_id)->count() === 0) {
                // This is virtually guaranteed to succeed during the first loop
                break;
            }
            if ($length === 255) {
                abort(500, "WTF?");
            }
        }

        $user = new User($data);
        $user->password = $hashed_password;
        $user->pseudo_id = $pseudo_id;
        $user->pseudo_password = $pseudo_password;
        $user->save();

        //TODO: Optimize. These loops can be refactored into a single SQL-query.

        $new_rehearsals = Rehearsal::all(['*'], false, false, true, false);
        foreach ($new_rehearsals as $rehearsal) {
            if ($rehearsal->mandatory) {
                // We prefill future mandatory rehearsals with 'attending'
                RehearsalController::createAttendances($rehearsal, \Config::get('enums.attendances')['yes'], $user);
            } else {
                RehearsalController::createAttendances($rehearsal, \Config::get('enums.attendances')['no'], $user);
            }
        }

        $old_rehearsals = Rehearsal::all(['*'], true, false, false, true);
        foreach ($old_rehearsals as $rehearsal) {
            if ($rehearsal->mandatory) {
                // For rehearsals in the past, we claim the user attended them. This ensures he/she doesn't get a missed rehearsal mark for rehearsal when they were not yet part of the choir.
                RehearsalController::createAttendances($rehearsal, \Config::get('enums.attendances')['no'], $user, false);
            }
        }

        // New users rarely attend upcoming gigs in their first semester.
        $new_gigs = Gig::all(['*'], false, false, true, true);
        foreach ($new_gigs as $gig) {
            GigController::createAttendances($gig, \Config::get('enums.attendances')['no'], $user);
        }

        $request->session()->flash('message_success', trans('user.success'));

        return redirect()->route('users.show', ['id' => $user->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        // Actually we do not need a "show single user".
        return $this->edit($id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $user = User::find($id);

        if (null !== $user) {
            if(\Auth::user()->isAdmin()) {
                $voice_choice = Voice::getChildVoices()->pluck('name', 'id')->toArray();
                $voice_choice[1] = trans('user.no_voice');
            } else {
                $voice_choice = [
                    $user->voice->id => $user->voice->name,
                ];
            }

            return view('user.profile', [
                'user' => $user,
                'voice_choice' => $voice_choice
            ]);
        } else {
            return redirect()->route('users.index')->withErrors([trans('user.not_found')]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        try {
            $user = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('users.index')->withErrors([trans('user.not_found')]);
        }

        // Ignore the current user for the unique mail check.
        $validation = $this->validation;
        $validation['email'] .= ',' . $user->id;

        $hashed_password = null;
        if ($request->get('password') == '') {
            $this->validate($request, $validation);
            $data = $request->except('password');
        } else {
            if (\Auth::user()->id === $id) {
                $validation = array_merge($validation, $this->password_validation_own_update);
            } else {
                $validation = array_merge($validation, $this->password_validation);
            }

            $this->validate($request, $validation);

            $data = $request->all();
            $hashed_password = bcrypt(\Arr::pull($data, 'password'));
        }


        $user->fill($data);
        if (!empty($hashed_password)) {
            $user->password = $hashed_password;
        }
        if(!$user->save()) {
            return redirect()->route('users.index')->withErrors([trans('user.update_failed')]);
        }

        $request->session()->flash('message_success', trans('user.success'));

        return redirect()->route('users.show', ['id' => $id]);
    }

    /**
     * Function to increment the User's last_echo semester id.
     *
     * @param Request $request
     * @param $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSemester(Request $request, $user_id) {
        try {
            $user = User::findOrFail($user_id);
        } catch (ModelNotFoundException $e) {
            if ($request->wantsJson()) {
                return \Response::json(['success' => false, 'message' => trans('user.not_found')]);
            } else {
                return back()->withErrors(trans('user.not_found'));
            }
        }

        $user->last_echo = Semester::current(true)->id;

        if(!$user->save()) {
            if ($request->wantsJson()) {
                return \Response::json(['success' => false, 'message' => trans('user.update_failed')]);
            } else {
                return back()->withErrors(trans('user.update_failed'));
            }
        }

        if ($request->wantsJson()) {
            return \Response::json(['success' => true, 'message' => trans('user.semester_update_success')]);
        } else {
            $request->session()->flash('message_success', trans('user.semester_update_success'));
            return back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy($id) {
        try {
            $user = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('users.index')->withErrors([trans('user.not_found')]);
        }

        if (null === $user) {
            return redirect()->route('users.index')->withErrors([trans('user.not_found')]);
        }

        $user->delete();

        \Session::flash('message_success', trans('user.delete_success'));

        return redirect()->route('users.index');
    }

    /**
     * Reset All passwords to random strings. Generate a CSV-Style output
     */
    /*public function resetAllPasswords() {
        $users = User::all(['*'], true);
        foreach ($users as $user) {
            $random_password = Str::random(10);
            $v = \Validator::make(['password' => $random_password], $this->password_validation);
            for ($i = 0; $i < 30; $i++) { // max 30 times just in case
                if (!$v->passes()) {
                    $random_password = Str::random(10);
                    $v = \Validator::make(['password' => $random_password], $this->password_validation);
                } else {
                    break;
                }
            }
            if ($i == 30) {
                var_dump('fail'); die();
            }
            echo('"' . $user->email . '", ' . '"' . $random_password . '"' . "\n");
            $user->password = bcrypt($random_password);
            $user->save();
        }
        die();
    }*/
}
