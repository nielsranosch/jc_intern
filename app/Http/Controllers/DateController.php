<?php

namespace App\Http\Controllers;

use App\Models\Birthday;
use App\Models\Gig;
use App\Models\Rehearsal;
use App\Models\User;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Request;
use MaddHatter\LaravelFullcalendar\Event;
use MaddHatter\LaravelFullcalendar\Facades\Calendar;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
    protected static function ical_headers(string $calendar_name) {
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
     * Renders an empty calendar. Useful for purging a user's calendar when they are no longer allowed to be subscribed.
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function emptyIcal() {
        $calendar_name = 'jazzchor_expired_calendar';
        $cache_key = 'render_ical_' . $calendar_name;

        $ical = cache_atomic_lock_provider($cache_key, function () use ($calendar_name) {
            $vCalendar = new \Eluceo\iCal\Component\Calendar($calendar_name);
            $vCalendar->setName(trans('date.empty_ical_title'));
            $vCalendar->setDescription(trans('date.empty_ical_description'));
            return $vCalendar->render();
        }, Carbon::now()->addYear());

        // Carrying status code 410 is the best choice because it instructs the client to purge the ressource and never ask again.
        // However, some clients don't really do that. Unfortunately, we don't have time to investigate which clients react in what why to which error codes.
        // Therefore, we just randomly throw some response codes at them hoping that one will make them purge the calendar eventually.
        switch (rand(1,12)) {
            case 1: case 2: case 3:
                $status_code = 200; // 'Success', but purges the calendar. Weighted higher because it ensures an empty calendar on the client.
                break;
            case 4:
                $status_code = 301; // 'Moved permanently', but to an unknown location
                break;
            case 5:
                $status_code = 403; // 'Forbidden'
                break;
            case 6:
                $status_code = 404; // 'Not found'
                break;
            default:
                $status_code = 410; // 'Gone'
                break;
        }


        return response($ical, $status_code)->setExpires(Carbon::now('UTC')->addYears(10))
            ->withHeaders(self::ical_headers($calendar_name));
    }

    /**
     * Method to render an ICAL calender.
     *
     * @return mixed
     */
    public function renderIcal() {
        /*
         * It is unclear in which cases we want to abort(403); and in which cases we want to return $this->emptyIcal();
         * Both have advantages and disadvantages.
         *
         * TODO: the user authetification part should to moved to some middleware
         */

        if (!Request::has('user_id') || !Request::has('key') || !Request::has('req_key')) {
            //abort(403);
            return $this->emptyIcal();
        }

        $input_user_id = (String) Request::input('user_id');
        $input_key = (String) Request::input('key');
        $input_req_key = (String) Request::input('req_key');

        if (strlen($input_user_id) < 20 || strlen($input_key) < 20 || strlen($input_req_key) < 3) {
            //abort(403);
            return $this->emptyIcal();
        }

        try {
            $user = User::where('pseudo_id', '=', $input_user_id)->firstOrFail(['id', 'pseudo_id', 'pseudo_password', 'last_echo', 'first_name']);
        } catch (ModelNotFoundException $e) {
            //abort(403);
            return $this->emptyIcal();
        }

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

        $generated_key = generate_calendar_password_hash($user, $input_req_key, array_keys($date_types));
        if ($input_key !== $generated_key) {
            //abort(403);
            return $this->emptyIcal();
        }

        if (!$user->isActive()) {
            // User was successfully authenticated, but is no longer allowed to sync a calendar. We purge their calendar.
            return $this->emptyIcal();
        }


        if (empty($date_types)) {
            // This handles subscriptions that were made through the 'link rel="alternate" type="text/calendar"'-attribute of our layout.
            // Specifying the date-types there is an option, but not a very good one
            $date_types = self::$date_types;
        }

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

            $vCalendar = new \Eluceo\iCal\Component\Calendar('jazzchor_' . $calendar_name);
            $vCalendar->setName($shortDescription);
            $vCalendar->setDescription($shortDescription);
            foreach ($dates as $date) {
                $vEvent = new \Eluceo\iCal\Component\Event();
                $vEvent
                    ->setDtStart($date->getStart())
                    ->setDtEnd($date->getEnd())
                    ->setNoTime($date->isAllDay())
                    ->setSummary($date->getTitle())
                    ->setDescription($date->description);

                if (method_exists($date, 'isAttending')) {
                    $attendance = $date->isAttending($user);
                    if (!empty($attendance)) {
                        $vEvent->setStatus(self::$convert_attendance_eluceo[$attendance]);

                        if ($attendance === 'no') {
                            $vEvent->setSummary($date->getTitle() . ' – ' . trans('date.not-going'));
                            $vEvent->setDescription(trans('date.user_not_attending') . "\n" . $date->description);
                        }
                    }
                }

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
