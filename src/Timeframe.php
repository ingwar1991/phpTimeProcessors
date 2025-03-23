<?php
/**
 * Author: ingwar1991@gmail.com
 */

namespace ingwar1991\TimeProcessors;

class Timeframe {
    const TF_1DY = '1dy';
    const TF_2DY = '2dy';
    const TF_1WK = '1wk';
    const TF_2WK = '2wk';
    const TF_1MO = '1mo';
    const TF_2MO = '2mo';
    const TF_3MO = '3mo';
    const TF_6MO = '6mo';
    const TF_1YR = '1yr';

    const DATE_FORMAT_SQL = 'sql';
    const DATE_FORMAT_UNIX = 'unix';
    const DATE_FORMAT_TEXT = 'text';
    const DATE_FORMAT_TEXT_WITH_TIME = 'text_with_time';

    private $timeFrameValues = [ 
        '1 day' => '1dy',
        '2 day' => '2dy',
        '1 week' => '1wk',
        '2 week' => '2wk',
        '1 month' => '1mo',
        '2 month' => '2mo',
        '3 month' => '3mo',
        '6 month' => '6mo',
        '1 year' => '1yr',
    ];

    private $defaultTimeFrame = '2wk';

    public function getTimeFrameValues() {
        return $this->timeFrameValues;
    }

    public function getTimeFrameText($timeFrame) {
        $this->checkTimeFrame($timeFrame);

        return array_search($timeFrame, $this->getTimeFrameValues());
    }

    public function getTimeFrameDaysValues() {
        return [ 
            '1 day' => '1dy',
            '2 day' => '2dy',
        ];
    }

    public function getTimeFrameWeeksValues() {
        return [
            '1 week' => '1wk',
            '2 week' => '2wk',
        ];
    }

    public function getTimeFrameMonthsValues() {
        return [
            '1 month' => '1mo',
            '2 month' => '2mo',
            '3 month' => '3mo',
            '6 month' => '6mo',
        ];
    }

    public function getDefaultTimeFrame() {
        return $this->defaultTimeFrame;
    }

    public function checkTimeFrame(&$timeFrame) {
        if (!in_array($timeFrame, $this->getTimeFrameValues())) {
            $timeFrame = $this->getDefaultTimeFrame();

            return false;
        }

        return true;
    }

    public function getIntervalFromTimeFrame($timeFrame) {
        $this->checkTimeFrame($timeFrame);

        return array_search($timeFrame, $this->getTimeFrameValues());
    }

    public function getDateFromTimeFrame($timeFrame, $resultFormat = TFManager::DATE_FORMAT_SQL) {
        $interval = $this->getIntervalFromTimeFrame($timeFrame);

        if ($resultFormat == self::DATE_FORMAT_SQL) {
            return ' (now() - interval ' . $interval . ') ';
        }

        $dateObj = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateObj->add(\DateInterval::createFromDateString('- ' . $interval));

        $dateFormat = 'U';
        if ($resultFormat == self::DATE_FORMAT_TEXT) {
            $dateFormat = 'Y-m-d';
        }
        if ($resultFormat == self::DATE_FORMAT_TEXT_WITH_TIME) {
            $dateFormat = 'Y-m-d H:i:s';
        }

        return $dateObj->format($dateFormat);
    }

    public function isTimestamp($timestamp) {
        return
            ((string)(int) $timestamp == trim((string) $timestamp))
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX)
                ? true
                : false;
    }

    public function checkDate($date, $notEmpty = false) {
        if ($notEmpty && empty($date)) {
            return false;
        }

        $dateObj = false;

        try {
            $dateObj = $this->isTimestamp($date)
                ? $dateObj = new \DateTime('@' . $date)
                : $dateObj = new \DateTime($date);
        } catch(\Exception $e) {
            return false;
        }

        return $dateObj
            ? true
            : false;
    }

    public function getDateFromDate($date) {
        $dateObj = false;

        if ($this->isTimestamp($date)) {
            $dateObj = new \DateTime('@' . $date);
            $dateObj->setTimezone(new \DateTimeZone('UTC'));
        } else {
            $dateObj = new \DateTime($date);
            $dateObj->setTimezone(new \DateTimeZone('UTC'));
        }

        return $dateObj->format('Y-m-d H:i:s');
    }

    public function getDateObjectFromDate($date) {
        $dateObj = false;

        if ($this->isTimestamp($date)) {
            $dateObj = new \DateTime('@' . $date);
            $dateObj->setTimezone(new \DateTimeZone('UTC'));
        } else {
            $dateObj = new \DateTime($date);
            $dateObj->setTimezone(new \DateTimeZone('UTC'));
        }

        return $dateObj;
    }

    /**
     * This method return number of periods, used for building graphs
     *
     *
     * @param string $timeframe
     *
     * @return int 
     */
    public function getPeriodsCountByTimeFrame($timeframe) {
        $this->checkTimeFrame($timeframe);

        switch ($timeframe) {
            case $timeframe == self::TF_1DY :
                return 23;
                break;
            case $timeframe == self::TF_2DY :
                return 47;
                break;
            case $timeframe == self::TF_1WK :
                return 6;
                break;
            case $timeframe == self::TF_2WK :
                return 13;
                break;
            case $timeframe == self::TF_1MO :
                $curDate = new \DateTime();
                $fromDate = new \DateTime('@' . strtotime('-1 month'));
                return $curDate->diff($fromDate)->format('%a');
                break;
            case $timeframe == self::TF_2MO :
                $curDate = new \DateTime();
                $fromDate = new \DateTime('@' . strtotime('-2 month'));
                return $curDate->diff($fromDate)->format('%a');
                break;
            case $timeframe == self::TF_3MO :
                $curDate = new \DateTime();
                $fromDate = new \DateTime('@' . strtotime('-3 month'));
                return $curDate->diff($fromDate)->format('%a');
                break;
            case $timeframe == self::TF_6MO :
                $curDate = new \DateTime();
                $fromDate = new \DateTime('@' . strtotime('-6 month'));
                return $curDate->diff($fromDate)->format('%a');
                break;
            case $timeframe == self::TF_1YR :
                $curDate = new \DateTime();
                $fromDate = new \DateTime('@' . strtotime('-1 year'));
                return $curDate->diff($fromDate)->format('%a');
                break;
            default :
                return 1;
                break;
        }
    }

    public function dateAdd($intervalStr, $date) {
        $dateObj = $this->getDateObjectFromDate($date);
        $dateObj->add(\DateInterval::createFromDateString($intervalStr));

        return $dateObj->format('Y-m-d H:i:s');
    }
}
