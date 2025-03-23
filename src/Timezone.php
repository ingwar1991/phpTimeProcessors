<?php
/**
 * Author: ingwar1991@gmail.com
 */

namespace ingwar1991\TimeProcessors;

class Timezone {
    private $userTimezone;
    private $serverTimezone = 'UTC';

    private $defaultDateFormat = 'Y-m-d';
    private $defaultDateTimeFormat = 'Y-m-d H:i:s';

    public function __construct($timezone = 'UTC') {
        $this->userTimezone = $timezone;
    }

    public function userTimezone() {
        return $this->userTimezone;
    }

    public function changeUserTimezone($timezone) {
        $this->userTimezone = $timezone;
    }

    public function serverTimezone() {
        return $this->serverTimezone;
    }

    private function defaultFormat() {
        return $this->defaultDateTimeFormat;
    }

    private function isTimestamp($timestamp) {
        return
            ((string)(int) $timestamp == trim((string) $timestamp))
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX)
                ? true
                : false;
    }

    /**
     * This function transforms front date with transmitted format and interval
     *
     * @param string $date
     * @param string|bool $format
     * @param string|bool $interval
     *
     * @return string
     */
    public function frontDate($date = 'now', $format = false, $interval = false) {
        $dateObj = $this->getDate($this->userTimezone(), $date, $interval);

        return $this->getString($dateObj, $format);
    }

    /**
     * This function transforms server date with transmitted format and interval
     *
     * @param string $date
     * @param string|bool $format
     * @param string|bool $interval
     *
     * @return string
     */
    public function serverDate($date = 'now', $format = false, $interval = false) {
        $dateObj = $this->getDate($this->serverTimezone(), $date, $interval);

        return $this->getString($dateObj, $format);
    }

    /**
     * This function transforms front date to unix time
     *
     * @param string $date
     *
     * @return string
     */
    public function frontTime($date = 'now') {
        return $this->frontDate($date, 'U');
    }

    /**
     * This function transforms server date to unix time
     *
     * @param string $date
     *
     * @return string
     */
    public function serverTime($date = 'now') {
        return $this->serverDate($date, 'U');
    }

    /**
     * This function compares 2 front dates
     *
     * @param string $date
     *
     * @return string
     */
    public function frontCompare($date1, $date2 = 'now') {
        $time1 = $this->frontTime($date1);
        $time2 = $this->frontTime($date2);

        if ($time1 == $time2) {
            return 0;
        }

        return $time1 > $time2
            ? 1
            : -1;
    }

    /**
     * This function compares 2 server dates
     *
     * @param string $date
     *
     * @return string
     */
    public function serverCompare($date1, $date2 = 'now') {
        $time1 = $this->serverTime($date1);
        $time2 = $this->serverTime($date2);

        if ($time1 == $time2) {
            return 0;
        }

        return $time1 > $time2
            ? 1
            : -1;
    }

    /**
     * This function checks if date is in between condition string or 2 transmitted front dates
     *
     * $strictSide can be [ false, 'left', 'right' ].
     * Compares:
     *      false: from < DATE > to
     *      left: from <= DATE > to
     *      right: from < DATE => to
     *
     * $withTime toggles the format between this::$defaultDateFormat, this::$defaultDateTimeFormat
     *
     * $condStr should be standard 2 intervals separated by coma
     * If $condStr = false will use $dateFrom and $dateTo
     *
     * $isFront toggles the timezone to use
     */
    private function isInBetween(
        $date, $strictSide = false, $withTime = true, $condStr = false, $dateFrom = 'now', $dateTo = 'now', $isFront = true 
   ) {
        if ($condStr) {
            $conditions = array_map(function($el) {
                return $el
                    ? $el
                    : false;
            }, explode(',', $condStr));

            if (count($conditions) == 2) {
                $dateFrom = $isFront
                    ? $this->frontDate('now', false, $conditions[0])
                    : $this->serverDate('now', false, $conditions[0]);
                $dateTo = $isFront
                    ? $this->frontDate('now', 'Y-m-d H:i:s', $conditions[1])
                    : $this->serverDate('now', 'Y-m-d H:i:s', $conditions[1]);
            }
        }

        $strictSide = in_array($strictSide, ['left', 'right'])
            ? $strictSide
            : false;

        $format = $withTime
            ? $this->defaultDateTimeFormat
            : $this->defaultDateFormat;

        $date = $isFront
            ? $this->frontDate($date, $format)
            : $this->serverDate($date, $format);
        $dateFrom = $isFront
            ? $this->frontDate($dateFrom, $format)
            : $this->serverDate($dateFrom, $format);
        $dateTo = $isFront
            ? $this->frontDate($dateTo, $format)
            : $this->serverDate($dateTo, $format);

        $compareResult = $isFront
            ? $this->frontCompare($date, $dateFrom)
            : $this->serverCompare($date, $dateFrom);
        $compareCheck = $strictSide == 'left'
            ? 0
            : 1;
        if ($compareResult != $compareCheck) {
            return false;
        }

        $compareResult = $isFront
            ? $this->frontCompare($date, $dateTo)
            : $this->serverCompare($date, $dateTo);
        $compareCheck = $strictSide == 'right'
            ? 0
            : -1;
        if ($compareResult != $compareCheck) {
            return false;
        }

        return true;
    }

    public function frontIsInBetween($date, $strictSide = false, $withTime = true, $condStr = false, $dateFrom = 'now', $dateTo = 'now') {
        return $this->isInBetween($date, $strictSide, $withTime, $condStr, $dateFrom, $dateTo, true);
    }

    public function serverIsInBetween($date, $strictSide = false, $withTime = true, $condStr = false, $dateFrom = 'now', $dateTo = 'now') {
        return $this->isInBetween($date, $strictSide, $withTime, $condStr, $dateFrom, $dateTo, false);
    }

    /**
     * This function transforms server date into front date with transmitted format and interval
     *
     * @param string $date
     * @param string|bool $format
     * @param string|bool $interval
     *
     * @return string
     */
    public function toFrontDate($date, $format = false, $interval = false) {
        $dateObj = $this->getDate($this->serverTimezone(), $date, $interval);
        $this->changeTimezone($dateObj, $this->userTimezone());

        return $this->getString($dateObj, $format);
    }

    /**
     * This function transforms front date into server date with transmitted format and interval
     *
     * @param string $date
     * @param string|bool $format
     * @param string|bool $interval
     *
     * @return string
     */
    public function toServerDate($date, $format = false, $interval = false) {
        $dateObj = $this->getDate($this->userTimezone(), $date, $interval);
        $this->changeTimezone($dateObj, $this->serverTimezone());

        return $this->getString($dateObj, $format);
    }

    /**
     * This function transforms front start date into server date
     *
     * @param string $date
     * @param string|bool $format
     * @param string|bool $interval
     *
     * @return string
     */
    public function startDate($date = 'now', $format = false, $interval = false) {
        $dateObj = $this->getDate($this->userTimezone(), $date, $interval);

        $time = $this->getString($dateObj, 'H:i:s');
        if ($time != '00:00:00') {
            $seconds = $this->countSecondsFromTime($time);
            $this->addInterval($dateObj, "- {$seconds} second");
        }

        $this->changeTimezone($dateObj, $this->serverTimezone());

        return $this->getString($dateObj, $format);
    }

    /**
     * This function transforms front end date into server date
     *
     * @param string $date
     * @param string|bool $format
     * @param string|bool $interval
     *
     * @return string
     */
    public function endDate($date = 'now', $format = false, $interval = false) {
        $dateObj = $this->getDate($this->userTimezone(), $date, $interval);

        $time = $this->getString($dateObj, 'H:i:s');
        if ($time != '23:59:59') {
            $seconds = $this->countSecondsFromTime('23:59:59') - $this->countSecondsFromTime($time);
            $this->addInterval($dateObj, "+ {$seconds} second");
        }

        $this->changeTimezone($dateObj, $this->serverTimezone());

        return $this->getString($dateObj, $format);
    }

    private function getDate($timezone, $date, $interval) {
        $dateObj = $this->createDate($timezone, $date);
        if ($interval) {
            $this->addInterval($dateObj, $interval);
        }

        return $dateObj;
    }

    private function createDate($timezone, $date) {
        $date = $date
            ? $date
            : date($this->defaultFormat());

        $dateObj = false;

        if ($this->isTimestamp($date)) {
            $dateObj = new \DateTime('@' . $date);
        }
        else {
            $dateObj = new \DateTime($date, new \DateTimeZone($timezone));
        }
        $dateObj->setTimezone(new \DateTimeZone($timezone));

        return $dateObj;
    }

    private function addInterval(&$dateObj, $interval) {
        $dateObj->add(\DateInterval::createFromDateString($interval));
    }

    private function changeTimezone(&$dateObj, $timezone) {
        $dateObj->setTimezone(new \DateTimeZone($timezone));
    }

    private function getString($dateObj, $format = false) {
        return $dateObj->format(
            $format
                ? $format
                : $this->defaultFormat()
       );
    }

    /**
     * $time format `H:i:s`
     *
     * @param $time
     * @return mixed
     */
    private function countSecondsFromTime($time) {
        $time = explode(':', $time);

        // (((hours * 60) + minutes) * 60) + seconds
        return ((($time[0] * 60) + $time[1]) * 60) + $time[2];
    }
}
