<?php

namespace Rw4lll\ICal;

use DateInterval;
use DateTimeImmutable;

class Event
{
    /**
     * https://www.kanzaki.com/docs/ical/summary.html
     *
     * @var string $summary
     */
    public string $summary = '';

    /**
     * https://www.kanzaki.com/docs/ical/dtstart.html
     *
     * @var DateTimeImmutable $dtstart
     */
    public DateTimeImmutable $dtstart;

    /**
     * https://www.kanzaki.com/docs/ical/dtend.html
     *
     * @var DateTimeImmutable $dtend
     */
    public DateTimeImmutable $dtend;

    /**
     * https://www.kanzaki.com/docs/ical/duration.html
     *
     * @var DateInterval $duration
     */
    public DateInterval $duration;

    /**
     * https://www.kanzaki.com/docs/ical/dtstamp.html
     *
     * @var DateTimeImmutable $dtstamp
     */
    public DateTimeImmutable $dtstamp;

    /**
     * When the event starts, represented as a timezone-adjusted string
     *
     * @var DateTimeImmutable $dtstart_tz
     */
    public DateTimeImmutable $dtstart_tz;

    /**
     * When the event ends, represented as a timezone-adjusted string
     *
     * @var DateTimeImmutable $dtend_tz
     */
    public DateTimeImmutable $dtend_tz;

    /**
     * https://www.kanzaki.com/docs/ical/uid.html
     *
     * @var string $uid
     */
    public string $uid;

    /**
     * https://www.kanzaki.com/docs/ical/created.html
     *
     * @var DateTimeImmutable $created
     */
    public DateTimeImmutable $created;

    /**
     * https://www.kanzaki.com/docs/ical/lastModified.html
     *
     * @var DateTimeImmutable $last_modified
     */
    public DateTimeImmutable $last_modified;

    /**
     * https://www.kanzaki.com/docs/ical/description.html
     *
     * @var string $description
     */
    public string $description = '';

    /**
     * https://www.kanzaki.com/docs/ical/location.html
     *
     * @var string $location
     */
    public string $location = '';

    /**
     * https://www.kanzaki.com/docs/ical/sequence.html
     *
     * @var int $sequence
     */
    public int $sequence;

    /**
     * https://www.kanzaki.com/docs/ical/status.html
     *
     * @var string $status
     */
    public string $status = '';

    /**
     * https://www.kanzaki.com/docs/ical/transp.html
     *
     * @var string $transp
     */
    public string $transp = '';

    /**
     * https://www.kanzaki.com/docs/ical/organizer.html
     *
     * @var string $organizer
     */
    public string $organizer = '';

    /**
     * https://www.kanzaki.com/docs/ical/attendee.html
     *
     * @var string $attendee
     */
    public string $attendee = '';

    protected const STRING_PROPERTIES = [
        'summary',
        'uid',
        'description',
        'location',
        'status',
        'transp',
        'organizer',
        'attendee'
    ];

    protected const DATETIME_PROPERTIES = [
        'dtstart',
        'dtend',
        'dtstamp',
        'dtstart_tz',
        'dtend_tz',
        'created',
        'last_modified'
    ];

    /**
     * Creates the Event object
     *
     * @param  array $data
     * @return void
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $variable = $this->toSnakeCase($key);
            if ($variable === 'sequence') {
                $value = (int)$value;
            } elseif ($variable === 'duration') {
                $value = DateTimeParser::parseDuration($value);
            } elseif (in_array($variable, self::STRING_PROPERTIES)) {
                $value = (string)$value;
            } elseif (in_array($variable, self::DATETIME_PROPERTIES)) {
                $value = DateTimeParser::parse($value);
            } else {
                //custom props
                $value = static::prepareCustomProperty($value);
            }
            $this->{$variable} = $value;
        }
        //fix issue when dtstart or dtend are missing in ICal
        if(!isset($this->dtend) && isset($this->dtstart) ) {
            $this->dtend = $this->dtstart->modify('+1 day');
        }

        if(!isset($this->dtstart) && isset($this->dtend) ) {
            $this->dtstart = $this->dtend->modify('-1 day');
        }
    }

    /**
     * Prepares the data for output
     *
     * @param  mixed $value
     * @return mixed
     */
    protected static function prepareCustomProperty($value)
    {
        if (is_string($value)) {
            return stripslashes(trim(str_replace('\n', "\n", $value)));
        }

        if (is_array($value)) {
            return array_map('self::prepareCustomProperty', $value);
        }

        return $value;
    }

    /**
     * Returns Event data excluding anything blank
     * within an HTML template
     *
     * @param array $customFields
     * @return array
     */
    public function toArray(array $customFields = []): array
    {
        $result = [
            'SUMMARY'       => $this->summary,
            'DTSTART'       => $this->dtstart,
            'DTEND'         => $this->dtend,
            'DTSTART_TZ'    => $this->dtstart_tz,
            'DTEND_TZ'      => $this->dtend_tz,
            'DURATION'      => $this->duration,
            'DTSTAMP'       => $this->dtstamp,
            'UID'           => $this->uid,
            'CREATED'       => $this->created,
            'LAST-MODIFIED' => $this->last_modified,
            'DESCRIPTION'   => $this->description,
            'LOCATION'      => $this->location,
            'SEQUENCE'      => $this->sequence,
            'STATUS'        => $this->status,
            'TRANSP'        => $this->transp,
            'ORGANISER'     => $this->organizer,
            'ATTENDEE(S)'   => $this->attendee,
        ];

        if(!empty($customFields)) {
            foreach ($customFields as $key => $value) {
                $result[$key] = $this->{$value};
            }
        }

        // Remove any blank values
        return array_filter($result);
    }

    /**
     * Converts the given input to snake_case
     *
     * @param string $input
     * @param string $glue
     * @param string $separator
     * @return string
     */
    protected function toSnakeCase(string $input, string $glue = '_', string $separator = '-'): string
    {
        $result = preg_split('/(?<=[a-z])(?=[A-Z])/x', $input);
        $result = implode($glue, $result);
        $result = str_replace($separator, $glue, $result);

        return strtolower($result);
    }
}
