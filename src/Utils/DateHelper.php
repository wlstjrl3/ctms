<?php
declare(strict_types=1);

namespace App\Utils;

use DateTime;
use DateTimeImmutable;
use DateInterval;

class DateHelper
{
    /**
     * Get start and end date of the week
     * Converted from weekSE(dates, s_date, e_date)
     */
    public static function getWeekRange(string|DateTime $date, int $startDay = 1, int $endDay = 7): array
    {
        $dt = ($date instanceof DateTime) ? $date : new DateTime($date);
        $w = (int)$dt->format('w'); // 0 (Sun) to 6 (Sat)
        
        // ASP's DatePart("w") is 1-indexed (Sun=1, Sat=7)
        // Adjusting logic to match ASP behavior if needed, but let's make it robust
        $w_asp = $w + 1; 

        $diff_s = $startDay - $w_asp;
        $diff_e = $endDay - $w_asp;

        $start = (clone $dt)->modify("$diff_s days");
        $end = (clone $dt)->modify("$diff_e days");

        return [
            'start' => $start->format('Y-m-d'),
            'end'   => $end->format('Y-m-d')
        ];
    }

    /**
     * Get number of days in a month
     * Converted from Month_Day(i_year, i_month)
     */
    public static function getDaysInMonth(int $year, int $month): int
    {
        return (int)cal_days_in_month(CAL_GREGORIAN, $month, $year);
    }

    /**
     * Get status text for a date within a range
     * Converted from tdate_today
     */
    public static function getStatusInRange(string $startDate, string $endDate, string $currentDate, string $label): string
    {
        $s = new DateTime($startDate);
        $e = new DateTime($endDate);
        $c = new DateTime($currentDate);

        if ($c < $s) {
            return "<span style='color: gray;'>{$label} 기간 아님 (미시작)</span>";
        }

        if ($c > $e) {
            return "<span style='color: gray;'>{$label} 기간 아님 (종료됨)</span>";
        }

        // Today is within range
        $diff_s = $c->diff($s)->days;
        $diff_e = $c->diff($e)->days;

        if ($diff_s === 0) {
            return "<span style='color: orange;'>{$label} 진행중 (시작일)</span>";
        }
        
        if ($diff_e === 0) {
            return "<span style='color: orange;'>{$label} 진행중 (종료일)</span>";
        }

        return "<span style='color: orange;'>{$label} 진행중</span>";
    }

    /**
     * Modernized date format for display
     */
    public static function format(string|DateTime $date, string $format = 'Y-m-d H:i'): string
    {
        $dt = ($date instanceof DateTime) ? $date : new DateTime($date);
        return $dt->format($format);
    }
}
