<?php
namespace App\Entity;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Annotations\AuditLog;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="station_playlists")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @AuditLog\Auditable
 *
 * @OA\Schema(type="object")
 */
class StationPlaylist
{
    use Traits\TruncateStrings;

    public const DEFAULT_WEIGHT = 3;
    public const DEFAULT_REMOTE_BUFFER = 20;

    public const TYPE_DEFAULT = 'default';
    public const TYPE_SCHEDULED = 'scheduled';
    public const TYPE_ONCE_PER_X_SONGS = 'once_per_x_songs';
    public const TYPE_ONCE_PER_X_MINUTES = 'once_per_x_minutes';
    public const TYPE_ONCE_PER_HOUR = 'once_per_hour';
    public const TYPE_ADVANCED = 'custom';

    public const SOURCE_SONGS = 'songs';
    public const SOURCE_REMOTE_URL ='remote_url';

    public const REMOTE_TYPE_STREAM = 'stream';
    public const REMOTE_TYPE_PLAYLIST = 'playlist';

    public const ORDER_RANDOM = 'random';
    public const ORDER_SHUFFLE = 'shuffle';
    public const ORDER_SEQUENTIAL = 'sequential';

    public const OPTION_INTERRUPT_OTHER_SONGS = 'interrupt';
    public const OPTION_LOOP_PLAYLIST_ONCE = 'loop_once';
    public const OPTION_PLAY_SINGLE_TRACK = 'single_track';
    public const OPTION_MERGE = 'merge';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @OA\Property(example=1)
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="playlists")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="name", type="string", length=200)
     *
     * @Assert\NotBlank()
     * @OA\Property(example="Test Playlist")
     *
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(name="type", type="string", length=50)
     *
     * @Assert\Choice(choices={"default", "scheduled", "once_per_x_songs", "once_per_x_minutes", "once_per_hour", "once_per_day", "custom"})
     * @OA\Property(example="default")
     *
     * @var string
     */
    protected $type = self::TYPE_DEFAULT;

    /**
     * @ORM\Column(name="source", type="string", length=50)
     *
     * @Assert\Choice(choices={"songs", "remote_url"})
     * @OA\Property(example="songs")
     *
     * @var string
     */
    protected $source = self::SOURCE_SONGS;

    /**
     * @ORM\Column(name="playback_order", type="string", length=50)
     *
     * @Assert\Choice(choices={"random", "shuffle", "sequential"})
     * @OA\Property(example="shuffle")
     *
     * @var string
     */
    protected $order = self::ORDER_SHUFFLE;

    /**
     * @ORM\Column(name="remote_url", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="http://remote-url.example.com/stream.mp3")
     *
     * @var string|null
     */
    protected $remote_url;

    /**
     * @ORM\Column(name="remote_type", type="string", length=25, nullable=true)
     *
     * @Assert\Choice(choices={"stream", "playlist"})
     * @OA\Property(example="stream")
     *
     * @var string|null
     */
    protected $remote_type = self::REMOTE_TYPE_STREAM;

    /**
     * @ORM\Column(name="remote_timeout", type="smallint")
     *
     * @OA\Property(example=0)
     *
     * @var int The total time (in seconds) that Liquidsoap should buffer remote URL streams.
     */
    protected $remote_buffer = 0;

    /**
     * @ORM\Column(name="is_enabled", type="boolean")
     *
     * @OA\Property(example=true)
     *
     * @var bool
     */
    protected $is_enabled = true;

    /**
     * @ORM\Column(name="is_jingle", type="boolean")
     *
     * @OA\Property(example=false)
     *
     * @var bool If yes, do not send jingle metadata to AutoDJ or trigger web hooks.
     */
    protected $is_jingle = false;

    /**
     * @ORM\Column(name="play_per_songs", type="smallint")
     *
     * @OA\Property(example=5)
     *
     * @var int
     */
    protected $play_per_songs = 0;

    /**
     * @ORM\Column(name="play_per_minutes", type="smallint")
     *
     * @OA\Property(example=120)
     *
     * @var int
     */
    protected $play_per_minutes = 0;

    /**
     * @ORM\Column(name="play_per_hour_minute", type="smallint")
     *
     * @OA\Property(example=15)
     *
     * @var int
     */
    protected $play_per_hour_minute = 0;

    /**
     * @ORM\Column(name="schedule_start_time", type="smallint")
     *
     * @OA\Property(example=900)
     *
     * @var int
     */
    protected $schedule_start_time = 0;

    /**
     * @ORM\Column(name="schedule_end_time", type="smallint")
     *
     * @OA\Property(example=2200)
     *
     * @var int
     */
    protected $schedule_end_time = 0;

    /**
     * @ORM\Column(name="schedule_days", type="string", length=50, nullable=true)
     *
     * @OA\Property(example="0,1,2,3")
     *
     * @var string
     */
    protected $schedule_days;

    /**
     * @ORM\Column(name="weight", type="smallint")
     *
     * @OA\Property(example=3)
     *
     * @var int
     */
    protected $weight = self::DEFAULT_WEIGHT;

    /**
     * @ORM\Column(name="include_in_requests", type="boolean")
     *
     * @OA\Property(example=true)
     *
     * @var bool
     */
    protected $include_in_requests = true;

    /**
     * @ORM\Column(name="include_in_automation", type="boolean")
     *
     * @OA\Property(example=false)
     *
     * @var bool
     */
    protected $include_in_automation = false;

    /**
     * @ORM\Column(name="backend_options", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="interrupt,loop_once,single_track,merge")
     *
     * @var string
     */
    protected $backend_options = '';

    /**
     * @ORM\Column(name="played_at", type="integer")
     * @AuditLog\AuditIgnore
     *
     * @var int The UNIX timestamp at which a track from this playlist was last played.
     */
    protected $played_at = 0;

    /**
     * @ORM\Column(name="queue", type="array", nullable=true)
     * @AuditLog\AuditIgnore
     *
     * @var array|null The current queue of unplayed songs for this playlist.
     */
    protected $queue;

    /**
     * @ORM\OneToMany(targetEntity="StationPlaylistMedia", mappedBy="playlist", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"weight" = "ASC"})
     * @var Collection
     */
    protected $media_items;

    public function __construct(Station $station)
    {
        $this->station = $station;

        $this->media_items = new ArrayCollection;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @AuditLog\AuditIdentifier
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getShortName(): string
    {
        return Station::getStationShortName($this->name);
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $this->_truncateString($name, 200);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource(string $source): void
    {
        // Reset the playback queue if source is changed.
        if ($source !== $this->source) {
            $this->queue = null;
        }

        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * @param string $order
     */
    public function setOrder(string $order): void
    {
        // Reset the playback queue if order is changed.
        if ($order !== $this->order) {
            $this->queue = null;
        }

        $this->order = $order;
    }

    /**
     * @return null|string
     */
    public function getRemoteUrl(): ?string
    {
        return $this->remote_url;
    }

    /**
     * @param null|string $remote_url
     */
    public function setRemoteUrl(?string $remote_url): void
    {
        $this->remote_url = $remote_url;
    }

    /**
     * @return string
     */
    public function getRemoteType(): ?string
    {
        return $this->remote_type;
    }

    /**
     * @param null|string $remote_type
     */
    public function setRemoteType(?string $remote_type): void
    {
        $this->remote_type = $remote_type;
    }

    /**
     * @return int
     */
    public function getRemoteBuffer(): int
    {
        return $this->remote_buffer;
    }

    /**
     * @param int $remote_buffer
     */
    public function setRemoteBuffer(int $remote_buffer): void
    {
        $this->remote_buffer = $remote_buffer;
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * @param bool $is_enabled
     */
    public function setIsEnabled(bool $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
    }

    /**
     * @return bool
     */
    public function isJingle(): bool
    {
        return $this->is_jingle;
    }

    /**
     * @param bool $is_jingle
     */
    public function setIsJingle(bool $is_jingle): void
    {
        $this->is_jingle = $is_jingle;
    }

    /**
     * @return int
     */
    public function getPlayPerSongs(): int
    {
        return $this->play_per_songs;
    }

    /**
     * @param int $play_per_songs
     */
    public function setPlayPerSongs(int $play_per_songs): void
    {
        $this->play_per_songs = $play_per_songs;
    }

    /**
     * @return int
     */
    public function getPlayPerMinutes(): int
    {
        return $this->play_per_minutes;
    }

    /**
     * @param int $play_per_minutes
     */
    public function setPlayPerMinutes(int $play_per_minutes): void
    {
        $this->play_per_minutes = $play_per_minutes;
    }

    /**
     * @return int
     */
    public function getPlayPerHourMinute(): int
    {
        return $this->play_per_hour_minute;
    }

    /**
     * @param int $play_per_hour_minute
     */
    public function setPlayPerHourMinute(int $play_per_hour_minute): void
    {
        if ($play_per_hour_minute > 59 || $play_per_hour_minute < 0) {
            $play_per_hour_minute = 0;
        }

        $this->play_per_hour_minute = $play_per_hour_minute;
    }

    /**
     * @return int
     */
    public function getScheduleStartTime(): int
    {
        return (int)$this->schedule_start_time;
    }

    /**
     * @param int $schedule_start_time
     */
    public function setScheduleStartTime(int $schedule_start_time): void
    {
        $this->schedule_start_time = $schedule_start_time;
    }

    /**
     * @return int
     */
    public function getScheduleEndTime(): int
    {
        return (int)$this->schedule_end_time;
    }

    /**
     * @param int $schedule_end_time
     */
    public function setScheduleEndTime(int $schedule_end_time): void
    {
        $this->schedule_end_time = $schedule_end_time;
    }

    /**
     * @return int Get the duration of scheduled play time in seconds (used for remote URLs of indeterminate length).
     */
    public function getScheduleDuration(): int
    {
        if (self::TYPE_SCHEDULED !== $this->type) {
            return 0;
        }

        $start_time = self::getDateTime($this->schedule_start_time)
            ->getTimestamp();
        $end_time = self::getDateTime($this->schedule_end_time)
            ->getTimestamp();

        if ($start_time > $end_time) {
            /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
            return 86400 - ($start_time - $end_time);
        }

        return $end_time - $start_time;
    }

    /**
     * @return array|null
     */
    public function getScheduleDays(): ?array
    {
        return (!empty($this->schedule_days)) ? explode(',', $this->schedule_days) : null;
    }

    /**
     * @param array $schedule_days
     */
    public function setScheduleDays($schedule_days): void
    {
        $this->schedule_days = implode(',', (array)$schedule_days);
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        if ($this->weight < 1) {
            return self::DEFAULT_WEIGHT;
        }

        return $this->weight;
    }

    /**
     * @param int $weight
     */
    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * @return bool
     */
    public function getIncludeInRequests(): bool
    {
        return $this->include_in_requests;
    }

    /**
     * Indicates whether this playlist can be used as a valid source of requestable media.
     *
     * @return bool
     */
    public function isRequestable(): bool
    {
        return ($this->is_enabled && $this->include_in_requests);
    }

    /**
     * @param bool $include_in_requests
     */
    public function setIncludeInRequests(bool $include_in_requests): void
    {
        $this->include_in_requests = $include_in_requests;
    }

    /**
     * @return bool
     */
    public function getIncludeInAutomation(): bool
    {
        return $this->include_in_automation;
    }

    /**
     * @param bool $include_in_automation
     */
    public function setIncludeInAutomation(bool $include_in_automation): void
    {
        $this->include_in_automation = $include_in_automation;
    }

    /**
     * @return array
     */
    public function getBackendOptions(): array
    {
        return explode(',', $this->backend_options);
    }

    public function backendInterruptOtherSongs(): bool
    {
        $backend_options = $this->getBackendOptions();
        return in_array(self::OPTION_INTERRUPT_OTHER_SONGS, $backend_options, true);
    }

    public function backendLoopPlaylistOnce(): bool
    {
        $backend_options = $this->getBackendOptions();
        return in_array(self::OPTION_LOOP_PLAYLIST_ONCE, $backend_options, true);
    }

    public function backendPlaySingleTrack(): bool
    {
        $backend_options = $this->getBackendOptions();
        return in_array(self::OPTION_PLAY_SINGLE_TRACK, $backend_options, true);
    }

    public function backendMerge(): bool
    {
        $backend_options = $this->getBackendOptions();
        return in_array(self::OPTION_MERGE, $backend_options, true);
    }

    /**
     * @param array $backend_options
     */
    public function setBackendOptions($backend_options): void
    {
        $this->backend_options = implode(',', (array)$backend_options);
    }

    /**
     * @return int
     */
    public function getPlayedAt(): int
    {
        return $this->played_at;
    }

    /**
     * @param int $played_at
     */
    public function setPlayedAt(int $played_at): void
    {
        $this->played_at = $played_at;
    }

    public function played(): void
    {
        $this->played_at = time();
    }

    /**
     * @return array|null
     */
    public function getQueue(): ?array
    {
        return $this->queue;
    }

    /**
     * @param array|null $queue
     */
    public function setQueue(?array $queue): void
    {
        $this->queue = $queue;
    }

    /**
     * @return Collection
     */
    public function getMediaItems(): Collection
    {
        return $this->media_items;
    }

    /**
     * Indicates whether a playlist is enabled and has content which can be scheduled by an AutoDJ scheduler.
     *
     * @return bool
     */
    public function isPlayable(): bool
    {
        return ($this->is_enabled
            && (self::SOURCE_SONGS !== $this->source || $this->media_items->count() > 0)
            && !$this->backendInterruptOtherSongs()
            && !$this->backendMerge()
            && !$this->backendLoopPlaylistOnce());
    }

    /**
     * Parent function for determining whether a playlist of any type can be played by the AutoDJ.
     *
     * @param Chronos|null $now
     * @param array $recentSongHistory
     * @return bool
     */
    public function shouldPlayNow(Chronos $now = null, array $recentSongHistory = []): bool
    {
        if (null === $now) {
            $now = Chronos::now(new \DateTimeZone($this->getStation()->getTimezone()));
        }

        switch($this->type) {
            case self::TYPE_ONCE_PER_HOUR:
                return $this->shouldPlayNowPerHour($now);
                break;

            case self::TYPE_ONCE_PER_X_SONGS:
                return !$this->wasPlayedRecently($recentSongHistory, $this->getPlayPerSongs());
                break;

            case self::TYPE_ONCE_PER_X_MINUTES:
                return $this->shouldPlayNowPerMinute($now);
                break;

            case self::TYPE_SCHEDULED:
                // If the times match, it's a "play once" playlist.
                if ($this->getScheduleStartTime() === $this->getScheduleEndTime()) {
                    return $this->shouldPlayNowOnce($now);
                }

                return $this->shouldPlayNowScheduled($now);
                break;

            case self::TYPE_ADVANCED:
                return false;
                break;

            case self::TYPE_DEFAULT:
            default:
                return true;
                break;
        }
    }

    /**
     * Returns whether the playlist is scheduled to play according to schedule rules.
     *
     * @param Chronos $now
     * @return bool
     */
    protected function shouldPlayNowScheduled(Chronos $now): bool
    {
        $day_to_check = (int)$now->format('N');
        $current_timecode = (int)$now->format('Hi');

        $schedule_start_time = $this->getScheduleStartTime();
        $schedule_end_time = $this->getScheduleEndTime();

        // Special handling for playlists ending at midnight (hour code "000").
        if (0 === $schedule_end_time) {
            $schedule_end_time = 2400;
        }

        // Handle overnight playlists that stretch into the next day.
        if ($schedule_end_time < $schedule_start_time) {
            if ($current_timecode <= $schedule_end_time) {
                // Check the previous day, since it's before the end time.
                $day_to_check = (1 === $day_to_check) ? 7 : $day_to_check - 1;
            } else if ($current_timecode < $schedule_start_time) {
                // The playlist shouldn't be playing before the start time on the current date.
                return false;
            }
        // Non-overnight playlist check
        } else if ($current_timecode < $schedule_start_time || $current_timecode > $schedule_end_time) {
            return false;
        }

        // Check that the current day is one of the scheduled play days.
        if (!$this->isScheduledToPlayToday($day_to_check)) {
            return false;
        }

        return ($this->backendPlaySingleTrack())
            ? !$this->wasPlayedInLastXMinutes($now, 720)
            : true;
    }

    /**
     * Given a day code (1-7) a-la date('N'), return if the playlist can be played on that day.
     *
     * @param int $day_to_check
     * @return bool
     */
    protected function isScheduledToPlayToday(int $day_to_check): bool
    {
        $play_once_days = $this->getScheduleDays();
        return empty($play_once_days)
            || in_array($day_to_check, $play_once_days);
    }

    /**
     * @param Chronos $now
     * @return bool
     */
    protected function shouldPlayNowPerMinute(Chronos $now): bool
    {
        return !$this->wasPlayedInLastXMinutes($now, $this->getPlayPerMinutes());
    }

    /**
     * @param Chronos $now
     * @return bool
     */
    protected function shouldPlayNowPerHour(Chronos $now): bool
    {
        $current_minute = (int)$now->minute;
        $target_minute = $this->getPlayPerHourMinute();

        if ($current_minute < $target_minute) {
            $target_time = $now->addHour(-1)->minute($target_minute);
        } else {
            $target_time = $now->minute($target_minute);
        }

        $playlist_diff = $target_time->diffInMinutes($now, false);

        if ($playlist_diff < 0 || $playlist_diff > 15) {
            return false;
        }

        return !$this->wasPlayedInLastXMinutes($now, 30);
    }

    /**
     * Returns whether the playlist is scheduled to play once.
     *
     * @param Chronos $now
     * @return bool
     */
    protected function shouldPlayNowOnce(Chronos $now): bool
    {
        if (!$this->isScheduledToPlayToday((int)$now->format('N'))) {
            return false;
        }

        $current_timecode = (int)$now->format('Hi');

        $playlist_play_time = $this->getScheduleStartTime();
        $playlist_diff = $current_timecode - $playlist_play_time;
        if ($playlist_diff < 0 || $playlist_diff > 15) {
            return false;
        }

        return !$this->wasPlayedInLastXMinutes($now, 720);
    }

    /**
     * @param array $songHistoryEntries
     * @param int $length
     * @return bool
     */
    protected function wasPlayedRecently(array $songHistoryEntries = [], $length = 15): bool
    {
        if (empty($songHistoryEntries)) {
            return true;
        }

        // Check if already played
        $relevant_song_history = array_slice($songHistoryEntries, 0, $length);

        $was_played = false;
        foreach($relevant_song_history as $sh_row) {
            if ((int)$sh_row['playlist_id'] === $this->id) {
                $was_played = true;
                break;
            }
        }

        reset($songHistoryEntries);
        return $was_played;
    }

    protected function wasPlayedInLastXMinutes(Chronos $now, int $minutes): bool
    {
        if (0 === $this->played_at) {
            return false;
        }

        $threshold = $now->addMinutes(0-$minutes)->getTimestamp();
        return ($this->played_at > $threshold);
    }

    /**
     * Export the playlist into a reusable format.
     *
     * @param string $file_format
     * @param bool $absolute_paths
     * @param bool $with_annotations
     * @return string
     */
    public function export($file_format = 'pls', $absolute_paths = false, $with_annotations = false): string
    {
        $media_path = ($absolute_paths) ? $this->station->getRadioMediaDir().'/' : '';

        switch($file_format)
        {
            case 'm3u':
                $playlist_file = [];
                foreach ($this->media_items as $media_item) {
                    $media_file = $media_item->getMedia();
                    $media_file_path = $media_path . $media_file->getPath();
                    $playlist_file[] = $media_file_path;
                }

                return implode("\n", $playlist_file);
                break;

            case 'pls':
            default:
                $playlist_file = [
                    '[playlist]',
                ];

                $i = 0;
                foreach($this->media_items as $media_item) {
                    $i++;

                    $media_file = $media_item->getMedia();
                    $media_file_path = $media_path . $media_file->getPath();
                    $playlist_file[] = 'File'.$i.'='.$media_file_path;
                    $playlist_file[] = 'Title'.$i.'='.$media_file->getArtist().' - '.$media_file->getTitle();
                    $playlist_file[] = 'Length'.$i.'='.$media_file->getLength();
                    $playlist_file[] = '';
                }

                $playlist_file[] = 'NumberOfEntries='.$i;
                $playlist_file[] = 'Version=2';

                return implode("\n", $playlist_file);
                break;
        }
    }

    /**
     * Return a \DateTime object (or null) for a given time code, by default in the UTC time zone.
     *
     * @param string|int $time_code
     * @param Chronos|null $now
     * @return Chronos
     */
    public static function getDateTime($time_code, Chronos $now = null): Chronos
    {
        if ($now === null) {
            $now = Chronos::now(new \DateTimeZone('UTC'));
        }

        $time_code = str_pad($time_code, 4, '0', STR_PAD_LEFT);
        return $now->setTime(substr($time_code, 0, 2), substr($time_code, 2));
    }
}
