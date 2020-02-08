<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use MaddHatter\LaravelFullcalendar\Event;

class Birthday extends Model implements Event {
    use Date;

    // This is for carbon dates.
    protected $dates = ['start', 'end'];

    protected $calendar_options = [
        'className' => 'event-birthday'
    ];

    protected $title;
    protected $start;
    protected $end;

    protected $user;

    public function __construct(User $user = null) {
        $this->title = trans('form.birthday') . " " . $user->first_name . ' ' . $user->last_name;
        $this->user = $user;

        // Date arithmetic: Set to current year, add one year if date is more than one week ago.
        $dateCurrentYear = new Carbon($user->birthday);
        $dateCurrentYear->year = date('Y');
        if ($dateCurrentYear->lt(Carbon::now()->subWeek(1))) {
            $dateCurrentYear->addYear();
        }

        $this->start = $dateCurrentYear;
        $this->end   = $dateCurrentYear;

        $this->setApplicableFilters();
    }

    public static function getShortName() {
        return 'birthday';
    }

    public static function getShortNamePlural() {
        return 'birthdays';
    }

    /**
     * Get the event's title
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    public function getUser() {
        return $this->user;
    }

    public function getIdAttribute() {
        return $this->user->id;
    }

    public function getDescriptionAttribute() {
        return '';
    }

    public function getUpdatedAtAttribute() {
        return $this->user->updated_at;
    }

    public function getCreatedAtAttribute() {
        return $this->user->created_at;
    }

    /**
     * Is it an all day event?
     *
     * @return bool
     */
    public function isAllDay() {
        return true;
    }

    /**
     * Check if this date has a place
     *
     * @return Boolean
     */
    public function hasPlace() {
        return false;
    }

    /**
     * Optional FullCalendar.io settings for this event
     *
     * @return array
     */
    public function getEventOptions() {
        return $this->calendar_options;
    }

    /**
     * Returns all Birthdays (unsorted, can use ->sortBy(function($item) {return $item->getStart();}))
     *
     * @return Collection
     */
    public static function all($columns = ['*']) {
        $collection = new Collection();
        $users = User::all();

        foreach ($users as $user) {
            if (!empty($user->birthday)) {
                $collection->add(new Birthday($user));
            }
        }

        return $collection;
    }
}
