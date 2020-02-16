<?php

namespace App\Http\Middleware;

use App\Http\Controllers\DateController;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Auth;

class AuthenticateCalendarSync
{
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
            ->withHeaders(DateController::ical_headers($calendar_name));
    }

    /**
     * Handle an incoming request.
     * Will allow access based on area. If none is given, the default area 'configure' is set.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  String $area     The guarded area.
     * @return mixed
     */
    public function handle($request, Closure $next) {
        if (Auth::guard('calendarSync')->once($request->all())) {
            return $next($request);
        } else {
            return $this->emptyIcal();
        }
    }
}
