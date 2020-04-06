<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;

abstract class AttendanceController extends Controller {
    protected $validation = [
        'comment' => 'string|max:255',
        'internal_comment' => 'string|max:255',
    ];

    /**
     * RehearsalAttendanceController constructor.
     *
     * Only
     */
    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Retrieve an event by the given ID.
     *
     * @param $event_id
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    protected abstract function getEventById ($event_id);

    /**
     * This function can be overridden by children to add more data to attendance.
     * Usually, this function does only return an empty array.
     *
     * @param Request $request
     * @return array
     */
    protected function prepareAdditionalData (Request $request) {
        return [];
    }

    /**
     * Shorthand "I will attend $event_id" for routes.
     *
     * @param Request $request
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function attendSelf(Request $request, $event_id) {
        return $this->changeOwnAttendance($request, $event_id, 'yes');
    }

    /**
     * Shorthand "I might attend $event_id" for routes.
     *
     * @param Request $request
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function maybeSelf(Request $request, $event_id) {
        return $this->changeOwnAttendance($request, $event_id, 'maybe');
    }

    /**
     * Shorthand "I won't attend $event_id" for routes.
     *
     * @param Request $request
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function excuseSelf(Request $request, $event_id) {
        return $this->changeOwnAttendance($request, $event_id, 'no');
    }

    /**
     * Function to change if user with $user_id will attend.
     *
     * @param Request $request
     * @param Event $event
     * @param Integer $user_id
     * @param String $attendance  Can also be taken from the request if null.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function changeEventAttendance (Request $request, $event, $user_id, $attendance = null) {
        // Try to get the user.
        if (null === $user_id) {
            if ($request->wantsJson()) {
                return \Response::json(['success' => false, 'message' => trans('date.user_not_given')]);
            } else {
                return back()->withErrors(trans('date.user_not_given'));
            }
        }

        $user = User::find($user_id);

        if (null === $user) {
            if ($request->wantsJson()) {
                return \Response::json(['success' => false, 'message' => trans('date.user_not_found')]);
            } else {
                return back()->withErrors(trans('date.user_not_found'));
            }
        }

        return $this->changeUserEventAttendance($request, $event, $user, $attendance);
    }

    /**
     * Function to change the currently logged in user's attendance for a given event.
     *
     * @param Request $request
     * @param integer $event_id
     * @param string $attendance  Can also be taken from the request if null.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function changeOwnAttendance (Request $request, $event_id, $attendance = null) {
        // Retrieve event by child methods.
        $event = $this->getEventById($event_id);

        // Check if there is an event.
        if (null === $event) {
            if ($request->wantsJson()) {
                return \Response::json(['success' => false, 'message' => trans('date.gig_not_found')]);
            } else {
                return back()->withErrors(trans('date.gig_not_found'));
            }
        }

        // Get logged in user and prepare data for saving. No need for check, can't reach this without the user.
        $user = \Auth::user();
        return $this->changeUserEventAttendance($request, $event, $user, $attendance);
    }

    /**
     * Change event attendance for given User.
     *
     * @param Request $request
     * @param $event
     * @param User $user
     * @param string $attendance  Can also be taken from the request if null.
     * @return $this|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    private function changeUserEventAttendance(Request $request, $event, User $user, $attendance = null) {
        // Check if the User hit the delete button
        if ($request->filled('submit') && trans('form.delete') === $request->get('submit')){
            if(!$this->deleteComment($event, $user)){
                $message = trans('date.comment_delete_error');
                if($request->wantsJson()){
                    return \Response::json(['success' => false, 'message' => $message]);
                }else{
                    return back()->withErrors($message);
                }
            }
            $message = trans('date.comment_delete_success');
            if($request->wantsJson()){
                return \Response::json(['success' => true, 'message' => $message]);
            }else{
                $request->session()->flash('message_success', $message);
                return back();
            }
        }


        // Check if we have an attendance state given.
        if (null === $attendance) {
            if ($request->filled('attendance')) {
                $attendance = $request->get('attendance');
            }
            // No attendance given is possible, for example to add comment.
        }

        // Prepare new data for database.
        $data = [];
        if (null !== $attendance) {
            $data['attendance'] = \Config::get('enums.attendances')[$attendance];
        }

        // Only include comments if we have some.
        if ($request->filled('comment')) {
            $data['comment'] = $request->get('comment');
        }
        if ($request->filled('internal_comment')) {
            $data['internal_comment'] = $request->get('internal_comment');
        }

        $data = array_merge($data, $this->prepareAdditionalData($request));
        $this->validate($request, $this->validation);

        // Change the attendance according to the subclass.
        if (!$this->storeAttendance($event, $user, $data)) {
            // Attendance was not saved.
            if (null === $attendance) {
                $message = trans('date.comment_error');
            } else {
                $message = 'yes' == $attendance ? trans('date.attendance_error') : trans('date.excuse_error');
            }
            if ($request->wantsJson()) {
                return \Response::json(['success' => false, 'message' => $message]);
            } else {
                return back()->withErrors($message);
            }
        }

        // If we arrive here everything went fine.
        if (null === $attendance) {
            $message = trans('date.comment_success');
        } else {
            $message = 'yes' == $attendance ? trans('date.attendance_saved') : trans('date.excuse_saved');
        }
        if ($request->wantsJson()) {
            return \Response::json(['success' => true, 'message' => $message]);
        } else {
            $request->session()->flash('message_success', $message);
            return back();
        }
    }

    /**
     * Helper to update or create an attendance.
     *
     * @param $event
     * @param User $user
     * @param array $data
     * @return bool
     */
    abstract protected function storeAttendance($event, User $user, array $data);

    /**
     * Helper to delete a comment.
     * 
     */
    abstract protected function deleteComment($event, User $user);

    /**
     * This function simplifies the routing by choosing the appropriate controller and function to call.
     *
     * @param Request $request
     * @param $events_name
     * @param $event_id
     * @param null $shorthand
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public static function attendanceRouteSwitch(Request $request, $events_name, $event_id, $shorthand = null) {
        // The right controller gets chosen based on the event name (in plural for routes!).
        $controller = null;

        if ($events_name == 'gigs') {
            $controller = new GigAttendanceController();
        } else if ($events_name == 'rehearsals') {
            $controller = new RehearsalAttendanceController();
        } else {
            // Not a known type of event.
            if ($request->wantsJson()) {
                return \Response::json(['success' => false, 'message' => trans('date.route_invalid')]);
            } else {
                return back()->withErrors(trans('date.route_invalid'));
            }
        }

        // We have the right controller now, choose the right method, too.
        switch ($shorthand) {
            case 'attend':
                return $controller->attendSelf($request, $event_id);
                break;
            case 'maybe':
                return $controller->maybeSelf($request, $event_id);
                break;
            case 'excuse':
                return $controller->excuseSelf($request, $event_id);
                break;
            default:
            case 'change':
                return $controller->changeOwnAttendance($request, $event_id);
                break;
        }
    }
}
