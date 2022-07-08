<?php

declare(strict_types=1);


namespace Tests;

use PHPUnit\Framework\TestCase;
use Rw4lll\ICal\DateTimeParser;
use Rw4lll\ICal\ICal;

/**
 *
 */
class RecurrencesTest extends TestCase
{
    private $originalTimeZone = null;

    /**
     * @before
     */
    public function setUpFixtures(): void
    {
        $this->originalTimeZone = date_default_timezone_get();
    }

    /**
     * @after
     */
    public function tearDownFixtures(): void
    {
        date_default_timezone_set($this->originalTimeZone);
    }

    /**
     * @return void
     */
    public function testYearlyFullDayTimeZoneBerlin(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20000301', 'message' => '1st event, CET: '],
            ['index' => 1, 'dateString' => '20010301T000000', 'message' => '2nd event, CET: '],
            ['index' => 2, 'dateString' => '20020301T000000', 'message' => '3rd event, CET: '],
        ];
        $this->assertVEVENT(
            'Europe/Berlin',
            [
                'DTSTART;VALUE=DATE:20000301',
                'DTEND;VALUE=DATE:20000302',
                'RRULE:FREQ=YEARLY;WKST=SU;COUNT=3',
            ],
            3,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testMonthlyFullDayTimeZoneBerlin(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20000301', 'message' => '1st event, CET: '],
            ['index' => 1, 'dateString' => '20000401T000000', 'message' => '2nd event, CEST: '],
            ['index' => 2, 'dateString' => '20000501T000000', 'message' => '3rd event, CEST: '],
        ];
        $this->assertVEVENT(
            'Europe/Berlin',
            [
                'DTSTART;VALUE=DATE:20000301',
                'DTEND;VALUE=DATE:20000302',
                'RRULE:FREQ=MONTHLY;BYMONTHDAY=1;WKST=SU;COUNT=3',
            ],
            3,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testMonthlyFullDayTimeZoneBerlinSummerTime(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20180701', 'message' => '1st event, CEST: '],
            ['index' => 1, 'dateString' => '20180801T000000', 'message' => '2nd event, CEST: '],
            ['index' => 2, 'dateString' => '20180901T000000', 'message' => '3rd event, CEST: '],
        ];
        $this->assertVEVENT(
            'Europe/Berlin',
            [
                'DTSTART;VALUE=DATE:20180701',
                'DTEND;VALUE=DATE:20180702',
                'RRULE:FREQ=MONTHLY;WKST=SU;COUNT=3',
            ],
            3,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testMonthlyFullDayTimeZoneBerlinFromFile(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20180701', 'message' => '1st event, CEST: '],
            ['index' => 1, 'dateString' => '20180801T000000', 'message' => '2nd event, CEST: '],
            ['index' => 2, 'dateString' => '20180901T000000', 'message' => '3rd event, CEST: '],
        ];
        $this->assertEventDataFromString(
            'Europe/Berlin',
            file_get_contents('./tests/ical/ical-monthly.ics'),
            25,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testIssue196FromFile(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20191105T190000', 'timezone' => 'Europe/Berlin', 'message' => '1st event, CEST: '],
            ['index' => 1, 'dateString' => '20191106T190000', 'timezone' => 'Europe/Berlin', 'message' => '2nd event, CEST: '],
            ['index' => 2, 'dateString' => '20191107T190000', 'timezone' => 'Europe/Berlin', 'message' => '3rd event, CEST: '],
            ['index' => 3, 'dateString' => '20191108T190000', 'timezone' => 'Europe/Berlin', 'message' => '4th event, CEST: '],
            ['index' => 4, 'dateString' => '20191109T170000', 'timezone' => 'Europe/Berlin', 'message' => '5th event, CEST: '],
            ['index' => 5, 'dateString' => '20191110T180000', 'timezone' => 'Europe/Berlin', 'message' => '6th event, CEST: '],
        ];
        $this->assertEventDataFromString(
            'UTC',
            file_get_contents('./tests/ical/issue-196.ics'),
            7,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testWeeklyFullDayTimeZoneBerlin(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20000301', 'message' => '1st event, CET: '],
            ['index' => 1, 'dateString' => '20000308T000000', 'message' => '2nd event, CET: '],
            ['index' => 2, 'dateString' => '20000315T000000', 'message' => '3rd event, CET: '],
            ['index' => 3, 'dateString' => '20000322T000000', 'message' => '4th event, CET: '],
            ['index' => 4, 'dateString' => '20000329T000000', 'message' => '5th event, CEST: '],
            ['index' => 5, 'dateString' => '20000405T000000', 'message' => '6th event, CEST: '],
        ];
        $this->assertVEVENT(
            'Europe/Berlin',
            [
                'DTSTART;VALUE=DATE:20000301',
                'DTEND;VALUE=DATE:20000302',
                'RRULE:FREQ=WEEKLY;WKST=SU;COUNT=6',
            ],
            6,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testDailyFullDayTimeZoneBerlin(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20000301', 'message' => '1st event, CET: '],
            ['index' => 1, 'dateString' => '20000302T000000', 'message' => '2nd event, CET: '],
            ['index' => 30, 'dateString' => '20000331T000000', 'message' => '31st event, CEST: '],
        ];
        $this->assertVEVENT(
            'Europe/Berlin',
            [
                'DTSTART;VALUE=DATE:20000301',
                'DTEND;VALUE=DATE:20000302',
                'RRULE:FREQ=DAILY;WKST=SU;COUNT=31',
            ],
            31,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testWeeklyFullDayTimeZoneBerlinLocal(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20000301T000000', 'message' => '1st event, CET: '],
            ['index' => 1, 'dateString' => '20000308T000000', 'message' => '2nd event, CET: '],
            ['index' => 2, 'dateString' => '20000315T000000', 'message' => '3rd event, CET: '],
            ['index' => 3, 'dateString' => '20000322T000000', 'message' => '4th event, CET: '],
            ['index' => 4, 'dateString' => '20000329T000000', 'message' => '5th event, CEST: '],
            ['index' => 5, 'dateString' => '20000405T000000', 'message' => '6th event, CEST: '],
        ];
        $this->assertVEVENT(
            'Europe/Berlin',
            [
                'DTSTART;TZID=Europe/Berlin:20000301T000000',
                'DTEND;TZID=Europe/Berlin:20000302T000000',
                'RRULE:FREQ=WEEKLY;WKST=SU;COUNT=6',
            ],
            6,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testRFCDaily10NewYork(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '19970902T090000', 'timezone' => 'America/New_York', 'message' => '1st event, EDT: '],
            ['index' => 1, 'dateString' => '19970903T090000', 'timezone' => 'America/New_York', 'message' => '2nd event, EDT: '],
            ['index' => 9, 'dateString' => '19970911T090000', 'timezone' => 'America/New_York', 'message' => '10th event, EDT: '],
        ];
        $this->assertVEVENT(
            'Europe/Berlin',
            [
                'DTSTART;TZID=America/New_York:19970902T090000',
                'RRULE:FREQ=DAILY;COUNT=10',
            ],
            10,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testRFCDaily10Berlin(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '19970902T090000', 'timezone' => 'Europe/Berlin', 'message' => '1st event, CEST: '],
            ['index' => 1, 'dateString' => '19970903T090000', 'timezone' => 'Europe/Berlin', 'message' => '2nd event, CEST: '],
            ['index' => 9, 'dateString' => '19970911T090000', 'timezone' => 'Europe/Berlin', 'message' => '10th event, CEST: '],
        ];
        $this->assertVEVENT(
            'Europe/Berlin',
            [
                'DTSTART;TZID=Europe/Berlin:19970902T090000',
                'RRULE:FREQ=DAILY;COUNT=10',
            ],
            10,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testStartDateIsExdateUsingUntil(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20190918T095000', 'timezone' => 'Europe/London', 'message' => '1st event: '],
            ['index' => 1, 'dateString' => '20191002T095000', 'timezone' => 'Europe/London', 'message' => '2nd event: '],
            ['index' => 2, 'dateString' => '20191016T095000', 'timezone' => 'Europe/London', 'message' => '3rd event: '],
        ];
        $this->assertVEVENT(
            'Europe/London',
            [
                'DTSTART;TZID=Europe/London:20190911T095000',
                'RRULE:FREQ=WEEKLY;WKST=SU;UNTIL=20191027T235959Z;BYDAY=WE',
                'EXDATE;TZID=Europe/London:20191023T095000',
                'EXDATE;TZID=Europe/London:20191009T095000',
                'EXDATE;TZID=Europe/London:20190925T095000',
                'EXDATE;TZID=Europe/London:20190911T095000',
            ],
            3,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testStartDateIsExdateUsingCount(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20190918T095000', 'timezone' => 'Europe/London', 'message' => '1st event: '],
            ['index' => 1, 'dateString' => '20191002T095000', 'timezone' => 'Europe/London', 'message' => '2nd event: '],
            ['index' => 2, 'dateString' => '20191016T095000', 'timezone' => 'Europe/London', 'message' => '3rd event: '],
        ];
        $this->assertVEVENT(
            'Europe/London',
            [
                'DTSTART;TZID=Europe/London:20190911T095000',
                'RRULE:FREQ=WEEKLY;WKST=SU;COUNT=7;BYDAY=WE',
                'EXDATE;TZID=Europe/London:20191023T095000',
                'EXDATE;TZID=Europe/London:20191009T095000',
                'EXDATE;TZID=Europe/London:20190925T095000',
                'EXDATE;TZID=Europe/London:20190911T095000',
            ],
            3,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testCountWithExdate(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20200323T050000', 'timezone' => 'Europe/Paris', 'message' => '1st event: '],
            ['index' => 1, 'dateString' => '20200324T050000', 'timezone' => 'Europe/Paris', 'message' => '2nd event: '],
            ['index' => 2, 'dateString' => '20200327T050000', 'timezone' => 'Europe/Paris', 'message' => '3rd event: '],
        ];
        $this->assertVEVENT(
            'Europe/London',
            [
                'DTSTART;TZID=Europe/Paris:20200323T050000',
                'DTEND;TZID=Europe/Paris:20200323T070000',
                'RRULE:FREQ=DAILY;COUNT=5',
                'EXDATE;TZID=Europe/Paris:20200326T050000',
                'EXDATE;TZID=Europe/Paris:20200325T050000',
                'DTSTAMP:20200318T141057Z',
            ],
            3,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testRFCDaily10BerlinFromNewYork(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '19970902T090000', 'timezone' => 'Europe/Berlin', 'message' => '1st event, CEST: '],
            ['index' => 1, 'dateString' => '19970903T090000', 'timezone' => 'Europe/Berlin', 'message' => '2nd event, CEST: '],
            ['index' => 9, 'dateString' => '19970911T090000', 'timezone' => 'Europe/Berlin', 'message' => '10th event, CEST: '],
        ];
        $this->assertVEVENT(
            'America/New_York',
            [
                'DTSTART;TZID=Europe/Berlin:19970902T090000',
                'RRULE:FREQ=DAILY;COUNT=10',
            ],
            10,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testExdatesInDifferentTimezone(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20170503T190000', 'message' => '1st event: '],
            ['index' => 1, 'dateString' => '20170510T190000', 'message' => '2nd event: '],
            ['index' => 9, 'dateString' => '20170712T190000', 'message' => '10th event: '],
            ['index' => 19, 'dateString' => '20171004T190000', 'message' => '20th event: '],
        ];
        $this->assertVEVENT(
            'America/Chicago',
            [
                'DTSTART;TZID=America/Chicago:20170503T190000',
                'RRULE:FREQ=WEEKLY;BYDAY=WE;WKST=SU;UNTIL=20180101',
                'EXDATE:20170601T000000Z',
                'EXDATE:20170803T000000Z',
                'EXDATE:20170824T000000Z',
                'EXDATE:20171026T000000Z',
                'EXDATE:20171102T000000Z',
                'EXDATE:20171123T010000Z',
                'EXDATE:20171221T010000Z',
            ],
            28,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testYearlyWithBySetPos(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '19970306T090000', 'message' => '1st occurrence: '],
            ['index' => 1, 'dateString' => '19970313T090000', 'message' => '2nd occurrence: '],
            ['index' => 2, 'dateString' => '19970325T090000', 'message' => '3rd occurrence: '],
            ['index' => 3, 'dateString' => '19980305T090000', 'message' => '4th occurrence: '],
            ['index' => 4, 'dateString' => '19980312T090000', 'message' => '5th occurrence: '],
            ['index' => 5, 'dateString' => '19980326T090000', 'message' => '6th occurrence: '],
            ['index' => 9, 'dateString' => '20000307T090000', 'message' => '10th occurrence: '],
        ];
        $this->assertVEVENT(
            'America/New_York',
            [
                'DTSTART;TZID=America/New_York:19970306T090000',
                'RRULE:FREQ=YEARLY;COUNT=10;BYMONTH=3;BYDAY=TU,TH;BYSETPOS=2,4,-2',
            ],
            10,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testDailyWithByMonthDay(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20000206T120000', 'message' => '1st event: '],
            ['index' => 1, 'dateString' => '20000211T120000', 'message' => '2nd event: '],
            ['index' => 2, 'dateString' => '20000216T120000', 'message' => '3rd event: '],
            ['index' => 4, 'dateString' => '20000226T120000', 'message' => '5th event, transition from February to March: '],
            ['index' => 5, 'dateString' => '20000301T120000', 'message' => '6th event, transition to March from February: '],
            ['index' => 11, 'dateString' => '20000331T120000', 'message' => '12th event, transition from March to April: '],
            ['index' => 12, 'dateString' => '20000401T120000', 'message' => '13th event, transition to April from March: '],
        ];
        $this->assertVEVENT(
            'Europe/Berlin',
            [
                'DTSTART:20000206T120000',
                'DTEND:20000206T130000',
                'RRULE:FREQ=DAILY;BYMONTHDAY=1,6,11,16,21,26,31;COUNT=16',
            ],
            16,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testYearlyWithByMonthDay(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20001214T120000', 'message' => '1st event: '],
            ['index' => 1, 'dateString' => '20001221T120000', 'message' => '2nd event: '],
            ['index' => 2, 'dateString' => '20010107T120000', 'message' => '3rd event: '],
            ['index' => 3, 'dateString' => '20010114T120000', 'message' => '4th event: '],
            ['index' => 6, 'dateString' => '20010214T120000', 'message' => '7th event: '],
        ];
        $this->assertVEVENT(
            'Europe/Berlin',
            [
                'DTSTART:20001214T120000',
                'DTEND:20001214T130000',
                'RRULE:FREQ=YEARLY;BYMONTHDAY=7,14,21;COUNT=8',
            ],
            8,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testYearlyWithByMonthDayAndByDay(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20001214T120000', 'message' => '1st event: '],
            ['index' => 1, 'dateString' => '20001221T120000', 'message' => '2nd event: '],
            ['index' => 2, 'dateString' => '20010607T120000', 'message' => '3rd event: '],
            ['index' => 3, 'dateString' => '20010614T120000', 'message' => '4th event: '],
            ['index' => 6, 'dateString' => '20020214T120000', 'message' => '7th event: '],
        ];
        $this->assertVEVENT(
            'Europe/Berlin',
            [
                'DTSTART:20001214T120000',
                'DTEND:20001214T130000',
                'RRULE:FREQ=YEARLY;BYMONTHDAY=7,14,21;BYDAY=TH;COUNT=8',
            ],
            8,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testYearlyWithByMonthAndByMonthDay(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20001214T120000', 'message' => '1st event: '],
            ['index' => 1, 'dateString' => '20001221T120000', 'message' => '2nd event: '],
            ['index' => 2, 'dateString' => '20010607T120000', 'message' => '3rd event: '],
            ['index' => 3, 'dateString' => '20010614T120000', 'message' => '4th event: '],
            ['index' => 6, 'dateString' => '20011214T120000', 'message' => '7th event: '],
        ];
        $this->assertVEVENT(
            'Europe/Berlin',
            [
                'DTSTART:20001214T120000',
                'DTEND:20001214T130000',
                'RRULE:FREQ=YEARLY;BYMONTH=12,6;BYMONTHDAY=7,14,21;COUNT=8',
            ],
            8,
            $checks
        );
    }

    /**
     * @return void
     */
    public function testCountIsOne(): void
    {
        $checks = [
            ['index' => 0, 'dateString' => '20211201T090000', 'message' => '1st and only expected event: '],
        ];
        $this->assertVEVENT(
            'UTC',
            [
                'DTSTART:20211201T090000',
                'DTEND:20211201T100000',
                'RRULE:FREQ=DAILY;COUNT=1',
            ],
            1,
            $checks
        );
    }

    /**
     * @param $defaultTimezone
     * @param $veventParts
     * @param $count
     * @param $checks
     * @return void
     */
    public function assertVEVENT($defaultTimezone, $veventParts, $count, $checks): void
    {
        $options = $this->getOptions($defaultTimezone);

        $testIcal  = implode(PHP_EOL, $this->getIcalHeader());
        $testIcal .= PHP_EOL;
        $testIcal .= implode(PHP_EOL, $this->formatIcalEvent($veventParts));
        $testIcal .= PHP_EOL;
        $testIcal .= implode(PHP_EOL, $this->getIcalFooter());

        $ical = ICal::initFromString($testIcal, $options);

        $events = $ical->getEvents();

        $this->assertCount($count, $events);

        foreach ($checks as $check) {
            $this->assertEvent($events[$check['index']], $check['dateString'], $check['message'], $check['timezone'] ?? $defaultTimezone);
        }
    }

    /**
     * @param $defaultTimezone
     * @param $file
     * @param $count
     * @param $checks
     * @return void
     */
    public function assertEventDataFromString($defaultTimezone, $data, $count, $checks): void
    {
        $options = $this->getOptions($defaultTimezone);

        $ical = ICal::initFromString($data, $options);

        $events = $ical->getEvents();

        $this->assertCount($count, $events);

        $events = $ical->sortEventsWithOrder($events);

        foreach ($checks as $check) {
            $this->assertEvent($events[$check['index']], $check['dateString'], $check['message'], $check['timezone'] ?? $defaultTimezone);
        }
    }

    /**
     * @param $event
     * @param $expectedDateString
     * @param $message
     * @param $timeZone
     * @return void
     */
    public function assertEvent($event, $expectedDateString, $message, $timeZone = null): void
    {
        if (!is_null($timeZone)) {
            date_default_timezone_set($timeZone);
        }

        $this->assertEquals(DateTimeParser::parse($expectedDateString), $event->dtstart, $message . 'dtstart mismatch');
    }

    /**
     * @param $defaultTimezone
     * @return array
     */
    public function getOptions($defaultTimezone): array
    {
        return [
            'defaultSpan'                 => 2,                // Default value
            'defaultTimeZone'             => $defaultTimezone, // Default value: UTC
            'defaultWeekStart'            => 'MO',             // Default value
            'disableCharacterReplacement' => false,            // Default value
            'filterDaysAfter'             => null,             // Default value
            'filterDaysBefore'            => null,             // Default value
            'httpUserAgent'               => null,             // Default value
            'skipRecurrence'              => false,            // Default value
        ];
    }

    /**
     * @param $veventParts
     * @return array
     */
    public function formatIcalEvent($veventParts): array
    {
        return array_merge(
            [
                'BEGIN:VEVENT',
                'CREATED:' . gmdate('Ymd\THis\Z'),
                'UID:M2CD-1-1-5FB000FB-BBE4-4F3F-9E7E-217F1FF97209',
            ],
            $veventParts,
            [
                'SUMMARY:test',
                'LAST-MODIFIED:' . gmdate('Ymd\THis\Z', filemtime(__FILE__)),
                'END:VEVENT',
            ]
        );
    }

    /**
     * @return string[]
     */
    public function getIcalHeader(): array
    {
        return [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Google Inc//Google Calendar 70.9054//EN',
            'X-WR-CALNAME:Private',
            'X-APPLE-CALENDAR-COLOR:#FF2968',
            'X-WR-CALDESC:',
        ];
    }

    /**
     * @return string[]
     */
    public function getIcalFooter(): array
    {
        return ['END:VCALENDAR'];
    }
}
