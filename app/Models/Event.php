<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * Trait Event
 * @package App\Models
 */
trait Event {
    use Date {
        Date::setApplicableFilters as private setDateApplicableFilters;
    }

    /**
     * Extend the Date's applicable filters by the attendance of the event.
     */
    protected function setApplicableFilters() {
        if ($this->needsAnswer()) {
            if ($this->hasAnswered()) {
                switch ($this->isAttending()) {
                    case 'yes':
                        $this->applicable_filters[] = 'going';
                        break;
                    case 'maybe':
                        $this->applicable_filters[] = 'maybe-going';
                        break;
                    case 'no':
                        $this->applicable_filters[] = 'not-going';
                        break;
                }
            } else {
                $this->applicable_filters[] = 'unanswered';
            }
        }
        $this->setDateApplicableFilters();
    }

    /**
     * Returns true if only 'yes' and 'no' are acceptable answers for this date.
     *
     * @return boolean
     */
    public function hasBinaryAnswer() {
        if (isset($this->binary_answer)) {
            return $this->binary_answer;
        }

        return true;
    }

    /**
     * Returns answer, if a user (or on null the authenticated user) has answered this Date.
     *
     * @param User|null $user
     * @return bool
     */
    public function isAttending(User $user = null) {
        if (null === $user) {
            $user = \Auth::user();
        }

        $attendance = $this->getAttendance($user);

        if (null === $attendance || null === $attendance->attendance) return '';
        return \Config::get('enums.attendances_reversed')[$attendance->attendance];
    }

    /**
     * Returns true, if a user (or on null the authenticated user) has answered this Date.
     *
     * @param User|null $user
     * @return bool
     */
    public function hasAnswered(User $user = null) {
        if (null === $user) {
            $user = \Auth::user();
        }

        if (null === $user) { // Needed for seeding
            return false;
        }

        $attendance = $this->getAttendance($user);

        if (null === $attendance || null === $attendance->attendance) {
            return false;
        } else if ($this->hasBinaryAnswer()) {
            return \Config::get('enums.attendances')['maybe'] !== $attendance->attendance;
        } else {
            return true;
        }
    }

    /**
     * @param User $user
     * @return Attendance
     */
    abstract protected function getAttendance(User $user);

    /**
     * Should return true, if a user has already commented on an attendance.
     *
     * @param User|null $user
     * @return boolean
     */
    public function hasCommented(User $user = null) {
        if (null === $user) {
            $user = \Auth::user();
        }

        $attendance = $this->getAttendance($user);

        // Make use of lazy evaluation (attendance will be set if second part is evaluated).
        if (null !== $attendance && !empty($attendance->comment)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the comment a user has given on the Event.
     *
     * @param User|null $user
     * @return bool
     */
    public function getComment(User $user = null) {
        if (null === $user) {
            $user = \Auth::user();
        }

        $attendance = $this->getAttendance($user);

        if (null === $attendance) {
            return '';
        } else {
            return $attendance->comment;
        }
    }

    /**
     * All Events need an answer of the user.
     *
     * @return bool
     */
    public function needsAnswer() {
        return true;
    }

    abstract protected function getAttendances();

    /**
     * Get the number of people who will attend an event (filtered by voice if given)
     *
     * @param User[]|null $users
     * @return int
     */
    public function getAttendanceCount($users = null) {
        $attendances = $this->getAttendances()->filter(function ($value) {
            return $value->attendance === \Config::get('enums.attendances')['yes'];
        });

        if (null !== $users) {
            if ($users->count() < 1) {
                return 0;
            }

            $attendances = $attendances->whereIn('user.id', $users->keyBy('id')->keys()->all());
        }

        return $attendances->count();
    }

    protected static $current_events = null;

    /**
     * Load events from database according to given options
     *
     * @param array $columns
     * @param bool $with_old include prior to today
     * @param bool $with_attendances
     * @param bool $with_new include after now
     * @param bool $current_only restrict to current semester
     * @return \Eloquent[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function all($columns = ['*'], $with_old = false, $with_attendances = false, $with_new = true, $current_only = false) {
        // The most common query is being stored to reduce database access
        $cache_this = false;
        if ($with_old === true && $with_attendances === false && $with_new === false && $current_only === true) {
            if (self::$current_events !== null) {
                return self::$current_events;
            } else {
                $cache_this = true;
            }
        }

        $query = parent::orderBy('start');

        if ($with_attendances) {
            $query = $query->with(self::getShortName() . '_attendances.user');
        }

        if (!$with_old) {
            $query->where('end', '>=', Carbon::today());
        }

        if (!$with_new) {
            $query->where('end', '<=', Carbon::now());
        }

        if ($current_only) {
            $query->where('semester_id', '=', Semester::current()->id);
        }

        $result = $query->get($columns);

        if ($cache_this) {
            self::$current_events = $result;
        }

        return $result;
    }
}
