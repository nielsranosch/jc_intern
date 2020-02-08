<?php

namespace App\Http\Controllers;

use App\Models\Birthday;
use App\Models\Gig;
use App\Models\Rehearsal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Request;
use MaddHatter\LaravelFullcalendar\Event;
use MaddHatter\LaravelFullcalendar\Facades\Calendar;
use Illuminate\Support\Facades\Auth;


class DateController extends Controller {
    // There are multiple ways to display the dates.
    protected static $view_types = [
        'list'     => 'listIndex',
        'calendar' => 'calendarIndex',
    ];

    // These are the types our dates can be in.
    protected static $date_types = [
        'rehearsals' => Rehearsal::class,
        'gigs'       => Gig::class,
        'birthdays'  => Birthday::class,
    ];

    // Statuses that the UI can filter by.
    protected static $date_statuses = [
        'going',
        'not-going',
        'maybe-going',
        'unanswered'
    ];

    /**
     * To be able pass our attendances to the iCal correctly, we need to convert them to Eluceo's format.
     * We use a static array for now, maybe a proper function would be better.
     */
    public static $convert_attendance_eluceo = [
        'yes' => \Eluceo\iCal\Component\Event::STATUS_CONFIRMED,
        'no' => \Eluceo\iCal\Component\Event::STATUS_CANCELLED,
        'maybe' => \Eluceo\iCal\Component\Event::STATUS_TENTATIVE
    ];

    /**
     * Default header array for iCals
     *
     * @param string $calendar_name
     * @return array
     */
    public static function ical_headers(string $calendar_name) {
        return [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="calendar_'.$calendar_name.'.ics"'
        ];
    }

    /**
     * DateController constructor.
     */
    public function __construct() {
        $this->middleware('auth', ['except' => ['renderIcal']]);
    }

    /**
     * Get the supported types of displaying the dates.
     *
     * @return array
     */
    public static function getViewTypes() {
        return array_keys(self::$view_types);
    }

    /**
     * Returns available date types as an arrays of strings (str_singular was applied to)
     *
     * @return array
     */
    public static function getDateTypes() {
        return array_map('Str::singular', array_keys(self::$date_types));
    }

    public static function getDateStatuses() {
        return self::$date_statuses;
    }


    /**
     * Display a listing of the resource.
     *
     * Either renders a list view (default) or a calendar view of the dates. Can be filtered by Input.
     *
     * @param String $view_type
     * @return \Illuminate\Http\Response
     */
    public function index ($view_type = 'list') {
        // If we have no valid parameter we take the default.
        if (!array_key_exists($view_type, self::$view_types)) {
            $view_type = self::$view_types[0];
        }

        $view_variables = [];

        $view_variables['override_types'] = [];
        if (Request::has('hideByType') && is_array(Request::input('hideByType')) && (count(Request::input('hideByType')) > 0 )) {
            $view_variables['override_types'] = Request::input('hideByType');
            $view_variables['override_types'] = array_intersect(self::getDateTypes(), $view_variables['override_types']); // Because never trust the client!
        }

        $view_variables['override_statuses'] = [];
        if (Request::has('hideByStatus') && is_array(Request::input('hideByStatus')) && (count(Request::input('hideByStatus')) > 0 )) {
            $view_variables['override_statuses'] = Request::input('hideByStatus');
            $view_variables['override_statuses'] = array_intersect(self::getDateStatuses(), $view_variables['override_statuses']); // Because never trust the client!
        }

        // showAll overrides all hideBy's
        $view_variables['override_show_all'] = Request::has('showAll') && 'true' === Request::input('showAll');

        // Prepare rest of view variables.
        // Always show calender with old dates, too.
        $view_variables = array_merge($view_variables, [
            'date_types'        => $this->getDateTypes(),
            'date_statuses'     => $this->getDateStatuses(),
            'view_types'        => $this->getViewTypes(),
        ]);

        // Generate new view by calling view type index.
        $view = call_user_func_array(
            [
                $this,
                self::$view_types[$view_type]
            ],
            [
                'dates'          => $this->getDates(self::$date_types, 'calendar' === $view_type, true),
                'view_variables' => $view_variables
            ]
        );

        // If the call didn't work out: Redirect to date index with errors.
        if (false !== $view) {
            return $view;
        } else {
            return redirect()->route('index', $view_variables)->withErrors(trans('date.view_type_not_found'));
        }
    }

    /**
     * Render the calender view of the given dates.
     *
     * @param $dates
     * @param array $view_variables
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function calendarIndex ($dates, array $view_variables) {
        foreach ($dates as $date) {
            // Prepare the applicable filters for javascript
            $date->getApplicableFilters();
        }

        $view_variables['calendar'] = Calendar::addEvents($dates);
        $view_variables['calendar']->setId('dates');

        return view('date.calendar', $view_variables);
    }

    /**
     * Render the list view of the given dates.
     *
     * @param \Illuminate\Support\Collection $dates
     * @param array $view_variables
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function listIndex (\Illuminate\Support\Collection $dates, array $view_variables) {
        $view_variables['dates'] = $dates->sortBy(function (Event $date) {
            return Carbon::now()->diffInSeconds($date->getStart(), false);
        });

        return view('date.list', $view_variables);
    }

    /**
     * @param array $date_types
     * @param bool $with_old
     * @param bool $with_attendances
     * @return \Illuminate\Support\Collection
     */
    private function getDates (array $date_types, bool $with_old = false, bool $with_attendances = true) {
        $dates = new Collection();

        foreach ($date_types as $set) {
            $dates->add(call_user_func_array([$set, 'all'], [['*'], $with_old, $with_attendances]));
        }

        return $dates->flatten();
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function calendarSync() {
        return view('date.calendar_sync', [
            'date_types' => array_keys(self::$date_types)
        ]);
    }

    /**
     * Method to render an ICAL calender.
     *
     * @return mixed
     */
    public function renderIcal() {
        $date_types = [];
        if (Request::has('show_types') && is_array(Request::input('show_types')) && (count(Request::input('show_types')) > 0 )) {
            $show_types = Request::input('show_types');

            foreach (self::$date_types as $key => $value) {
                // For now, we just ignore unknown elements from the GET-array.
                if (in_array($key, $show_types)) {
                    $date_types[$key] = $value;
                }
            }
        }

        if (empty($date_types)) {
            // This handles subscriptions that were made through the 'link rel="alternate" type="text/calendar"'-attribute of our layout.
            // Specifying the date-types there is an option, but not a very good one
            $date_types = self::$date_types;
        }

        $user = Auth::user();
        $calendar_id = $user->id . '_' . implode('-', array_keys($date_types));
        $calendar_name = implode('-', array_keys($date_types)) . '_' . $user->pseudo_id;

        // Only re-render every 4 hours to serve annoying clients slightly faster
        // Also, this makes it so the UIDs generated by Eluceo-iCal don't change on every request
        // Use UTC because we want to use it for HTTP headers.
        $expiry_time = Carbon::now('UTC')->addHours(4);

        $cache_key = 'render_ical_' . $calendar_id;
        $ical = cache_atomic_lock_provider($cache_key, function () use ($date_types, $calendar_name, $user) {

            $dates = $this->getDates($date_types);

            $calendars_title_merge = implode(' ' . trans('date.and') . ' ', array_map(function ($value) {
                return trans('date.' . $value);
            }, array_keys($date_types)));
            $shortDescription = trans('date.ical_title', ['name' => $user->first_name, 'calendars' => $calendars_title_merge]);

            $vCalendar = new \Eluceo\iCal\Component\Calendar(config('app.domain') . '_' . $calendar_name);
            $vCalendar->setName($shortDescription);
            $vCalendar->setDescription($shortDescription);

            foreach ($dates as $date) {
                // Make sure uniqid are not freshly generated on every request. This makes syncing on clients more efficient.
                $event_sync_id = 'date_ical_uniqid_' . $date->getShortName() . '-' . $date->id . '_user-' . $user->pseudo_id;
                $uniqid = md5(config('app.domain') . $event_sync_id) . '@' . config('app.domain');

                $vEvent = new \Eluceo\iCal\Component\Event($uniqid);
                $vEvent
                    ->setDtStart($date->getStart())
                    ->setDtEnd($date->getEnd())
                    ->setNoTime($date->isAllDay())
                    ->setSummary($date->getTitle())
                    ->setDescription($date->description);

                // The sequence number determines if an event needs to be resynced. Since we don't keep track how often an event has changed,
                // we simply use the unix timestamp of the modification date.
                // Fun fact: This value will be too large for a regular iCal-integer on Tuesday, 19. January 2038 03:14:07 GMT
                $sequence = $date->updated_at ? $date->updated_at->timestamp : null;

                if (method_exists($date, 'isAttending')) {
                    // If a user changes their attendance, we need to resync the event.
                    if (null !== $date->getAttendance($user) && null !== $date->getAttendance($user)->updated_at) {
                        $sequence = max($sequence, $date->getAttendance($user)->updated_at->timestamp);
                    }

                    $attendance = $date->isAttending($user);
                    if (!empty($attendance)) {
                        $vEvent->setStatus(self::$convert_attendance_eluceo[$attendance]);

                        if ($attendance === 'no') {
                            $vEvent->setSummary($date->getTitle() . ' – ' . trans('date.not-going'));
                            $vEvent->setDescription(trans('date.user_not_attending') . "\n" . $date->description);
                        }
                    }
                }

                // To make the sequence numbers a little smaller, we subtract the timestamp of our very first commit.
                // Fun fact: After this, the resulting value will be too large for a regular iCal-integer after Wednesday, 5. April 2084 03:14:07 GMT
                if ($sequence === null || $sequence < 1458259200) {
                    $sequence = 0;
                } else {
                    $sequence -= 1458259200;
                }

                // This ensures that birthdays get resynced when they are shifted to a new year.
                $sequence += $date->getStart()->year;

                $vEvent->setSequence($sequence);

                if (true === $date->hasPlace()) {
                    $vEvent->setLocation($date->place);
                }
                $vCalendar->addComponent($vEvent);
            }

            return $vCalendar->render();
        }, $expiry_time);

        return response($ical)->setExpires($expiry_time)->withHeaders(self::ical_headers($calendar_name));
    }
}
