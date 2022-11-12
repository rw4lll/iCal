<?php
declare(strict_types=1);

namespace Rw4lll\ICal;

use DateTimeZone;

class TimeZoneHelper
{
    use TimeZoneMaps;

    /**
     * Cache valid IANA time zone IDs to avoid unnecessary lookups
     *
     * @var array
     */
    protected static array $validIanaTimeZones = [];

    /**
     * Checks if a time zone is valid (IANA, CLDR, or Windows)
     *
     * @param string $timeZone
     * @return bool
     */
    public static function isValidTimeZoneId(string $timeZone): bool
    {
        return self::isValidIanaTimeZoneId($timeZone)
            || self::isValidCldrTimeZoneId($timeZone)
            || self::isValidWindowsTimeZoneId($timeZone);
    }

    /**
     * Checks if a time zone is a valid IANA time zone
     *
     * @param string $timeZone
     * @return bool
     */
    public static function isValidIanaTimeZoneId(string $timeZone): bool
    {
        if (in_array($timeZone, self::$validIanaTimeZones)) {
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
            self::$validIanaTimeZones[] = $timeZone;

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
    public static function isValidCldrTimeZoneId(string $timeZone): bool
    {
        return array_key_exists(html_entity_decode($timeZone), self::$cldrTimeZonesMap);
    }

    /**
     * Checks if a time zone is a recognised Windows (non-CLDR) time zone
     *
     * @param string $timeZone
     * @return bool
     */
    public static function isValidWindowsTimeZoneId(string $timeZone): bool
    {
        return array_key_exists(html_entity_decode($timeZone), self::$windowsTimeZonesMap);
    }

    /**
     * Returns a `DateTimeZone` object based on a string containing a time zone name.
     * Falls back to the default time zone if string passed not a recognised time zone.
     *
     * @param string $timeZoneString
     * @param string $defaultTimeZone
     * @return DateTimeZone
     */
    public static function timeZoneStringToDateTimeZone(string $timeZoneString, string $defaultTimeZone): DateTimeZone
    {
        // Some time zones contain characters that are not permitted in param-texts,
        // but are within quoted texts. We need to remove the quotes as they're not
        // actually part of the time zone.
        $timeZoneString = trim($timeZoneString, '"');
        $timeZoneString = html_entity_decode($timeZoneString);

        if (self::isValidIanaTimeZoneId($timeZoneString)) {
            return new DateTimeZone($timeZoneString);
        }

        if (self::isValidCldrTimeZoneId($timeZoneString)) {
            return new DateTimeZone(self::$cldrTimeZonesMap[$timeZoneString]);
        }

        if (self::isValidWindowsTimeZoneId($timeZoneString)) {
            return new DateTimeZone(self::$windowsTimeZonesMap[$timeZoneString]);
        }

        return new DateTimeZone($defaultTimeZone);
    }
}
