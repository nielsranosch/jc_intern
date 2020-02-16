<?php

namespace App\Models;

use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use MaddHatter\LaravelFullcalendar\IdentifiableEvent;

/**
 * App\Models\Gig
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property \Carbon\Carbon $start
 * @property \Carbon\Carbon $end
 * @property string $place
 * @property bool $binary_answer
 * @property int $semester_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GigAttendance[] $gig_attendances
 * @property-read \App\Models\Semester $semester
 * @method static Builder|Gig whereBinaryAnswer($value)
 * @method static Builder|Gig whereCreatedAt($value)
 * @method static Builder|Gig whereDescription($value)
 * @method static Builder|Gig whereEnd($value)
 * @method static Builder|Gig whereId($value)
 * @method static Builder|Gig wherePlace($value)
 * @method static Builder|Gig whereSemesterId($value)
 * @method static Builder|Gig whereStart($value)
 * @method static Builder|Gig whereTitle($value)
 * @method static Builder|Gig whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Gig extends \Eloquent implements IdentifiableEvent {
    use Event;

    protected $dates = ['start', 'end'];

    protected $calendar_options = [
        'className' => 'event-gig',
        'url' => ''
    ];

    protected $casts = [
        'binary_answer' => 'boolean'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'start',
        'end',
        'place',
        'semester_id',
        'binary_answer',
    ];

    private $user_attendances = [];

    public function gig_attendances() {
        return $this->hasMany('App\Models\GigAttendance');
    }

    public function users() {
        $users = new Collection();

        foreach ($this->gig_attendances as $gig_attendance) {
            $users->put($gig_attendance->user->id, $gig_attendance->user);
        }

        return $users;
    }

    public function semester() {
        return $this->belongsTo('App\Models\Semester');
    }

    public static function getShortName() {
        return 'gig';
    }

    public static function getShortNamePlural() {
        return 'gigs';
    }

    /**
     * Get the event's title
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    public function getId() {
        return $this->id;
    }

    /**
     * Optional FullCalendar.io settings for this event
     *
     * @return array
     */
    public function getEventOptions() {
        $this->calendar_options['url'] = route('gigs.show', ['gig' => $this->id]);

        return $this->calendar_options;
    }

    /**
     * @param User $user
     * @return Attendance
     */
    public function getAttendance(User $user) {
        // Try saving the user's attendance in array for quicker access and no extra DB query.
        if (!Arr::has($this->user_attendances, $user->id)) {
            // We use the collection here, because it has a huge! impact on loading speed.
            $this->user_attendances[$user->id] = $this->gig_attendances->filter(
                function ($value) use ($user) { return $value->user->id == $user->id; }
            )->first();
        }

        return $this->user_attendances[$user->id];
    }

    protected function getAttendances() {
        return $this->gig_attendances;
    }
}
