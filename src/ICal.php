<?php

namespace Rw4lll\ICal;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use Psr\Log\LoggerInterface;

use UnexpectedValueException;

use const PHP_VERSION_ID;

class ICal
{
    public const DATE_TIME_FORMAT = 'Ymd\THis';
    public const DATE_TIME_FORMAT_PRETTY = 'F Y H:i:s';
    public const ICAL_DATE_TIME_TEMPLATE = 'TZID=%s:';
    public const ISO_8601_WEEK_START = 'MO';
    public const RECURRENCE_EVENT = 'Generated recurrence event';
    public const SECONDS_IN_A_WEEK = 604800;
    public const TIME_FORMAT = 'His';
    public const TIME_ZONE_UTC = 'UTC';
    public const UNIX_FORMAT = 'U';
    public const UNIX_MIN_YEAR = 1970;
    /**
     * Define which variables can be configured
     */
    private static array $configurableOptions = [
        'defaultSpan',
        'defaultTimeZone',
        'defaultWeekStart',
        'disableCharacterReplacement',
        'filterDaysAfter',
        'filterDaysBefore',
        'skipRecurrence',
    ];
    /**
     * CLDR time zones mapped to IANA time zones.
     */
    private static array $cldrTimeZonesMap = [
        '(UTC-12:00) International Date Line West' => 'Etc/GMT+12',
        '(UTC-11:00) Coordinated Universal Time-11' => 'Etc/GMT+11',
        '(UTC-10:00) Hawaii' => 'Pacific/Honolulu',
        '(UTC-09:00) Alaska' => 'America/Anchorage',
        '(UTC-08:00) Pacific Time (US & Canada)' => 'America/Los_Angeles',
        '(UTC-07:00) Arizona' => 'America/Phoenix',
        '(UTC-07:00) Chihuahua, La Paz, Mazatlan' => 'America/Chihuahua',
        '(UTC-07:00) Mountain Time (US & Canada)' => 'America/Denver',
        '(UTC-06:00) Central America' => 'America/Guatemala',
        '(UTC-06:00) Central Time (US & Canada)' => 'America/Chicago',
        '(UTC-06:00) Guadalajara, Mexico City, Monterrey' => 'America/Mexico_City',
        '(UTC-06:00) Saskatchewan' => 'America/Regina',
        '(UTC-05:00) Bogota, Lima, Quito, Rio Branco' => 'America/Bogota',
        '(UTC-05:00) Chetumal' => 'America/Cancun',
        '(UTC-05:00) Eastern Time (US & Canada)' => 'America/New_York',
        '(UTC-05:00) Indiana (East)' => 'America/Indianapolis',
        '(UTC-04:00) Asuncion' => 'America/Asuncion',
        '(UTC-04:00) Atlantic Time (Canada)' => 'America/Halifax',
        '(UTC-04:00) Caracas' => 'America/Caracas',
        '(UTC-04:00) Cuiaba' => 'America/Cuiaba',
        '(UTC-04:00) Georgetown, La Paz, Manaus, San Juan' => 'America/La_Paz',
        '(UTC-04:00) Santiago' => 'America/Santiago',
        '(UTC-03:30) Newfoundland' => 'America/St_Johns',
        '(UTC-03:00) Brasilia' => 'America/Sao_Paulo',
        '(UTC-03:00) Cayenne, Fortaleza' => 'America/Cayenne',
        '(UTC-03:00) City of Buenos Aires' => 'America/Buenos_Aires',
        '(UTC-03:00) Greenland' => 'America/Godthab',
        '(UTC-03:00) Montevideo' => 'America/Montevideo',
        '(UTC-03:00) Salvador' => 'America/Bahia',
        '(UTC-02:00) Coordinated Universal Time-02' => 'Etc/GMT+2',
        '(UTC-01:00) Azores' => 'Atlantic/Azores',
        '(UTC-01:00) Cabo Verde Is.' => 'Atlantic/Cape_Verde',
        '(UTC) Coordinated Universal Time' => 'Etc/GMT',
        '(UTC+00:00) Casablanca' => 'Africa/Casablanca',
        '(UTC+00:00) Dublin, Edinburgh, Lisbon, London' => 'Europe/London',
        '(UTC+00:00) Monrovia, Reykjavik' => 'Atlantic/Reykjavik',
        '(UTC+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna' => 'Europe/Berlin',
        '(UTC+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague' => 'Europe/Budapest',
        '(UTC+01:00) Brussels, Copenhagen, Madrid, Paris' => 'Europe/Paris',
        '(UTC+01:00) Sarajevo, Skopje, Warsaw, Zagreb' => 'Europe/Warsaw',
        '(UTC+01:00) West Central Africa' => 'Africa/Lagos',
        '(UTC+02:00) Amman' => 'Asia/Amman',
        '(UTC+02:00) Athens, Bucharest' => 'Europe/Bucharest',
        '(UTC+02:00) Beirut' => 'Asia/Beirut',
        '(UTC+02:00) Cairo' => 'Africa/Cairo',
        '(UTC+02:00) Chisinau' => 'Europe/Chisinau',
        '(UTC+02:00) Damascus' => 'Asia/Damascus',
        '(UTC+02:00) Harare, Pretoria' => 'Africa/Johannesburg',
        '(UTC+02:00) Helsinki, Kyiv, Riga, Sofia, Tallinn, Vilnius' => 'Europe/Kiev',
        '(UTC+02:00) Jerusalem' => 'Asia/Jerusalem',
        '(UTC+02:00) Kaliningrad' => 'Europe/Kaliningrad',
        '(UTC+02:00) Tripoli' => 'Africa/Tripoli',
        '(UTC+02:00) Windhoek' => 'Africa/Windhoek',
        '(UTC+03:00) Baghdad' => 'Asia/Baghdad',
        '(UTC+03:00) Istanbul' => 'Europe/Istanbul',
        '(UTC+03:00) Kuwait, Riyadh' => 'Asia/Riyadh',
        '(UTC+03:00) Minsk' => 'Europe/Minsk',
        '(UTC+03:00) Moscow, St. Petersburg, Volgograd' => 'Europe/Moscow',
        '(UTC+03:00) Nairobi' => 'Africa/Nairobi',
        '(UTC+03:30) Tehran' => 'Asia/Tehran',
        '(UTC+04:00) Abu Dhabi, Muscat' => 'Asia/Dubai',
        '(UTC+04:00) Baku' => 'Asia/Baku',
        '(UTC+04:00) Izhevsk, Samara' => 'Europe/Samara',
        '(UTC+04:00) Port Louis' => 'Indian/Mauritius',
        '(UTC+04:00) Tbilisi' => 'Asia/Tbilisi',
        '(UTC+04:00) Yerevan' => 'Asia/Yerevan',
        '(UTC+04:30) Kabul' => 'Asia/Kabul',
        '(UTC+05:00) Ashgabat, Tashkent' => 'Asia/Tashkent',
        '(UTC+05:00) Ekaterinburg' => 'Asia/Yekaterinburg',
        '(UTC+05:00) Islamabad, Karachi' => 'Asia/Karachi',
        '(UTC+05:30) Chennai, Kolkata, Mumbai, New Delhi' => 'Asia/Calcutta',
        '(UTC+05:30) Sri Jayawardenepura' => 'Asia/Colombo',
        '(UTC+05:45) Kathmandu' => 'Asia/Katmandu',
        '(UTC+06:00) Astana' => 'Asia/Almaty',
        '(UTC+06:00) Dhaka' => 'Asia/Dhaka',
        '(UTC+06:30) Yangon (Rangoon)' => 'Asia/Rangoon',
        '(UTC+07:00) Bangkok, Hanoi, Jakarta' => 'Asia/Bangkok',
        '(UTC+07:00) Krasnoyarsk' => 'Asia/Krasnoyarsk',
        '(UTC+07:00) Novosibirsk' => 'Asia/Novosibirsk',
        '(UTC+08:00) Beijing, Chongqing, Hong Kong, Urumqi' => 'Asia/Shanghai',
        '(UTC+08:00) Irkutsk' => 'Asia/Irkutsk',
        '(UTC+08:00) Kuala Lumpur, Singapore' => 'Asia/Singapore',
        '(UTC+08:00) Perth' => 'Australia/Perth',
        '(UTC+08:00) Taipei' => 'Asia/Taipei',
        '(UTC+08:00) Ulaanbaatar' => 'Asia/Ulaanbaatar',
        '(UTC+09:00) Osaka, Sapporo, Tokyo' => 'Asia/Tokyo',
        '(UTC+09:00) Pyongyang' => 'Asia/Pyongyang',
        '(UTC+09:00) Seoul' => 'Asia/Seoul',
        '(UTC+09:00) Yakutsk' => 'Asia/Yakutsk',
        '(UTC+09:30) Adelaide' => 'Australia/Adelaide',
        '(UTC+09:30) Darwin' => 'Australia/Darwin',
        '(UTC+10:00) Brisbane' => 'Australia/Brisbane',
        '(UTC+10:00) Canberra, Melbourne, Sydney' => 'Australia/Sydney',
        '(UTC+10:00) Guam, Port Moresby' => 'Pacific/Port_Moresby',
        '(UTC+10:00) Hobart' => 'Australia/Hobart',
        '(UTC+10:00) Vladivostok' => 'Asia/Vladivostok',
        '(UTC+11:00) Chokurdakh' => 'Asia/Srednekolymsk',
        '(UTC+11:00) Magadan' => 'Asia/Magadan',
        '(UTC+11:00) Solomon Is., New Caledonia' => 'Pacific/Guadalcanal',
        '(UTC+12:00) Anadyr, Petropavlovsk-Kamchatsky' => 'Asia/Kamchatka',
        '(UTC+12:00) Auckland, Wellington' => 'Pacific/Auckland',
        '(UTC+12:00) Coordinated Universal Time+12' => 'Etc/GMT-12',
        '(UTC+12:00) Fiji' => 'Pacific/Fiji',
        "(UTC+13:00) Nuku'alofa" => 'Pacific/Tongatapu',
        '(UTC+13:00) Samoa' => 'Pacific/Apia',
        '(UTC+14:00) Kiritimati Island' => 'Pacific/Kiritimati',
    ];
    /**
     * Maps Windows (non-CLDR) time zone ID to IANA ID. This is pragmatic but not 100% precise as one Windows zone ID
     * maps to multiple IANA IDs (one for each territory). For all practical purposes this should be good enough, though.
     *
     * Source: http://unicode.org/repos/cldr/trunk/common/supplemental/windowsZones.xml
     */
    private static array $windowsTimeZonesMap = [
        'AUS Central Standard Time' => 'Australia/Darwin',
        'AUS Eastern Standard Time' => 'Australia/Sydney',
        'Afghanistan Standard Time' => 'Asia/Kabul',
        'Alaskan Standard Time' => 'America/Anchorage',
        'Aleutian Standard Time' => 'America/Adak',
        'Altai Standard Time' => 'Asia/Barnaul',
        'Arab Standard Time' => 'Asia/Riyadh',
        'Arabian Standard Time' => 'Asia/Dubai',
        'Arabic Standard Time' => 'Asia/Baghdad',
        'Argentina Standard Time' => 'America/Buenos_Aires',
        'Astrakhan Standard Time' => 'Europe/Astrakhan',
        'Atlantic Standard Time' => 'America/Halifax',
        'Aus Central W. Standard Time' => 'Australia/Eucla',
        'Azerbaijan Standard Time' => 'Asia/Baku',
        'Azores Standard Time' => 'Atlantic/Azores',
        'Bahia Standard Time' => 'America/Bahia',
        'Bangladesh Standard Time' => 'Asia/Dhaka',
        'Belarus Standard Time' => 'Europe/Minsk',
        'Bougainville Standard Time' => 'Pacific/Bougainville',
        'Canada Central Standard Time' => 'America/Regina',
        'Cape Verde Standard Time' => 'Atlantic/Cape_Verde',
        'Caucasus Standard Time' => 'Asia/Yerevan',
        'Cen. Australia Standard Time' => 'Australia/Adelaide',
        'Central America Standard Time' => 'America/Guatemala',
        'Central Asia Standard Time' => 'Asia/Almaty',
        'Central Brazilian Standard Time' => 'America/Cuiaba',
        'Central Europe Standard Time' => 'Europe/Budapest',
        'Central European Standard Time' => 'Europe/Warsaw',
        'Central Pacific Standard Time' => 'Pacific/Guadalcanal',
        'Central Standard Time (Mexico)' => 'America/Mexico_City',
        'Central Standard Time' => 'America/Chicago',
        'Chatham Islands Standard Time' => 'Pacific/Chatham',
        'China Standard Time' => 'Asia/Shanghai',
        'Cuba Standard Time' => 'America/Havana',
        'Dateline Standard Time' => 'Etc/GMT+12',
        'E. Africa Standard Time' => 'Africa/Nairobi',
        'E. Australia Standard Time' => 'Australia/Brisbane',
        'E. Europe Standard Time' => 'Europe/Chisinau',
        'E. South America Standard Time' => 'America/Sao_Paulo',
        'Easter Island Standard Time' => 'Pacific/Easter',
        'Eastern Standard Time (Mexico)' => 'America/Cancun',
        'Eastern Standard Time' => 'America/New_York',
        'Egypt Standard Time' => 'Africa/Cairo',
        'Ekaterinburg Standard Time' => 'Asia/Yekaterinburg',
        'FLE Standard Time' => 'Europe/Kiev',
        'Fiji Standard Time' => 'Pacific/Fiji',
        'GMT Standard Time' => 'Europe/London',
        'GTB Standard Time' => 'Europe/Bucharest',
        'Georgian Standard Time' => 'Asia/Tbilisi',
        'Greenland Standard Time' => 'America/Godthab',
        'Greenwich Standard Time' => 'Atlantic/Reykjavik',
        'Haiti Standard Time' => 'America/Port-au-Prince',
        'Hawaiian Standard Time' => 'Pacific/Honolulu',
        'India Standard Time' => 'Asia/Calcutta',
        'Iran Standard Time' => 'Asia/Tehran',
        'Israel Standard Time' => 'Asia/Jerusalem',
        'Jordan Standard Time' => 'Asia/Amman',
        'Kaliningrad Standard Time' => 'Europe/Kaliningrad',
        'Korea Standard Time' => 'Asia/Seoul',
        'Libya Standard Time' => 'Africa/Tripoli',
        'Line Islands Standard Time' => 'Pacific/Kiritimati',
        'Lord Howe Standard Time' => 'Australia/Lord_Howe',
        'Magadan Standard Time' => 'Asia/Magadan',
        'Magallanes Standard Time' => 'America/Punta_Arenas',
        'Marquesas Standard Time' => 'Pacific/Marquesas',
        'Mauritius Standard Time' => 'Indian/Mauritius',
        'Middle East Standard Time' => 'Asia/Beirut',
        'Montevideo Standard Time' => 'America/Montevideo',
        'Morocco Standard Time' => 'Africa/Casablanca',
        'Mountain Standard Time (Mexico)' => 'America/Chihuahua',
        'Mountain Standard Time' => 'America/Denver',
        'Myanmar Standard Time' => 'Asia/Rangoon',
        'N. Central Asia Standard Time' => 'Asia/Novosibirsk',
        'Namibia Standard Time' => 'Africa/Windhoek',
        'Nepal Standard Time' => 'Asia/Katmandu',
        'New Zealand Standard Time' => 'Pacific/Auckland',
        'Newfoundland Standard Time' => 'America/St_Johns',
        'Norfolk Standard Time' => 'Pacific/Norfolk',
        'North Asia East Standard Time' => 'Asia/Irkutsk',
        'North Asia Standard Time' => 'Asia/Krasnoyarsk',
        'North Korea Standard Time' => 'Asia/Pyongyang',
        'Omsk Standard Time' => 'Asia/Omsk',
        'Pacific SA Standard Time' => 'America/Santiago',
        'Pacific Standard Time (Mexico)' => 'America/Tijuana',
        'Pacific Standard Time' => 'America/Los_Angeles',
        'Pakistan Standard Time' => 'Asia/Karachi',
        'Paraguay Standard Time' => 'America/Asuncion',
        'Romance Standard Time' => 'Europe/Paris',
        'Russia Time Zone 10' => 'Asia/Srednekolymsk',
        'Russia Time Zone 11' => 'Asia/Kamchatka',
        'Russia Time Zone 3' => 'Europe/Samara',
        'Russian Standard Time' => 'Europe/Moscow',
        'SA Eastern Standard Time' => 'America/Cayenne',
        'SA Pacific Standard Time' => 'America/Bogota',
        'SA Western Standard Time' => 'America/La_Paz',
        'SE Asia Standard Time' => 'Asia/Bangkok',
        'Saint Pierre Standard Time' => 'America/Miquelon',
        'Sakhalin Standard Time' => 'Asia/Sakhalin',
        'Samoa Standard Time' => 'Pacific/Apia',
        'Sao Tome Standard Time' => 'Africa/Sao_Tome',
        'Saratov Standard Time' => 'Europe/Saratov',
        'Singapore Standard Time' => 'Asia/Singapore',
        'South Africa Standard Time' => 'Africa/Johannesburg',
        'Sri Lanka Standard Time' => 'Asia/Colombo',
        'Sudan Standard Time' => 'Africa/Tripoli',
        'Syria Standard Time' => 'Asia/Damascus',
        'Taipei Standard Time' => 'Asia/Taipei',
        'Tasmania Standard Time' => 'Australia/Hobart',
        'Tocantins Standard Time' => 'America/Araguaina',
        'Tokyo Standard Time' => 'Asia/Tokyo',
        'Tomsk Standard Time' => 'Asia/Tomsk',
        'Tonga Standard Time' => 'Pacific/Tongatapu',
        'Transbaikal Standard Time' => 'Asia/Chita',
        'Turkey Standard Time' => 'Europe/Istanbul',
        'Turks And Caicos Standard Time' => 'America/Grand_Turk',
        'US Eastern Standard Time' => 'America/Indianapolis',
        'US Mountain Standard Time' => 'America/Phoenix',
        'UTC' => 'Etc/GMT',
        'UTC+12' => 'Etc/GMT-12',
        'UTC+13' => 'Etc/GMT-13',
        'UTC-02' => 'Etc/GMT+2',
        'UTC-08' => 'Etc/GMT+8',
        'UTC-09' => 'Etc/GMT+9',
        'UTC-11' => 'Etc/GMT+11',
        'Ulaanbaatar Standard Time' => 'Asia/Ulaanbaatar',
        'Venezuela Standard Time' => 'America/Caracas',
        'Vladivostok Standard Time' => 'Asia/Vladivostok',
        'W. Australia Standard Time' => 'Australia/Perth',
        'W. Central Africa Standard Time' => 'Africa/Lagos',
        'W. Europe Standard Time' => 'Europe/Berlin',
        'W. Mongolia Standard Time' => 'Asia/Hovd',
        'West Asia Standard Time' => 'Asia/Tashkent',
        'West Bank Standard Time' => 'Asia/Hebron',
        'West Pacific Standard Time' => 'Pacific/Port_Moresby',
        'Yakutsk Standard Time' => 'Asia/Yakutsk',
    ];
    /**
     * Tracks the number of alarms in the current iCal feed
     *
     * @var int
     */
    public int $alarmCount = 0;
    /**
     * Tracks the number of events in the current iCal feed
     *
     * @var int
     */
    public int $eventCount = 0;
    /**
     * Tracks the free/busy count in the current iCal feed
     *
     * @var int
     */
    public int $freeBusyCount = 0;
    /**
     * Tracks the number of todos in the current iCal feed
     *
     * @var int
     */
    public int $todoCount = 0;
    /**
     * The value in years to use for indefinite, recurring events
     *
     * @var int
     */
    public int $defaultSpan = 2;
    /**
     * Enables customisation of the default time zone
     *
     * @var string
     */
    public string $defaultTimeZone;
    /**
     * The two letter representation of the first day of the week
     *
     * @var string
     */
    public string $defaultWeekStart = self::ISO_8601_WEEK_START;
    /**
     * Toggles whether to skip the parsing of recurrence rules
     *
     * @var bool
     */
    public bool $skipRecurrence = false;
    /**
     * Toggles whether to disable all character replacement.
     *
     * @var bool
     */
    public bool $disableCharacterReplacement = false;
    /**
     * With this being non-null the parser will ignore all events more than roughly this many days after now.
     *
     * @var int|null
     */
    public ?int $filterDaysBefore = null;
    /**
     * With this being non-null the parser will ignore all events more than roughly this many days before now.
     *
     * @var int|null
     */
    public ?int $filterDaysAfter = null;
    /**
     * The parsed calendar
     *
     * @var array
     */
    public array $cal = [];
    /**
     * Tracks the VFREEBUSY component
     *
     * @var int
     */
    protected int $freeBusyIndex = 0;
    /**
     * Variable to track the previous keyword
     *
     * @var string
     */
    protected string $lastKeyword;
    /**
     * Cache valid IANA time zone IDs to avoid unnecessary lookups
     *
     * @var array
     */
    protected array $validIanaTimeZones = [];
    /**
     * Event recurrence instances that have been altered
     *
     * @var array
     */
    protected array $alteredRecurrenceInstances = [];
    /**
     * An associative array containing weekday conversion data
     *
     * The order of the days in the array follow the ISO-8601 specification of a week.
     *
     * @var array
     */
    protected array $weekdays = [
        'MO' => 'monday',
        'TU' => 'tuesday',
        'WE' => 'wednesday',
        'TH' => 'thursday',
        'FR' => 'friday',
        'SA' => 'saturday',
        'SU' => 'sunday',
    ];
    /**
     * An associative array containing frequency conversion terms
     *
     * @var array
     */
    protected array $frequencyConversion = [
        'DAILY' => 'day',
        'WEEKLY' => 'week',
        'MONTHLY' => 'month',
        'YEARLY' => 'year',
    ];
    /**
     * @var LoggerInterface|null $logger
     */
    protected ?LoggerInterface $logger;
    /**
     * If `$filterDaysBefore` or `$filterDaysAfter` are set then the events are filtered according to the window defined
     * by this field and `$windowMaxTimestamp`.
     */
    private int $windowMinTimestamp;
    /**
     * If `$filterDaysBefore` or `$filterDaysAfter` are set then the events are filtered according to the window defined
     * by this field and `$windowMinTimestamp`.
     */
    private int $windowMaxTimestamp;
    /**
     * `true` if either `$filterDaysBefore` or `$filterDaysAfter` are set.
     */
    private bool $shouldFilterByWindow = false;

    /**
     * @param array<Event> $events
     */
    protected array $events = [];

    /**
     * Creates the ICal object
     *
     * @param array $lines
     * @param array $options
     * @throws Exception
     */
    public function __construct(array $lines = [], array $options = [], ?LoggerInterface $logger = null)
    {
        /**
         * TODO: check and remove if redundant, or try to solve without ini_set()
         */
        if (PHP_VERSION_ID < 80100) {
            ini_set('auto_detect_line_endings', '1');
        }

        $this->logger = $logger;


        foreach ($options as $option => $value) {
            if (in_array($option, self::$configurableOptions)) {
                $this->{$option} = $value;
            }
        }

        // Fallback to use the system default time zone
        if (!isset($this->defaultTimeZone) || !$this->isValidTimeZoneId($this->defaultTimeZone)) {
            $this->defaultTimeZone = date_default_timezone_get();
        }

        $this->windowMinTimestamp = is_null($this->filterDaysBefore) ? PHP_INT_MIN : (new DateTime('now'))->sub(
            new DateInterval('P' . $this->filterDaysBefore . 'D')
        )->getTimestamp();
        $this->windowMaxTimestamp = is_null($this->filterDaysAfter) ? PHP_INT_MAX : (new DateTime('now'))->add(
            new DateInterval('P' . $this->filterDaysAfter . 'D')
        )->getTimestamp();

        $this->shouldFilterByWindow = !is_null($this->filterDaysBefore) || !is_null($this->filterDaysAfter);

        if (!empty($lines)) {
            $this->initFromLines($lines);
            $this->loadEvents();
        }
    }

    /**
     * Checks if a time zone is valid (IANA, CLDR, or Windows)
     *
     * @param string $timeZone
     * @return bool
     */
    protected function isValidTimeZoneId(string $timeZone)
    {
        return $this->isValidIanaTimeZoneId($timeZone) !== false
            || $this->isValidCldrTimeZoneId($timeZone) !== false
            || $this->isValidWindowsTimeZoneId($timeZone) !== false;
    }

    /**
     * Checks if a time zone is a valid IANA time zone
     *
     * @param string $timeZone
     * @return bool
     */
    protected function isValidIanaTimeZoneId(string $timeZone)
    {
        if (in_array($timeZone, $this->validIanaTimeZones)) {
            return true;
        }

        $valid = [];
        $tza = timezone_abbreviations_list();

        foreach ($tza as $zone) {
            foreach ($zone as $item) {
                $valid[$item['timezone_id']] = true;
            }
        }

        unset($valid['']);

        if (isset($valid[$timeZone]) || in_array($timeZone, timezone_identifiers_list(DateTimeZone::ALL_WITH_BC))) {
            $this->validIanaTimeZones[] = $timeZone;

            return true;
        }

        return false;
    }

    /**
     * Checks if a time zone is a valid CLDR time zone
     *
     * @param string $timeZone
     * @return bool
     */
    public function isValidCldrTimeZoneId(string $timeZone)
    {
        return array_key_exists(html_entity_decode($timeZone), self::$cldrTimeZonesMap);
    }

    /**
     * Checks if a time zone is a recognised Windows (non-CLDR) time zone
     *
     * @param string $timeZone
     * @return bool
     */
    public function isValidWindowsTimeZoneId(string $timeZone)
    {
        return array_key_exists(html_entity_decode($timeZone), self::$windowsTimeZonesMap);
    }

    /**
     * Initialises the parser using an array
     * containing each line of iCal content
     *
     * @param array $lines
     * @return void
     * @throws Exception
     */
    protected function initFromLines(array $lines): ICal
    {
        $lines = $this->unfold($lines);

        if (stristr($lines[0], 'BEGIN:VCALENDAR') !== false) {
            $component = '';
            foreach ($lines as $line) {
                $line = rtrim($line); // Trim trailing whitespace
                $line = $this->removeUnprintableChars($line);

                if (empty($line)) {
                    continue;
                }

                if (!$this->disableCharacterReplacement) {
                    $line = $this->cleanData($line);
                }

                $add = $this->keyValueFromString($line);

                if ($add === false) {
                    continue;
                }

                $keyword = $add[0];
                $values = $add[1]; // May be an array containing multiple values

                if (!is_array($values)) {
                    if (!empty($values)) {
                        $values = [$values]; // Make an array as not one already
                        $blankArray = []; // Empty placeholder array
                        $values[] = $blankArray;
                    } else {
                        $values = []; // Use blank array to ignore this line
                    }
                } elseif (empty($values[0])) {
                    $values = []; // Use blank array to ignore this line
                }

                // Reverse so that our array of properties is processed first
                $values = array_reverse($values);

                foreach ($values as $value) {
                    switch ($line) {
                        // https://www.kanzaki.com/docs/ical/vtodo.html
                        case 'BEGIN:VTODO':
                            if (!is_array($value)) {
                                $this->todoCount++;
                            }

                            $component = 'VTODO';

                            break;

                        // https://www.kanzaki.com/docs/ical/vevent.html
                        case 'BEGIN:VEVENT':
                            if (!is_array($value)) {
                                $this->eventCount++;
                            }

                            $component = 'VEVENT';

                            break;

                        // https://www.kanzaki.com/docs/ical/vfreebusy.html
                        case 'BEGIN:VFREEBUSY':
                            if (!is_array($value)) {
                                $this->freeBusyIndex++;
                            }

                            $component = 'VFREEBUSY';

                            break;

                        case 'BEGIN:VALARM':
                            if (!is_array($value)) {
                                $this->alarmCount++;
                            }

                            $component = 'VALARM';

                            break;

                        case 'END:VALARM':
                            $component = 'VEVENT';

                            break;

                        case 'BEGIN:DAYLIGHT':
                        case 'BEGIN:STANDARD':
                        case 'BEGIN:VCALENDAR':
                        case 'BEGIN:VTIMEZONE':
                            $component = $value;

                            break;

                        case 'END:DAYLIGHT':
                        case 'END:STANDARD':
                        case 'END:VCALENDAR':
                        case 'END:VFREEBUSY':
                        case 'END:VTIMEZONE':
                        case 'END:VTODO':
                            $component = 'VCALENDAR';

                            break;

                        case 'END:VEVENT':
                            if ($this->shouldFilterByWindow) {
                                $this->removeLastEventIfOutsideWindowAndNonRecurring();
                            }

                            $component = 'VCALENDAR';

                            break;

                        default:
                            $this->addCalendarComponentWithKeyAndValue($component, $keyword, $value);

                            break;
                    }
                }
            }

            $this->processEvents();

            if (!$this->skipRecurrence) {
                $this->processRecurrences();

                // Apply changes to altered recurrence instances
                if (!empty($this->alteredRecurrenceInstances)) {
                    $events = $this->cal['VEVENT'];

                    foreach ($this->alteredRecurrenceInstances as $alteredRecurrenceInstance) {
                        if (isset($alteredRecurrenceInstance['altered-event'])) {
                            $alteredEvent = $alteredRecurrenceInstance['altered-event'];
                            $key = key($alteredEvent);
                            $events[$key] = $alteredEvent[$key];
                        }
                    }

                    $this->cal['VEVENT'] = $events;
                }
            }

            if ($this->shouldFilterByWindow) {
                $this->reduceEventsToMinMaxRange();
            }

            $this->processDateConversions();
        }
        return $this;
    }

    /**
     * Unfolds an iCal file in preparation for parsing
     * (https://icalendar.org/iCalendar-RFC-5545/3-1-content-lines.html)
     *
     * @param array $lines
     * @return array
     */
    protected function unfold(array $lines)
    {
        $string = implode(PHP_EOL, $lines);
        $string = preg_replace('/' . PHP_EOL . '[ \t]/', '', $string);

        return explode(PHP_EOL, $string);
    }

    /**
     * Removes unprintable ASCII and UTF-8 characters
     *
     * @param string $data
     * @return string
     */
    protected function removeUnprintableChars(string $data): string
    {
        return preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $data);
    }

    /**
     * Replaces curly quotes and other special characters
     * with their standard equivalents
     *
     * @param string $data
     * @return string
     */
    protected function cleanData(string $data): string
    {
        $replacementChars = [
            "\xe2\x80\x98" => "'",   // ‘
            "\xe2\x80\x99" => "'",   // ’
            "\xe2\x80\x9a" => "'",   // ‚
            "\xe2\x80\x9b" => "'",   // ‛
            "\xe2\x80\x9c" => '"',   // “
            "\xe2\x80\x9d" => '"',   // ”
            "\xe2\x80\x9e" => '"',   // „
            "\xe2\x80\x9f" => '"',   // ‟
            "\xe2\x80\x93" => '-',   // –
            "\xe2\x80\x94" => '--',  // —
            "\xe2\x80\xa6" => '...', // …
            "\xc2\xa0" => ' ',
        ];
        // Replace UTF-8 characters
        $cleanedData = strtr($data, $replacementChars);

        // Replace Windows-1252 equivalents
        $charsToReplace = array_map(function ($code) {
            return mb_chr($code);
        }, [133, 145, 146, 147, 148, 150, 151, 194]);
        $cleanedData = static::mb_str_replace($charsToReplace, $replacementChars, $cleanedData);

        return $cleanedData;
    }

    /**
     * Replace all occurrences of the search string with the replacement string.
     * Multibyte safe.
     *
     * @param string|array $search
     * @param string|array $replace
     * @param string|array $subject
     * @param string|null $encoding
     * @param int $count
     * @return array|string
     */
    protected static function mb_str_replace(
        $search,
        $replace,
        $subject,
        string $encoding = null,
        int &$count = 0
    ) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (is_array($subject)) {
            // Call `mb_str_replace()` for each subject in the array, recursively
            foreach ($subject as $key => $value) {
                $subject[$key] = self::mb_str_replace($search, $replace, $value, $encoding, $count);
            }
        } else {
            // Normalize $search and $replace so they are both arrays of the same length
            $searches = is_array($search) ? array_values($search) : [$search];
            $replacements = is_array($replace) ? array_values($replace) : [$replace];
            $replacements = array_pad($replacements, count($searches), '');

            foreach ($searches as $key => $search) {
                if (is_null($encoding)) {
                    $encoding = mb_detect_encoding($search, 'UTF-8', true);
                }

                $replace = $replacements[$key];
                $searchLen = mb_strlen($search, $encoding);

                $sb = [];
                while (($offset = mb_strpos($subject, $search, 0, $encoding)) !== false) {
                    $sb[] = mb_substr($subject, 0, $offset, $encoding);
                    $subject = mb_substr($subject, $offset + $searchLen, null, $encoding);
                    ++$count;
                }

                $sb[] = $subject;
                $subject = implode($replace, $sb);
            }
        }

        return $subject;
    }

    /**
     * Gets the key value pair from an iCal string
     *
     * @param string $text
     * @return array|bool
     */
    public function keyValueFromString(string $text)
    {
        $splitLine = $this->parseLine($text);
        $object = [];
        $paramObj = [];
        $valueObj = '';
        $i = 0;

        while ($i < count($splitLine)) {
            // The first token corresponds to the property name
            if ($i == 0) {
                $object[0] = $splitLine[$i];
                $i++;
                continue;
            }

            // After each semicolon define the property parameters
            if ($splitLine[$i] === ';') {
                $i++;
                $paramName = $splitLine[$i];
                $i += 2;
                $paramValue = [];
                $multiValue = false;
                // A parameter can have multiple values separated by a comma
                while ($i + 1 < count($splitLine) && $splitLine[$i + 1] === ',') {
                    $paramValue[] = $splitLine[$i];
                    $i += 2;
                    $multiValue = true;
                }

                if ($multiValue) {
                    $paramValue[] = $splitLine[$i];
                } else {
                    $paramValue = $splitLine[$i];
                }

                // Create object with paramName => paramValue
                $paramObj[$paramName] = $paramValue;
            }

            // After a colon all tokens are concatenated (non-standard behaviour because the property can have multiple values
            // according to RFC5545)
            if ($splitLine[$i] === ':') {
                $i++;
                while ($i < count($splitLine)) {
                    $valueObj .= $splitLine[$i];
                    $i++;
                }
            }

            $i++;
        }

        // Object construction
        if ($paramObj !== []) {
            $object[1][0] = $valueObj;
            $object[1][1] = $paramObj;
        } else {
            $object[1] = $valueObj;
        }

        return $object ?: false;
    }

    /**
     * Parses a line from an iCal file into an array of tokens
     *
     * @param string $line
     * @return array
     */
    protected function parseLine(string $line)
    {
        $words = [];
        $word = '';
        // The use of str_split is not a problem here even if the character set is in utf8
        // Indeed we only compare the characters , ; : = " which are on a single byte
        $arrayOfChar = str_split($line);
        $inDoubleQuotes = false;

        foreach ($arrayOfChar as $char) {
            // Don't stop the word on ; , : = if it is enclosed in double quotes
            if ($char === '"') {
                if ($word !== '') {
                    $words[] = $word;
                }

                $word = '';
                $inDoubleQuotes = !$inDoubleQuotes;
            } elseif (!in_array($char, [';', ':', ',', '=']) || $inDoubleQuotes) {
                $word .= $char;
            } else {
                if ($word !== '') {
                    $words[] = $word;
                }

                $words[] = $char;
                $word = '';
            }
        }

        $words[] = $word;

        return $words;
    }

    /**
     * Removes the last event (i.e. most recently parsed) if its start date is outside the window spanned by
     * `$windowMinTimestamp` / `$windowMaxTimestamp`.
     *
     * @return void
     */
    protected function removeLastEventIfOutsideWindowAndNonRecurring()
    {
        $events = $this->cal['VEVENT'];

        if (!empty($events)) {
            $lastIndex = (is_countable($events) ? count($events) : 0) - 1;
            $lastEvent = $events[$lastIndex];

            if ((!isset($lastEvent['RRULE']) || $lastEvent['RRULE'] === '') && $this->doesEventStartOutsideWindow(
                    $lastEvent
                )) {
                $this->eventCount--;

                unset($events[$lastIndex]);
            }

            $this->cal['VEVENT'] = $events;
        }
    }

    /**
     * Determines whether the event start date is outside `$windowMinTimestamp` / `$windowMaxTimestamp`.
     * Returns `true` for invalid dates.
     *
     * @param array $event
     * @return bool
     */
    protected function doesEventStartOutsideWindow(array $event): bool
    {
        return !$this->isValidDate($event['DTSTART']) || $this->isOutOfRange(
                $event['DTSTART'],
                $this->windowMinTimestamp,
                $this->windowMaxTimestamp
            );
    }

    /**
     * Checks if a date string is a valid date
     *
     * @param string $value
     * @return bool
     * @throws Exception
     */
    public function isValidDate(string $value): bool
    {
        if (!$value) {
            return false;
        }

        try {
            new DateTime($value);
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Determines whether a valid iCalendar date is within a given range
     *
     * @param string $calendarDate
     * @param int $minTimestamp
     * @param int $maxTimestamp
     * @return bool
     */
    protected function isOutOfRange(string $calendarDate, int $minTimestamp, int $maxTimestamp): bool
    {
        $timestamp = strtotime(explode('T', $calendarDate)[0]);

        return $timestamp < $minTimestamp || $timestamp > $maxTimestamp;
    }

    /**
     * Add one key and value pair to the `$this->cal` array
     *
     * @param string $component
     * @param string|bool $keyword
     * @param string|array $value
     * @return void
     */
    protected function addCalendarComponentWithKeyAndValue(string $component, $keyword, $value): void
    {
        if ($keyword == false) {
            $keyword = $this->lastKeyword;
        }

        switch ($component) {
            case 'VALARM':
                $key1 = 'VEVENT';
                $key2 = ($this->eventCount - 1);
                $key3 = $component;

                if (!isset($this->cal[$key1][$key2][$key3]["{$keyword}_array"])) {
                    $this->cal[$key1][$key2][$key3]["{$keyword}_array"] = [];
                }

                if (is_array($value)) {
                    // Add array of properties to the end
                    $this->cal[$key1][$key2][$key3]["{$keyword}_array"][] = $value;
                } else {
                    if (!isset($this->cal[$key1][$key2][$key3][$keyword])) {
                        $this->cal[$key1][$key2][$key3][$keyword] = $value;
                    }

                    if ($this->cal[$key1][$key2][$key3][$keyword] !== $value) {
                        $this->cal[$key1][$key2][$key3][$keyword] .= ',' . $value;
                    }
                }

                break;

            case 'VEVENT':
                $key1 = $component;
                $key2 = ($this->eventCount - 1);

                if (!isset($this->cal[$key1][$key2]["{$keyword}_array"])) {
                    $this->cal[$key1][$key2]["{$keyword}_array"] = [];
                }

                if (is_array($value)) {
                    // Add array of properties to the end
                    $this->cal[$key1][$key2]["{$keyword}_array"][] = $value;
                } else {
                    if (!isset($this->cal[$key1][$key2][$keyword])) {
                        $this->cal[$key1][$key2][$keyword] = $value;
                    }

                    if ($keyword === 'EXDATE') {
                        if (trim($value) === $value) {
                            $array = array_filter(explode(',', $value));
                            $this->cal[$key1][$key2]["{$keyword}_array"][] = $array;
                        } else {
                            $value = explode(
                                ',',
                                implode(',', $this->cal[$key1][$key2]["{$keyword}_array"][1]) . trim($value)
                            );
                            $this->cal[$key1][$key2]["{$keyword}_array"][1] = $value;
                        }
                    } else {
                        $this->cal[$key1][$key2]["{$keyword}_array"][] = $value;

                        if ($keyword === 'DURATION') {
                            $duration = new DateInterval($value);
                            $this->cal[$key1][$key2]["{$keyword}_array"][] = $duration;
                        }
                    }

                    if ($this->cal[$key1][$key2][$keyword] !== $value) {
                        $this->cal[$key1][$key2][$keyword] .= ',' . $value;
                    }
                }

                break;

            case 'VFREEBUSY':
                $key1 = $component;
                $key2 = ($this->freeBusyIndex - 1);
                $key3 = $keyword;

                if ($keyword === 'FREEBUSY') {
                    if (is_array($value)) {
                        $this->cal[$key1][$key2][$key3][][] = $value;
                    } else {
                        $this->freeBusyCount++;
                        $key = array_key_last($this->cal[$key1][$key2][$key3]);

                        $value = explode('/', $value);
                        $this->cal[$key1][$key2][$key3][$key][] = $value;
                    }
                } else {
                    $this->cal[$key1][$key2][$key3][] = $value;
                }

                break;

            case 'VTODO':
                $this->cal[$component][$this->todoCount - 1][$keyword] = $value;

                break;

            default:
                $this->cal[$component][$keyword] = $value;

                break;
        }

        $this->lastKeyword = $keyword;
    }

    /**
     * Performs admin tasks on all events as read from the iCal file.
     * Adds a Unix timestamp to all `{DTSTART|DTEND|RECURRENCE-ID}_array` arrays
     * Tracks modified recurrence instances
     *
     * @return void
     */
    protected function processEvents(): void
    {
        $checks = null;
        $events = $this->cal['VEVENT'] ?? [];

        if (!empty($events)) {
            foreach ($events as $key => $anEvent) {
                foreach (['DTSTART', 'DTEND', 'RECURRENCE-ID'] as $type) {
                    if (isset($anEvent[$type])) {
                        $date = $anEvent["{$type}_array"][1];

                        if (isset($anEvent["{$type}_array"][0]['TZID'])) {
                            $timeZone = $this->escapeParamText($anEvent["{$type}_array"][0]['TZID']);
                            $date = sprintf(self::ICAL_DATE_TIME_TEMPLATE, $timeZone) . $date;
                        }

                        $anEvent["{$type}_array"][2] = $this->iCalDateToUnixTimestamp($date);
                        $anEvent["{$type}_array"][3] = $date;
                    }
                }

                if (isset($anEvent['RECURRENCE-ID'])) {
                    $uid = $anEvent['UID'];

                    if (!isset($this->alteredRecurrenceInstances[$uid])) {
                        $this->alteredRecurrenceInstances[$uid] = [];
                    }

                    $recurrenceDateUtc = $this->iCalDateToUnixTimestamp($anEvent['RECURRENCE-ID_array'][3]);
                    $this->alteredRecurrenceInstances[$uid][$key] = $recurrenceDateUtc;
                }

                $events[$key] = $anEvent;
            }

            $eventKeysToRemove = [];

            foreach ($events as $key => $event) {
                $checks[] = !isset($event['RECURRENCE-ID']);
                $checks[] = isset($event['UID']);
                $checks[] = isset($event['UID']) && isset($this->alteredRecurrenceInstances[$event['UID']]);

                if ((bool)array_product($checks)) {
                    $eventDtstartUnix = $this->iCalDateToUnixTimestamp($event['DTSTART_array'][3]);

                    // phpcs:ignore CustomPHPCS.ControlStructures.AssignmentInCondition
                    if (($alteredEventKey = array_search(
                            $eventDtstartUnix,
                            $this->alteredRecurrenceInstances[$event['UID']]
                        )) !== false) {
                        $eventKeysToRemove[] = $alteredEventKey;

                        $alteredEvent = array_replace_recursive($events[$key], $events[$alteredEventKey]);
                        $this->alteredRecurrenceInstances[$event['UID']]['altered-event'] = [$key => $alteredEvent];
                    }
                }

                unset($checks);
            }

            foreach ($eventKeysToRemove as $eventKeyToRemove) {
                $events[$eventKeyToRemove] = null;
            }

            $this->cal['VEVENT'] = $events;
        }
    }

    /**
     * Places double-quotes around texts that have characters not permitted
     * in parameter-texts, but are permitted in quoted-texts.
     *
     * @param string $candidateText
     * @return string
     */
    protected function escapeParamText(string $candidateText): string
    {
        if (strpbrk($candidateText, ':;,') !== false) {
            return '"' . $candidateText . '"';
        }

        return $candidateText;
    }

    /**
     * Returns a Unix timestamp from an iCal date time format
     *
     * @param string $icalDate
     * @return int
     */
    public function iCalDateToUnixTimestamp(string $icalDate)
    {
        return $this->iCalDateToDateTime($icalDate)->getTimestamp();
    }

    /**
     * Returns a `DateTime` object from an iCal date time format
     *
     * @param string $icalDate
     * @return DateTime
     * @throws UnexpectedValueException
     */
    public function iCalDateToDateTime(string $icalDate): DateTime
    {
        /**
         * iCal times may be in 3 formats, (https://www.kanzaki.com/docs/ical/dateTime.html)
         *
         * UTC:      Has a trailing 'Z'
         * Floating: No time zone reference specified, no trailing 'Z', use local time
         * TZID:     Set time zone as specified
         *
         * Use DateTime class objects to get around limitations with `mktime` and `gmmktime`.
         * Must have a local time zone set to process floating times.
         */
        $pattern = '/^(?:TZID=)?([^:]*|".*")'; // [1]: Time zone
        $pattern .= ':?';                       //      Time zone delimiter
        $pattern .= '([0-9]{8})';               // [2]: YYYYMMDD
        $pattern .= 'T?';                       //      Time delimiter
        $pattern .= '(?(?<=T)([0-9]{6}))';      // [3]: HHMMSS (filled if delimiter present)
        $pattern .= '(Z?)/';                    // [4]: UTC flag

        preg_match($pattern, $icalDate, $date);

        if (empty($date)) {
            throw new \UnexpectedValueException('Invalid iCal date format.');
        }

        // A Unix timestamp usually cannot represent a date prior to 1 Jan 1970.
        // PHP, on the other hand, uses negative numbers for that. Thus we don't
        // need to special case them.

        if ($date[4] === 'Z') {
            $dateTimeZone = new DateTimeZone(self::TIME_ZONE_UTC);
        } elseif (!empty($date[1])) {
            $dateTimeZone = $this->timeZoneStringToDateTimeZone($date[1]);
        } else {
            $dateTimeZone = new DateTimeZone($this->defaultTimeZone);
        }

        // The exclamation mark at the start of the format string indicates that if a
        // time portion is not included, the time in the returned DateTime should be
        // set to 00:00:00. Without it, the time would be set to the current system time.
        $dateFormat = '!Ymd';
        $dateBasic = $date[2];
        if (!empty($date[3])) {
            $dateBasic .= "T{$date[3]}";
            $dateFormat .= '\THis';
        }

        return DateTime::createFromFormat($dateFormat, $dateBasic, $dateTimeZone);
    }

    /**
     * Returns a `DateTimeZone` object based on a string containing a time zone name.
     * Falls back to the default time zone if string passed not a recognised time zone.
     *
     * @param string $timeZoneString
     * @return DateTimeZone
     */
    public function timeZoneStringToDateTimeZone(string $timeZoneString): DateTimeZone
    {
        // Some time zones contain characters that are not permitted in param-texts,
        // but are within quoted texts. We need to remove the quotes as they're not
        // actually part of the time zone.
        $timeZoneString = trim($timeZoneString, '"');
        $timeZoneString = html_entity_decode($timeZoneString);

        if ($this->isValidIanaTimeZoneId($timeZoneString)) {
            return new DateTimeZone($timeZoneString);
        }

        if ($this->isValidCldrTimeZoneId($timeZoneString)) {
            return new DateTimeZone(self::$cldrTimeZonesMap[$timeZoneString]);
        }

        if ($this->isValidWindowsTimeZoneId($timeZoneString)) {
            return new DateTimeZone(self::$windowsTimeZonesMap[$timeZoneString]);
        }

        return new DateTimeZone($this->defaultTimeZone);
    }

    /**
     * Processes recurrence rules
     *
     * @return void
     * @throws UnexpectedValueException
     */
    protected function processRecurrences(): void
    {
        $events = $this->cal['VEVENT'] ?? [];

        // If there are no events, then we have nothing to process.
        if (empty($events)) {
            return;
        }

        $allEventRecurrences = [];
        $eventKeysToRemove = [];

        foreach ($events as $key => $anEvent) {
            if (!isset($anEvent['RRULE']) || $anEvent['RRULE'] === '') {
                continue;
            }

            // Tag as generated by a recurrence rule
            $anEvent['RRULE_array'][2] = self::RECURRENCE_EVENT;

            // Create new initial starting point.
            $initialEventDate = $this->iCalDateToDateTime($anEvent['DTSTART_array'][3]);

            // Separate the RRULE stanzas, and explode the values that are lists.
            $rrules = [];
            foreach (array_filter(explode(';', $anEvent['RRULE'])) as $s) {
                [$k, $v] = explode('=', $s);
                if (in_array($k, ['BYSETPOS', 'BYDAY', 'BYMONTHDAY', 'BYMONTH', 'BYYEARDAY', 'BYWEEKNO'])) {
                    $rrules[$k] = explode(',', $v);
                } else {
                    $rrules[$k] = $v;
                }
            }

            // Get frequency
            $frequency = $rrules['FREQ'];

            // Reject RRULE if BYDAY stanza is invalid:
            // > The BYDAY rule part MUST NOT be specified with a numeric value
            // > when the FREQ rule part is not set to MONTHLY or YEARLY.
            // > Furthermore, the BYDAY rule part MUST NOT be specified with a
            // > numeric value with the FREQ rule part set to YEARLY when the
            // > BYWEEKNO rule part is specified.
            if (isset($rrules['BYDAY'])) {
                $checkByDays = function ($carry, $weekday) {
                    return $carry && substr($weekday, -2) === $weekday;
                };
                if (!in_array($frequency, ['MONTHLY', 'YEARLY'])) {
                    if (!array_reduce($rrules['BYDAY'], $checkByDays, true)) {
                        if (!is_null($this->logger)) {
                            $this->logger->error(
                                "ICal::ProcessRecurrences: A {$frequency} RRULE may not contain BYDAY values with numeric prefixes"
                            );
                        }
                        continue;
                    }
                } elseif ($frequency === 'YEARLY' && !empty($rrules['BYWEEKNO'])) {
                    if (!array_reduce($rrules['BYDAY'], $checkByDays, true)) {
                        if (!is_null($this->logger)) {
                            $this->logger->error(
                                'ICal::ProcessRecurrences: A YEARLY RRULE with a BYWEEKNO part may not contain BYDAY values with numeric prefixes'
                            );
                        }
                        continue;
                    }
                }
            }

            // Get Interval
            $interval = (empty($rrules['INTERVAL'])) ? 1 : $rrules['INTERVAL'];

            // Throw an error if this isn't an integer.
            if (!is_int($this->defaultSpan)) {
                throw new UnexpectedValueException('ICal::defaultSpan: User defined value is not an integer');
            }

            // Compute EXDATEs
            $exdates = $this->parseExdates($anEvent);

            // Determine if the initial date is also an EXDATE
            $initialDateIsExdate = array_reduce($exdates, function ($carry, $exdate) use ($initialEventDate) {
                return $carry || $exdate->getTimestamp() == $initialEventDate->getTimestamp();
            }, false);

            if ($initialDateIsExdate) {
                $eventKeysToRemove[] = $key;
            }

            /**
             * Determine at what point we should stop calculating recurrences
             * by looking at the UNTIL or COUNT rrule stanza, or, if neither
             * if set, using a fallback.
             *
             * If the initial date is also an EXDATE, it shouldn't be included
             * in the count.
             *
             * Syntax:
             *   UNTIL={enddate}
             *   COUNT=<positive integer>
             *
             * Where:
             *   enddate = <icalDate> || <icalDateTime>
             */
            $count = 1;
            $countLimit = (isset($rrules['COUNT'])) ? (int)$rrules['COUNT'] : PHP_INT_MAX;
            $until = date_create()->modify("{$this->defaultSpan} years")->setTime(23, 59, 59)->getTimestamp();

            if (isset($rrules['UNTIL'])) {
                $until = min($until, $this->iCalDateToUnixTimestamp($rrules['UNTIL']));
            }

            $eventRecurrences = [];

            $frequencyRecurringDateTime = clone $initialEventDate;
            while ($frequencyRecurringDateTime->getTimestamp() <= $until && $count < $countLimit) {
                $candidateDateTimes = [];

                // phpcs:ignore Squiz.ControlStructures.SwitchDeclaration.MissingDefault
                switch ($frequency) {
                    case 'DAILY':
                        if (!empty($rrules['BYMONTHDAY'])) {
                            if (!isset($monthDays)) {
                                // This variable is unset when we change months (see below)
                                $monthDays = $this->getDaysOfMonthMatchingByMonthDayRRule(
                                    $rrules['BYMONTHDAY'],
                                    $frequencyRecurringDateTime
                                );
                            }

                            if (!in_array($frequencyRecurringDateTime->format('j'), $monthDays)) {
                                break;
                            }
                        }

                        $candidateDateTimes[] = clone $frequencyRecurringDateTime;

                        break;

                    case 'WEEKLY':
                        $initialDayOfWeek = $frequencyRecurringDateTime->format('N');
                        $matchingDays = [$initialDayOfWeek];

                        if (!empty($rrules['BYDAY'])) {
                            // setISODate() below uses the ISO-8601 specification of weeks: start on
                            // a Monday, end on a Sunday. However, RRULEs (or the caller of the
                            // parser) may state an alternate WeeKSTart.
                            $wkstTransition = 7;

                            if (empty($rrules['WKST'])) {
                                if ($this->defaultWeekStart !== self::ISO_8601_WEEK_START) {
                                    $wkstTransition = array_search(
                                        $this->defaultWeekStart,
                                        array_keys($this->weekdays)
                                    );
                                }
                            } elseif ($rrules['WKST'] !== self::ISO_8601_WEEK_START) {
                                $wkstTransition = array_search($rrules['WKST'], array_keys($this->weekdays));
                            }

                            $matchingDays = array_map(
                                function ($weekday) use ($initialDayOfWeek, $wkstTransition, $interval) {
                                    $day = array_search($weekday, array_keys($this->weekdays));

                                    if ($day < $initialDayOfWeek) {
                                        $day += 7;
                                    }

                                    if ($day >= $wkstTransition) {
                                        $day += 7 * ($interval - 1);
                                    }

                                    // Ignoring alternate week starts, $day at this point will have a
                                    // value between 0 and 6. But setISODate() expects a value of 1 to 7.
                                    // Even with alternate week starts, we still need to +1 to set the
                                    // correct weekday.
                                    $day++;

                                    return $day;
                                },
                                $rrules['BYDAY']
                            );
                        }

                        sort($matchingDays);

                        foreach ($matchingDays as $day) {
                            $clonedDateTime = clone $frequencyRecurringDateTime;
                            $candidateDateTimes[] = $clonedDateTime->setISODate(
                                $frequencyRecurringDateTime->format('o'),
                                $frequencyRecurringDateTime->format('W'),
                                $day
                            );
                        }

                        break;

                    case 'MONTHLY':
                        $matchingDays = [];

                        if (!empty($rrules['BYMONTHDAY'])) {
                            $matchingDays = $this->getDaysOfMonthMatchingByMonthDayRRule(
                                $rrules['BYMONTHDAY'],
                                $frequencyRecurringDateTime
                            );
                            if (!empty($rrules['BYDAY'])) {
                                $matchingDays = array_filter(
                                    $this->getDaysOfMonthMatchingByDayRRule(
                                        $rrules['BYDAY'],
                                        $frequencyRecurringDateTime
                                    ),
                                    function ($monthDay) use ($matchingDays) {
                                        return in_array($monthDay, $matchingDays);
                                    }
                                );
                            }
                        } elseif (!empty($rrules['BYDAY'])) {
                            $matchingDays = $this->getDaysOfMonthMatchingByDayRRule(
                                $rrules['BYDAY'],
                                $frequencyRecurringDateTime
                            );
                        } else {
                            $matchingDays[] = $frequencyRecurringDateTime->format('d');
                        }

                        if (!empty($rrules['BYSETPOS'])) {
                            $matchingDays = $this->filterValuesUsingBySetPosRRule($rrules['BYSETPOS'], $matchingDays);
                        }

                        foreach ($matchingDays as $day) {
                            // Skip invalid dates (e.g. 30th February)
                            if ($day > $frequencyRecurringDateTime->format('t')) {
                                continue;
                            }

                            $clonedDateTime = clone $frequencyRecurringDateTime;
                            $candidateDateTimes[] = $clonedDateTime->setDate(
                                $frequencyRecurringDateTime->format('Y'),
                                $frequencyRecurringDateTime->format('m'),
                                $day
                            );
                        }

                        break;

                    case 'YEARLY':
                        $matchingDays = [];

                        if (!empty($rrules['BYMONTH'])) {
                            $bymonthRecurringDatetime = clone $frequencyRecurringDateTime;
                            foreach ($rrules['BYMONTH'] as $byMonth) {
                                $bymonthRecurringDatetime->setDate(
                                    $frequencyRecurringDateTime->format('Y'),
                                    $byMonth,
                                    $frequencyRecurringDateTime->format('d')
                                );

                                // Determine the days of the month affected
                                // (The interaction between BYMONTHDAY and BYDAY is resolved later.)
                                $monthDays = [];
                                if (!empty($rrules['BYMONTHDAY'])) {
                                    $monthDays = $this->getDaysOfMonthMatchingByMonthDayRRule(
                                        $rrules['BYMONTHDAY'],
                                        $bymonthRecurringDatetime
                                    );
                                } elseif (!empty($rrules['BYDAY'])) {
                                    $monthDays = $this->getDaysOfMonthMatchingByDayRRule(
                                        $rrules['BYDAY'],
                                        $bymonthRecurringDatetime
                                    );
                                } else {
                                    $monthDays[] = $bymonthRecurringDatetime->format('d');
                                }

                                // And add each of them to the list of recurrences
                                foreach ($monthDays as $day) {
                                    $matchingDays[] = $bymonthRecurringDatetime->setDate(
                                            $frequencyRecurringDateTime->format('Y'),
                                            $bymonthRecurringDatetime->format('m'),
                                            $day
                                        )->format('z') + 1;
                                }
                            }
                        } elseif (!empty($rrules['BYWEEKNO'])) {
                            $matchingDays = $this->getDaysOfYearMatchingByWeekNoRRule(
                                $rrules['BYWEEKNO'],
                                $frequencyRecurringDateTime
                            );
                        } elseif (!empty($rrules['BYYEARDAY'])) {
                            $matchingDays = $this->getDaysOfYearMatchingByYearDayRRule(
                                $rrules['BYYEARDAY'],
                                $frequencyRecurringDateTime
                            );
                        } elseif (!empty($rrules['BYMONTHDAY'])) {
                            $matchingDays = $this->getDaysOfYearMatchingByMonthDayRRule(
                                $rrules['BYMONTHDAY'],
                                $frequencyRecurringDateTime
                            );
                        }

                        if (!empty($rrules['BYDAY'])) {
                            if (!empty($rrules['BYYEARDAY']) || !empty($rrules['BYMONTHDAY']) || !empty($rrules['BYWEEKNO'])) {
                                $matchingDays = array_filter(
                                    $this->getDaysOfYearMatchingByDayRRule(
                                        $rrules['BYDAY'],
                                        $frequencyRecurringDateTime
                                    ),
                                    function ($yearDay) use ($matchingDays) {
                                        return in_array($yearDay, $matchingDays);
                                    }
                                );
                            } elseif ($matchingDays === []) {
                                $matchingDays = $this->getDaysOfYearMatchingByDayRRule(
                                    $rrules['BYDAY'],
                                    $frequencyRecurringDateTime
                                );
                            }
                        }

                        if ($matchingDays === []) {
                            $matchingDays = [$frequencyRecurringDateTime->format('z') + 1];
                        } else {
                            sort($matchingDays);
                        }

                        if (!empty($rrules['BYSETPOS'])) {
                            $matchingDays = $this->filterValuesUsingBySetPosRRule($rrules['BYSETPOS'], $matchingDays);
                        }

                        foreach ($matchingDays as $day) {
                            $clonedDateTime = clone $frequencyRecurringDateTime;
                            $candidateDateTimes[] = $clonedDateTime->setDate(
                                $frequencyRecurringDateTime->format('Y'),
                                1,
                                $day
                            );
                        }

                        break;
                }

                foreach ($candidateDateTimes as $candidate) {
                    $timestamp = $candidate->getTimestamp();
                    if ($timestamp <= $initialEventDate->getTimestamp()) {
                        continue;
                    }

                    if ($timestamp > $until) {
                        break;
                    }

                    // Exclusions
                    $isExcluded = array_filter($exdates, function ($exdate) use ($timestamp) {
                        return $exdate->getTimestamp() == $timestamp;
                    });

                    if (isset($this->alteredRecurrenceInstances[$anEvent['UID']])) {
                        if (in_array($timestamp, $this->alteredRecurrenceInstances[$anEvent['UID']])) {
                            $isExcluded = true;
                        }
                    }

                    if (!$isExcluded) {
                        $eventRecurrences[] = $candidate;
                        $this->eventCount++;
                    }

                    // Count all evaluated candidates including excluded ones,
                    // and if RRULE[COUNT] (if set) is reached then break.
                    $count++;
                    if ($count >= $countLimit) {
                        break 2;
                    }
                }

                // Move forwards $interval $frequency.
                $monthPreMove = $frequencyRecurringDateTime->format('m');
                $frequencyRecurringDateTime->modify("{$interval} {$this->frequencyConversion[$frequency]}");

                // As noted in Example #2 on https://www.php.net/manual/en/datetime.modify.php,
                // there are some occasions where adding months doesn't give the month you might
                // expect. For instance: January 31st + 1 month == March 3rd (March 2nd on a leap
                // year.) The following code crudely rectifies this.
                if ($frequency === 'MONTHLY') {
                    $monthDiff = $frequencyRecurringDateTime->format('m') - $monthPreMove;

                    if (($monthDiff > 0 && $monthDiff > $interval) || ($monthDiff < 0 && $monthDiff > $interval - 12)) {
                        $frequencyRecurringDateTime->modify('-1 month');
                    }
                }

                // $monthDays is set in the DAILY frequency if the BYMONTHDAY stanza is present in
                // the RRULE. The variable only needs to be updated when we change months, so we
                // unset it here, prompting a recreation next iteration.
                if (isset($monthDays) && $frequencyRecurringDateTime->format('m') !== $monthPreMove) {
                    unset($monthDays);
                }
            }

            unset($monthDays); // Unset it here as well, so it doesn't bleed into the calculation of the next recurring event.

            // Determine event length
            $eventLength = 0;
            if (isset($anEvent['DURATION'])) {
                $clonedDateTime = clone $initialEventDate;
                $endDate = $clonedDateTime->add($anEvent['DURATION_array'][2]);
                $eventLength = $endDate->getTimestamp() - $anEvent['DTSTART_array'][2];
            } elseif (isset($anEvent['DTEND_array'])) {
                $eventLength = $anEvent['DTEND_array'][2] - $anEvent['DTSTART_array'][2];
            }

            // Whether or not the initial date was UTC
            $initialDateWasUTC = substr($anEvent['DTSTART'], -1) === 'Z';

            // Build the param array
            $dateParamArray = [];
            if (
                !$initialDateWasUTC
                && isset($anEvent['DTSTART_array'][0]['TZID'])
                && $this->isValidTimeZoneId($anEvent['DTSTART_array'][0]['TZID'])
            ) {
                $dateParamArray['TZID'] = $anEvent['DTSTART_array'][0]['TZID'];
            }

            // Populate the `DT{START|END}[_array]`s
            $eventRecurrences = array_map(
                function ($recurringDatetime) use ($anEvent, $eventLength, $initialDateWasUTC, $dateParamArray) {
                    $tzidPrefix = (isset($dateParamArray['TZID'])) ? 'TZID=' . $this->escapeParamText(
                            $dateParamArray['TZID']
                        ) . ':' : '';

                    foreach (['DTSTART', 'DTEND'] as $dtkey) {
                        $anEvent[$dtkey] = $recurringDatetime->format(self::DATE_TIME_FORMAT)
                            . (($initialDateWasUTC) ? 'Z' : '');

                        $anEvent["{$dtkey}_array"] = [
                            $dateParamArray,                    // [0] Array of params (incl. TZID)
                            $anEvent[$dtkey],                   // [1] ICalDateTime string w/o TZID
                            $recurringDatetime->getTimestamp(), // [2] Unix Timestamp
                            "{$tzidPrefix}{$anEvent[$dtkey]}",  // [3] Full ICalDateTime string
                        ];

                        if ($dtkey !== 'DTEND') {
                            $recurringDatetime->modify("{$eventLength} seconds");
                        }
                    }

                    return $anEvent;
                },
                $eventRecurrences
            );

            $allEventRecurrences = array_merge($allEventRecurrences, $eventRecurrences);
        }

        // Nullify the initial events that are also EXDATEs
        foreach ($eventKeysToRemove as $eventKeyToRemove) {
            $events[$eventKeyToRemove] = null;
        }

        $events = array_merge($events, $allEventRecurrences);

        $this->cal['VEVENT'] = $events;
    }

    /**
     * Parses a list of excluded dates
     * to be applied to an Event
     *
     * @param array $event
     * @return array
     */
    public function parseExdates(array $event): array
    {
        if (empty($event['EXDATE_array'])) {
            return [];
        }

        $exdates = $event['EXDATE_array'];

        $output = [];
        $currentTimeZone = new DateTimeZone($this->defaultTimeZone);

        foreach ($exdates as $subArray) {
            $finalKey = array_key_last($subArray);

            foreach (array_keys($subArray) as $key) {
                if ($key === 'TZID') {
                    $currentTimeZone = $this->timeZoneStringToDateTimeZone($subArray[$key]);
                } elseif (is_numeric($key)) {
                    $icalDate = $subArray[$key];

                    if (substr($icalDate, -1) === 'Z') {
                        $currentTimeZone = new DateTimeZone(self::TIME_ZONE_UTC);
                    }

                    $output[] = new DateTime($icalDate, $currentTimeZone);

                    if ($key === $finalKey) {
                        // Reset to default
                        $currentTimeZone = new DateTimeZone($this->defaultTimeZone);
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Find all days of a month that match the BYMONTHDAY stanza of an RRULE.
     *
     * RRUle Syntax:
     *   BYMONTHDAY={bymodaylist}
     *
     * Where:
     *   bymodaylist = {monthdaynum}[,{monthdaynum}...]
     *   monthdaynum = ([+] || -) {ordmoday}
     *   ordmoday    = 1 to 31
     *
     * @param array $byMonthDays
     * @param DateTime $initialDateTime
     * @return array
     */
    protected function getDaysOfMonthMatchingByMonthDayRRule(array $byMonthDays, DateTime $initialDateTime): array
    {
        return $this->resolveIndicesOfRange($byMonthDays, $initialDateTime->format('t'));
    }

    /**
     * Resolves values from indices of the range 1 -> $limit.
     *
     * For instance, if passed [1, 4, -16] and 28, this will return [1, 4, 13].
     *
     * @param array $indexes
     * @param int $limit
     * @return array
     */
    protected function resolveIndicesOfRange(array $indexes, int $limit): array
    {
        $matching = [];
        foreach ($indexes as $index) {
            if ($index > 0 && $index <= $limit) {
                $matching[] = $index;
            } elseif ($index < 0 && -$index <= $limit) {
                $matching[] = $index + $limit + 1;
            }
        }

        sort($matching);

        return $matching;
    }

    /**
     * Find all days of a month that match the BYDAY stanza of an RRULE.
     *
     * With no {ordwk}, then return the day number of every {weekday}
     * within the month.
     *
     * With a +ve {ordwk}, then return the {ordwk} {weekday} within the
     * month.
     *
     * With a -ve {ordwk}, then return the {ordwk}-to-last {weekday}
     * within the month.
     *
     * RRule Syntax:
     *   BYDAY={bywdaylist}
     *
     * Where:
     *   bywdaylist = {weekdaynum}[,{weekdaynum}...]
     *   weekdaynum = [[+]{ordwk} || -{ordwk}]{weekday}
     *   ordwk      = 1 to 53
     *   weekday    = SU || MO || TU || WE || TH || FR || SA
     *
     * @param array $byDays
     * @param DateTime $initialDateTime
     * @return array
     */
    protected function getDaysOfMonthMatchingByDayRRule(array $byDays, DateTime $initialDateTime): array
    {
        $matchingDays = [];

        foreach ($byDays as $weekday) {
            $bydayDateTime = clone $initialDateTime;

            $ordwk = (int)substr($weekday, 0, -2);

            // Quantise the date to the first instance of the requested day in a month
            // (Or last if we have a -ve {ordwk})
            $bydayDateTime->modify(
                (($ordwk < 0) ? 'Last' : 'First') .
                ' ' .
                $this->weekdays[substr($weekday, -2)] . // e.g. "Monday"
                ' of ' .
                $initialDateTime->format('F') // e.g. "June"
            );

            if ($ordwk < 0) { // -ve {ordwk}
                $bydayDateTime->modify((++$ordwk) . ' week');
                $matchingDays[] = $bydayDateTime->format('j');
            } elseif ($ordwk > 0) { // +ve {ordwk}
                $bydayDateTime->modify((--$ordwk) . ' week');
                $matchingDays[] = $bydayDateTime->format('j');
            } else { // No {ordwk}
                while ($bydayDateTime->format('n') === $initialDateTime->format('n')) {
                    $matchingDays[] = $bydayDateTime->format('j');
                    $bydayDateTime->modify('+1 week');
                }
            }
        }

        // Sort into ascending order
        sort($matchingDays);

        return $matchingDays;
    }

    /**
     * Filters a provided values-list by applying a BYSETPOS RRule.
     *
     * Where a +ve {daynum} is provided, the {ordday} position'd value as
     * measured from the start of the list of values should be retained.
     *
     * Where a -ve {daynum} is provided, the {ordday} position'd value as
     * measured from the end of the list of values should be retained.
     *
     * RRule Syntax:
     *   BYSETPOS={bysplist}
     *
     * Where:
     *   bysplist  = {setposday}[,{setposday}...]
     *   setposday = {daynum}
     *   daynum    = [+ || -] {ordday}
     *   ordday    = 1 to 366
     *
     * @param array $bySetPos
     * @param array $valuesList
     * @return array
     */
    protected function filterValuesUsingBySetPosRRule(array $bySetPos, array $valuesList): array
    {
        $filteredMatches = [];

        foreach ($bySetPos as $setPosition) {
            if ($setPosition < 0) {
                $setPosition = count($valuesList) + ++$setPosition;
            }

            // Positioning starts at 1, array indexes start at 0
            if (isset($valuesList[$setPosition - 1])) {
                $filteredMatches[] = $valuesList[$setPosition - 1];
            }
        }

        return $filteredMatches;
    }

    /**
     * Find all days of a year that match the BYWEEKNO stanza of an RRULE.
     *
     * Unfortunately, the RFC5545 specification does not specify exactly
     * how BYWEEKNO should expand on the initial DTSTART when provided
     * without any other stanzas.
     *
     * A comparison of expansions used by other ics parsers may be found
     * at https://github.com/s0600204/ics-parser-1/wiki/byweekno
     *
     * This method uses the same expansion as the python-dateutil module.
     *
     * RRUle Syntax:
     *   BYWEEKNO={bywknolist}
     *
     * Where:
     *   bywknolist = {weeknum}[,{weeknum}...]
     *   weeknum    = ([+] || -) {ordwk}
     *   ordwk      = 1 to 53
     *
     * @param array $byWeekNums
     * @param DateTime $initialDateTime
     * @return array
     */
    protected function getDaysOfYearMatchingByWeekNoRRule(array $byWeekNums, DateTime $initialDateTime): array
    {
        // `\DateTime::format('L')` returns 1 if leap year, 0 if not.
        $isLeapYear = $initialDateTime->format('L');
        $firstDayOfTheYear = date_create("first day of January {$initialDateTime->format('Y')}")->format('D');
        $weeksInThisYear = ($firstDayOfTheYear === 'Thu' || $isLeapYear && $firstDayOfTheYear === 'Wed') ? 53 : 52;

        $matchingWeeks = $this->resolveIndicesOfRange($byWeekNums, $weeksInThisYear);
        $matchingDays = [];
        $byweekDateTime = clone $initialDateTime;
        foreach ($matchingWeeks as $weekNum) {
            $dayNum = $byweekDateTime->setISODate(
                    $initialDateTime->format('Y'),
                    $weekNum,
                    1
                )->format('z') + 1;
            for ($x = 0; $x < 7; ++$x) {
                $matchingDays[] = $x + $dayNum;
            }
        }

        sort($matchingDays);

        return $matchingDays;
    }

    /**
     * Find all days of a year that match the BYYEARDAY stanza of an RRULE.
     *
     * RRUle Syntax:
     *   BYYEARDAY={byyrdaylist}
     *
     * Where:
     *   byyrdaylist = {yeardaynum}[,{yeardaynum}...]
     *   yeardaynum  = ([+] || -) {ordyrday}
     *   ordyrday    = 1 to 366
     *
     * @param array $byYearDays
     * @param DateTime $initialDateTime
     * @return array
     */
    protected function getDaysOfYearMatchingByYearDayRRule(array $byYearDays, DateTime $initialDateTime): array
    {
        // `\DateTime::format('L')` returns 1 if leap year, 0 if not.
        $daysInThisYear = $initialDateTime->format('L') ? 366 : 365;

        return $this->resolveIndicesOfRange($byYearDays, $daysInThisYear);
    }

    /**
     * Find all days of a year that match the BYMONTHDAY stanza of an RRULE.
     *
     * RRule Syntax:
     *   BYMONTHDAY={bymodaylist}
     *
     * Where:
     *   bymodaylist = {monthdaynum}[,{monthdaynum}...]
     *   monthdaynum = ([+] || -) {ordmoday}
     *   ordmoday    = 1 to 31
     *
     * @param array $byMonthDays
     * @param DateTime $initialDateTime
     * @return array
     */
    protected function getDaysOfYearMatchingByMonthDayRRule(array $byMonthDays, DateTime $initialDateTime): array
    {
        $matchingDays = [];
        $monthDateTime = clone $initialDateTime;
        for ($month = 1; $month < 13; $month++) {
            $monthDateTime->setDate(
                $initialDateTime->format('Y'),
                $month,
                1
            );

            $monthDays = $this->getDaysOfMonthMatchingByMonthDayRRule($byMonthDays, $monthDateTime);
            foreach ($monthDays as $day) {
                $matchingDays[] = $monthDateTime->setDate(
                        $initialDateTime->format('Y'),
                        $monthDateTime->format('m'),
                        $day
                    )->format('z') + 1;
            }
        }

        return $matchingDays;
    }

    /**
     * Find all days of a year that match the BYDAY stanza of an RRULE.
     *
     * With no {ordwk}, then return the day number of every {weekday}
     * within the year.
     *
     * With a +ve {ordwk}, then return the {ordwk} {weekday} within the
     * year.
     *
     * With a -ve {ordwk}, then return the {ordwk}-to-last {weekday}
     * within the year.
     *
     * RRule Syntax:
     *   BYDAY={bywdaylist}
     *
     * Where:
     *   bywdaylist = {weekdaynum}[,{weekdaynum}...]
     *   weekdaynum = [[+]{ordwk} || -{ordwk}]{weekday}
     *   ordwk      = 1 to 53
     *   weekday    = SU || MO || TU || WE || TH || FR || SA
     *
     * @param array $byDays
     * @param DateTime $initialDateTime
     * @return array
     */
    protected function getDaysOfYearMatchingByDayRRule(array $byDays, DateTime $initialDateTime): array
    {
        $matchingDays = [];

        foreach ($byDays as $weekday) {
            $bydayDateTime = clone $initialDateTime;

            $ordwk = (int)substr($weekday, 0, -2);

            // Quantise the date to the first instance of the requested day in a year
            // (Or last if we have a -ve {ordwk})
            $bydayDateTime->modify(
                (($ordwk < 0) ? 'Last' : 'First') .
                ' ' .
                $this->weekdays[substr($weekday, -2)] . // e.g. "Monday"
                ' of ' . (($ordwk < 0) ? 'December' : 'January') .
                ' ' . $initialDateTime->format('Y') // e.g. "2018"
            );

            if ($ordwk < 0) { // -ve {ordwk}
                $bydayDateTime->modify((++$ordwk) . ' week');
                $matchingDays[] = $bydayDateTime->format('z') + 1;
            } elseif ($ordwk > 0) { // +ve {ordwk}
                $bydayDateTime->modify((--$ordwk) . ' week');
                $matchingDays[] = $bydayDateTime->format('z') + 1;
            } else { // No {ordwk}
                while ($bydayDateTime->format('Y') === $initialDateTime->format('Y')) {
                    $matchingDays[] = $bydayDateTime->format('z') + 1;
                    $bydayDateTime->modify('+1 week');
                }
            }
        }

        // Sort into ascending order
        sort($matchingDays);

        return $matchingDays;
    }

    /**
     * Reduces the number of events to the defined minimum and maximum range
     *
     * @return void
     */
    protected function reduceEventsToMinMaxRange(): void
    {
        $events = $this->cal['VEVENT'] ?? [];

        if (!empty($events)) {
            foreach ($events as $key => $anEvent) {
                if ($anEvent === null) {
                    unset($events[$key]);

                    continue;
                }

                if ($this->doesEventStartOutsideWindow($anEvent)) {
                    $this->eventCount--;

                    unset($events[$key]);
                }
            }

            $this->cal['VEVENT'] = $events;
        }
    }

    /**
     * Processes date conversions using the time zone
     *
     * Add keys `DTSTART_tz` and `DTEND_tz` to each Event
     * These keys contain dates adapted to the calendar
     * time zone depending on the event `TZID`.
     *
     * @return void
     * @throws Exception
     */
    protected function processDateConversions(): void
    {
        $events = $this->cal['VEVENT'] ?? [];

        if (!empty($events)) {
            foreach ($events as $key => $anEvent) {
                if (is_null($anEvent) || !$this->isValidDate($anEvent['DTSTART'])) {
                    unset($events[$key]);
                    $this->eventCount--;

                    continue;
                }

                $events[$key]['DTSTART_tz'] = $this->iCalDateWithTimeZone($anEvent, 'DTSTART');

                if ($this->iCalDateWithTimeZone($anEvent, 'DTEND')) {
                    $events[$key]['DTEND_tz'] = $this->iCalDateWithTimeZone($anEvent, 'DTEND');
                } elseif ($this->iCalDateWithTimeZone($anEvent, 'DURATION')) {
                    $events[$key]['DTEND_tz'] = $this->iCalDateWithTimeZone($anEvent, 'DURATION');
                } else {
                    $events[$key]['DTEND_tz'] = $events[$key]['DTSTART_tz'];
                }
            }

            $this->cal['VEVENT'] = $events;
        }
    }

    /**
     * Returns a date adapted to the calendar time zone depending on the event `TZID`
     *
     * @param array $event
     * @param string $key
     * @param string $format
     * @return string|bool
     */
    public function iCalDateWithTimeZone(array $event, string $key, string $format = self::DATE_TIME_FORMAT)
    {
        if (!isset($event["{$key}_array"]) || !isset($event[$key])) {
            return false;
        }

        $dateArray = $event["{$key}_array"];

        if ($key === 'DURATION') {
            $dateTime = $this->parseDuration($event['DTSTART'], $dateArray[2], null);
        } else {
            // When constructing from a Unix Timestamp, no time zone needs passing.
            $dateTime = new DateTime("@{$dateArray[2]}");
        }

        // Set the time zone we wish to use when running `$dateTime->format`.
        $dateTime->setTimezone(new DateTimeZone($this->calendarTimeZone()));

        if (is_null($format)) {
            return $dateTime;
        }

        return $dateTime->format($format);
    }

    /**
     * Parses a duration and applies it to a date
     *
     * @param string $date
     * @param DateInterval $duration
     * @param string $format
     * @return int|DateTime
     */
    protected function parseDuration(string $date, DateInterval $duration, string $format = self::UNIX_FORMAT)
    {
        $dateTime = date_create($date);
        $dateTime->modify("{$duration->y} year");
        $dateTime->modify("{$duration->m} month");
        $dateTime->modify("{$duration->d} day");
        $dateTime->modify("{$duration->h} hour");
        $dateTime->modify("{$duration->i} minute");
        $dateTime->modify("{$duration->s} second");

        if (is_null($format)) {
            $output = $dateTime;
        } elseif ($format === self::UNIX_FORMAT) {
            $output = $dateTime->getTimestamp();
        } else {
            $output = $dateTime->format($format);
        }

        return $output;
    }

    /**
     * Returns the calendar time zone
     *
     * @param bool $ignoreUtc
     * @return string
     */
    public function calendarTimeZone(bool $ignoreUtc = false): ?string
    {
        if (isset($this->cal['VCALENDAR']['X-WR-TIMEZONE'])) {
            $timeZone = $this->cal['VCALENDAR']['X-WR-TIMEZONE'];
        } elseif (isset($this->cal['VTIMEZONE']['TZID'])) {
            $timeZone = $this->cal['VTIMEZONE']['TZID'];
        } else {
            $timeZone = $this->defaultTimeZone;
        }

        // Validate the time zone, falling back to the time zone set in the PHP environment.
        $timeZone = $this->timeZoneStringToDateTimeZone($timeZone)->getName();

        if ($ignoreUtc && strtoupper($timeZone) === self::TIME_ZONE_UTC) {
            return null;
        }

        return $timeZone;
    }

    /**
     * Initialises lines from a string
     *
     * @param string $string
     * @param array $options
     * @param LoggerInterface|null $logger
     * @return ICal
     * @throws Exception
     */
    public static function initFromString(string $string, array $options = [], ?LoggerInterface $logger = null): ICal
    {
        $string = str_replace(["\r\n", "\n\r", "\r"], "\n", $string);
        $lines = explode("\n", $string);
        return (new static($lines, $options, $logger));
    }

    /**
     * Returns the calendar name
     *
     * @return string
     */
    public function calendarName(): string
    {
        return $this->cal['VCALENDAR']['X-WR-CALNAME'] ?? '';
    }

    /**
     * Returns the calendar description
     *
     * @return string
     */
    public function calendarDescription(): string
    {
        return $this->cal['VCALENDAR']['X-WR-CALDESC'] ?? '';
    }

    /**
     * Returns an array of arrays with all free/busy events.
     * Every event is an associative array and each property
     * is an element it.
     *
     * @return array
     */
    public function freeBusyEvents(): array
    {
        return $this->cal['VFREEBUSY'] ?? [];
    }

    /**
     * Returns a boolean value whether the
     * current calendar has events or not
     *
     * @return bool
     */
    public function hasEvents(): bool
    {
        return !empty($this->events);
    }

    /**
     * Returns generator of Events.
     * Every event is a class with the event
     * details being properties within it.
     *
     * @return void
     */
    protected function loadEvents(): void
    {
        $events = $this->cal['VEVENT'] ?? [];

        if (!empty($events)) {
            foreach ($events as $event) {
                $this->events[] = new Event($event);
            }
        }
    }

    /**
     * @return array<Event>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * Returns a sorted array of the events following a given string,
     * or `false` if no events exist in the range.
     *
     * @param string $interval
     * @return array
     * @throws Exception
     */
    public function eventsFromInterval(string $interval): array
    {
        $rangeStart = new DateTime('now', new DateTimeZone($this->defaultTimeZone));
        $rangeEnd = new DateTime('now', new DateTimeZone($this->defaultTimeZone));

        $dateInterval = DateInterval::createFromDateString($interval);
        $rangeEnd->add($dateInterval);

        return $this->eventsFromRange($rangeStart->format('Y-m-d'), $rangeEnd->format('Y-m-d'));
    }

    /**
     * Returns a sorted array of the events in a given range,
     * or an empty array if no events exist in the range.
     *
     * Events will be returned if the start or end date is contained within the
     * range (inclusive), or if the event starts before and end after the range.
     *
     * If a start date is not specified or of a valid format, then the start
     * of the range will default to the current time and date of the server.
     *
     * If an end date is not specified or of a valid format, then the end of
     * the range will default to the current time and date of the server,
     * plus 20 years.
     *
     * Note that this function makes use of Unix timestamps. This might be a
     * problem for events on, during, or after 29 Jan 2038.
     * See https://en.wikipedia.org/wiki/Unix_time#Representing_the_number
     *
     * @param string|null $rangeStart
     * @param string|null $rangeEnd
     * @return array
     * @throws Exception
     */
    public function eventsFromRange(?string $rangeStart = null, ?string $rangeEnd = null): array
    {
        // Sort events before processing range
        $events = $this->sortEventsWithOrder($this->getEvents());

        if (empty($events)) {
            return [];
        }

        $extendedEvents = [];

        if (!is_null($rangeStart)) {
            try {
                $rangeStart = new DateTime($rangeStart, new DateTimeZone($this->defaultTimeZone));
            } catch (Exception $exception) {
                if (!is_null($this->logger)) {
                    $this->logger->error("ICal::eventsFromRange: Invalid date passed ({$rangeStart})");
                }
                $rangeStart = false;
            }
        } else {
            $rangeStart = new DateTime('now', new DateTimeZone($this->defaultTimeZone));
        }

        if (!is_null($rangeEnd)) {
            try {
                $rangeEnd = new DateTime($rangeEnd, new DateTimeZone($this->defaultTimeZone));
            } catch (Exception $exception) {
                if (!is_null($this->logger)) {
                    $this->logger->error("ICal::eventsFromRange: Invalid date passed ({$rangeEnd})");
                }
                $rangeEnd = false;
            }
        } else {
            $rangeEnd = new DateTime('now', new DateTimeZone($this->defaultTimeZone));
            $rangeEnd->modify('+20 years');
        }

        // If start and end are identical and are dates with no times...
        if ($rangeEnd->format('His') == 0 && $rangeStart->getTimestamp() === $rangeEnd->getTimestamp()) {
            $rangeEnd->modify('+1 day');
        }

        $rangeStart = $rangeStart->getTimestamp();
        $rangeEnd = $rangeEnd->getTimestamp();

        foreach ($events as $anEvent) {
            $eventStart = $anEvent->dtstart_array[2];
            $eventEnd = $anEvent->dtend_array[2] ?? null;

            if (
                ($eventStart >= $rangeStart && $eventStart < $rangeEnd)         // Event start date contained in the range
                || ($eventEnd !== null
                    && (
                        ($eventEnd > $rangeStart && $eventEnd <= $rangeEnd)     // Event end date contained in the range
                        || ($eventStart < $rangeStart && $eventEnd > $rangeEnd) // Event starts before and finishes after range
                    )
                )
            ) {
                $extendedEvents[] = $anEvent;
            }
        }

        if (empty($extendedEvents)) {
            return [];
        }

        return $extendedEvents;
    }

    /**
     * Sorts events based on a given sort order
     *
     * @param array $events
     * @param int $sortOrder Either SORT_ASC, SORT_DESC, SORT_REGULAR, SORT_NUMERIC, SORT_STRING
     * @return array
     */
    public function sortEventsWithOrder(array $events, int $sortOrder = SORT_ASC): array
    {
        $extendedEvents = [];
        $timestamp = [];

        foreach ($events as $key => $anEvent) {
            $extendedEvents[] = $anEvent;
            $timestamp[$key] = $anEvent->dtstart_array[2];
        }

        array_multisort($timestamp, $sortOrder, $extendedEvents);

        return $extendedEvents;
    }

    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
