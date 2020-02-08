<?php
/**
 * Guard to for user authentication when syncing a calendar
 */

namespace App\Guards;

use App\Models\User;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CalendarSyncGuard implements Guard
{

    use GuardHelpers;

    protected $lastAttempted;

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * Login for this request
     *
     * @param array $crendentials
     * @return bool
     */
    public function once(array $crendentials = []) {
        if ($this->validate($crendentials)) {
            $this->setUser($this->lastAttempted);
            return true;
        }
        return false;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $this->lastAttempted = null;

        if (!array_key_exists('user_id', $credentials) || !array_key_exists('key', $credentials) || !array_key_exists('req_key', $credentials)) {
            return false;
        }

        $input_user_id = (String) $credentials['user_id'];
        $input_key = (String) $credentials['key'];
        $input_req_key = (String) $credentials['req_key'];

        if (strlen($input_user_id) < 20 || strlen($input_key) < 20 || strlen($input_req_key) < 3) {
            return false;
        }

        try {
            $user = User::where('pseudo_id', '=', $input_user_id)->firstOrFail(['id', 'pseudo_id', 'pseudo_password', 'last_echo', 'first_name']);
            $this->lastAttempted = $user;
        } catch (ModelNotFoundException $e) {
            return false;
        }

        $date_types = [];
        if (array_key_exists('show_types', $credentials) && is_array($credentials['show_types']) && (count($credentials['show_types']) > 0 )) {
            $date_types = $credentials['show_types'];
        }

        $generated_key = generate_calendar_password_hash($user, $input_req_key, $date_types);
        if ($input_key !== $generated_key) {
            return false;
        }

        if (!$user->isActive()) {
            return false;
        }

        return true;
    }

}