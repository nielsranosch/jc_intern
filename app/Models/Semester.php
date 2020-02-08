<?php

namespace App\Models;

use App\Http\Controllers\SemesterController;
use \Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * App\Models\Semester
 *
 * @property int $id
 * @property string $start
 * @property string $end
 * @property string $label
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $echoed
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Gig[] $gigs
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Rehearsal[] $rehearsals
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SemesterFee[] $semester_fees
 * @method static Builder|Semester whereCreatedAt($value)
 * @method static Builder|Semester whereEnd($value)
 * @method static Builder|Semester whereId($value)
 * @method static Builder|Semester whereLabel($value)
 * @method static Builder|Semester whereStart($value)
 * @method static Builder|Semester whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Semester extends \Eloquent {
    private static $current_semester = ['current' => null, 'shifted' => null];

    protected $dates = ['start', 'end', 'created_at', 'modified_at'];

    public function gigs() {
        return $this->hasMany('App\Models\Gig');
    }

    public function rehearsals() {
        return $this->hasMany('App\Models\Rehearsal');
    }

    public function echoed() {
        return $this->hasMany('App\Models\User', 'last_echo');
    }

    public function semester_fees() {
        return $this->hasMany('App\Models\SemesterFee');
    }

    public static function current($shift_for_transition_period = false) {
        $shift = ($shift_for_transition_period ? 'shifted' : 'current');

        // Memorize current semester to save queries.
        if (self::$current_semester[$shift] === null) {
            $today = self::today($shift_for_transition_period);
            try {
                self::$current_semester[$shift] = Semester::where('start', '<=', $today)->where('end', '>=', $today)->firstOrFail();
            } catch (ModelNotFoundException $error) {
                // No fitting semester found: Add one and try again. This should never happen in production.
                if (SemesterController::generateNewSemester()) {
                    self::$current_semester[$shift] = Semester::where('start', '<=', $today)->where('end', '>=', $today)->firstOrFail();
                }
            }
        }
        return self::$current_semester[$shift];
    }

    /**
     * Generate colletion of all current and future semesters currently in database.
     *
     * @return Semester[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function currentList($shift_for_transition_period = false) {
        return self::where('end', '>=', self::today($shift_for_transition_period))->get(['id']);
    }

    public static function futureList($shift_for_transition_period = false) {
        return self::where('end', '>', self::current($shift_for_transition_period)->end);
    }

    public static function next($shift_for_transition_period = false) {
        $day_in_new_semester = (new Carbon(self::current($shift_for_transition_period)->end))->addDays(1);
        return (new SemesterController())->getSemester($day_in_new_semester);
    }

    /**
     * Checks whether we are in the transition period between semesters
     *
     * @return bool
     */
    public static function inTransition() {
        return (new Carbon(self::current()->end))->subDays(config('semester.transition_period'))->isPast();
    }

    /**
     *
     *
     * @param bool $shift_for_transition_period
     * @return Carbon
     */
    public static function today($shift_for_transition_period = false) {
        if ($shift_for_transition_period && self::inTransition()) {
            return (new Carbon(self::current(false)->end))->addDays(1);
        } else {
            return Carbon::today();
        }
    }

    public static function last() {
        return Semester::orderBy('end', 'desc')->firstOrFail();
    }
}
