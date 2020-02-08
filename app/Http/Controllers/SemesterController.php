<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use Carbon\Carbon;
use Config;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class SemesterController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Get the semester object for a given Carbon date.
     * If the semester does not yet exist, create all semesters up to the needed one (recursively).
     *
     * @param Carbon $date
     * @return Semester
     */
    public static function getSemester(Carbon $date) {
        $semester = Semester::where(
            'start', '<=', $date->toDateString()
        )->where(
            'end', '>=', $date->toDateString()
        )->first();

        if (null === $semester) {
            self::generateNewSemester();
            return self::getSemester($date);
        }

        return $semester;
    }

    /**
     * Generate a new Semester completely programmatic. Can be called via route (with Request object) or internal.
     *
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public static function generateNewSemester(Request $request = null) {
        // Start to add new semester object.
        $newSemester = new Semester();

        // Get start of new Semester by adding one day to end of the last one.
        try {
            $lastSemester = Semester::last();
        } catch (ModelNotFoundException $e) {
            // No valid semester in database, create a fake one
            $lastSemester = new \stdClass();
            $lastSemester->end = Carbon::now()->startOfMonth(); // avoid month overlapping

            // Avoid semester start in the future
            if (Carbon::now()->month < min(config('semester.summer_term_start'), config('semester.winter_term_start'))) {
                // Early in the year
                $lastSemester->end->month(max(config('semester.summer_term_start'), config('semester.winter_term_start')) - 1);
                $lastSemester->end->subYear();
            } else if (Carbon::now()->month >= max(config('semester.summer_term_start'), config('semester.winter_term_start'))) {
                // Late in the year
                $lastSemester->end->month(max(config('semester.summer_term_start'), config('semester.winter_term_start')) - 1);
            } else {
                $lastSemester->end->month(min(config('semester.summer_term_start'), config('semester.winter_term_start')) - 1);
            }
            $lastSemester->end->endOfMonth();
        }
        // Add one day, but make sure it's the start of a month.
        $newSemester->start = $lastSemester->end->copy()->addDay()->startOfMonth();

        // Calculate end of this semester later on.
        $newSemester->end = $newSemester->start->copy();

        if (!Config::has('semester')) {
            if (null !== $request) {
                if ($request->wantsJson()) {
                    return \Response::json(['success' => false, 'message' => trans('semester.config_missing')]);
                } else {
                    return back()->withErrors(trans('semester.config_missing'));
                }
            } else {
                return false;
            }
        }

        // Is this a valid starting month?
        if ($newSemester->start->month == Config::get('semester.summer_term_start')) {
            // Do we have summer term start?
            // Calculate ending month of semester
            $newSemester->end = $newSemester->end->month(Config::get('semester.winter_term_start') - 1);
            
            // Append last two digits of semester's year to summer term name.
            $newSemester->label = Config::get('semester.summer_term_name') . ' ' . $newSemester->start->format('y');
        } else if ($newSemester->start->month == Config::get('semester.winter_term_start')) {
            // Do we have winter term start?
            // Calculate ending month of semester
            $newSemester->end = $newSemester->end->month(Config::get('semester.summer_term_start') - 1);
            // And add one to the year for winter term if start of summer term is not first of January.
            if (Config::get('semester.summer_term_start') != '01') {
                $newSemester->end = $newSemester->end->addYear();
            }
            // Append last two digits of semester's start and end year to winter term name.
            $newSemester->label = Config::get('semester.winter_term_name') . ' ' .  $newSemester->start->format('y') . '/' .  $newSemester->end->format('y');
        } else {
            // Something is very wrong. One shouldn't land here, cause month is not a valid starting month.
            if (null !== $request) {
                if ($request->wantsJson()) {
                    return \Response::json(['success' => false, 'message' => trans('semester.wrong_start')]);
                } else {
                    return back()->withErrors(trans('semester.wrong_start'));
                }
            } else {
                throw new \ErrorException('New Semester could not be generated.');
            }
        }
        // End calculation of end date.
        $newSemester->end = $newSemester->end->endOfMonth();

        if ($newSemester->save()) {
            // Semester successfully saved.
            if (null !== $request) {
                if ($request->wantsJson()) {
                    return \Response::json(['success' => true, 'message' => trans('semester.semester_created_successful')]);
                } else {
                    $request->session()->flash('message_success', trans('semester.semester_created_successful'));
                    return back();
                }
            } else {
                return true;
            }
        } else {
            // Semester not successfully saved.
            if (null !== $request) {
                if ($request->wantsJson()) {
                    return \Response::json(['success' => false, 'message' => trans('semester.semester_creation_error')]);
                } else {
                    return back()->withErrors(trans('semester.semester_creation_error'));
                }
            } else {
                return false;
            }
        }
    }
}
