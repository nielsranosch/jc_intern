<?php

namespace App\Models;

use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use MaddHatter\LaravelFullcalendar\IdentifiableEvent;

/**
 * App\Models\Rehearsal
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property \Carbon\Carbon $start
 * @property \Carbon\Carbon $end
 * @property string|null $place
 * @property int $voice_id
 * @property int $semester_id
 * @property bool $binary_answer
 * @property bool $mandatory
 * @property float $weight
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RehearsalAttendance[] $rehearsal_attendances
 * @property-read \App\Models\Semester $semester
 * @property-read \App\Models\Voice $voice
 * @method static Builder|Rehearsal whereBinaryAnswer($value)
 * @method static Builder|Rehearsal whereCreatedAt($value)
 * @method static Builder|Rehearsal whereDescription($value)
 * @method static Builder|Rehearsal whereEnd($value)
 * @method static Builder|Rehearsal whereId($value)
 * @method static Builder|Rehearsal whereMandatory($value)
 * @method static Builder|Rehearsal wherePlace($value)
 * @method static Builder|Rehearsal whereSemesterId($value)
 * @method static Builder|Rehearsal whereStart($value)
 * @method static Builder|Rehearsal whereTitle($value)
 * @method static Builder|Rehearsal whereUpdatedAt($value)
 * @method static Builder|Rehearsal whereVoiceId($value)
 * @method static Builder|Rehearsal whereWeight($value)
 * @mixin \Eloquent
 */
class Rehearsal extends \Eloquent implements IdentifiableEvent {
    use Event;

    protected $dates = ['start', 'end'];

    protected $calendar_options = [
        'className' => 'event-rehearsal',
        'url' => ''
    ];

    protected $casts = [
        'mandatory' => 'boolean',
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
        'binary_answer',
        'mandatory',
        'weight',
        'semester_id',
        'voice_id',
    ];

    private $user_attendances = [];

    public function semester() {
        return $this->belongsTo('App\Models\Semester');
    }

    public function voice() {
        return $this->belongsTo('App\Models\Voice');
    }

    public function rehearsal_attendances() {
        return $this->hasMany('App\Models\RehearsalAttendance');
    }

    public function users() {
        $users = new Collection();

        foreach ($this->rehearsal_attendances as $rehearsal_attendance) {
            $users->put($rehearsal_attendance->user->id, $rehearsal_attendance->user);
        }

        return $users;
    }

    public static function getShortName() {
        return 'rehearsal';
    }

    public static function getShortNamePlural() {
        return 'rehearsals';
    }

    public static function isMissable() {
        return true;
    }

    /**
     * Tells you if there is a non-standard weight attached to this rehearsal
     *
     * @return bool
     */
    public function isWeighted() {
        return $this->weight != 1.0;
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
        $this->calendar_options['url'] = route('rehearsals.show', ['rehearsal' => $this->id]);
        
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
            $this->user_attendances[$user->id] = $this->rehearsal_attendances->filter(
                function ($value) use ($user) { return $value->user->id == $user->id; }
            )->first();
        }
        return $this->user_attendances[$user->id];
    }

    protected function getAttendances() {
        return $this->rehearsal_attendances;
    }

}
