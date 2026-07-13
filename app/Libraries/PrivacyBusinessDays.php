<?php

namespace App\Libraries;

final class PrivacyBusinessDays
{
    public static function add(string $date, int $days): string
    {
        $cursor = new \DateTimeImmutable($date);
        $added = 0;

        while ($added < $days) {
            $cursor = $cursor->modify('+1 day');
            if (self::isBusinessDay($cursor)) {
                $added++;
            }
        }

        return $cursor->format('Y-m-d');
    }

    public static function legalReceipt(string $timestamp, string $cutoff = '17:00'): string
    {
        $received = new \DateTimeImmutable($timestamp, new \DateTimeZone('America/Bogota'));
        $date = $received->setTime(0, 0);
        if (! self::isBusinessDay($date) || $received->format('H:i') > $cutoff) {
            do {
                $date = $date->modify('+1 day');
            } while (! self::isBusinessDay($date));
        }
        return $date->format('Y-m-d');
    }

    public static function isBusinessDay(\DateTimeInterface|string $date): bool
    {
        $value = is_string($date) ? new \DateTimeImmutable($date) : \DateTimeImmutable::createFromInterface($date);
        return (int) $value->format('N') < 6 && ! in_array($value->format('Y-m-d'), self::holidaysForYear((int) $value->format('Y')), true);
    }

    public static function elapsed(string $start, string $end): int
    {
        $cursor = new \DateTimeImmutable(substr($start, 0, 10));
        $limit = new \DateTimeImmutable(substr($end, 0, 10));
        $days = 0;
        while ($cursor < $limit) {
            $cursor = $cursor->modify('+1 day');
            if (self::isBusinessDay($cursor)) { $days++; }
        }
        return $days;
    }

    public static function holidaysForYear(int $year): array
    {
        $dates = [
            "$year-01-01", "$year-05-01", "$year-07-20", "$year-08-07", "$year-12-08", "$year-12-25",
        ];
        foreach (["$year-01-06", "$year-03-19", "$year-06-29", "$year-08-15", "$year-10-12", "$year-11-01", "$year-11-11"] as $movable) {
            $dates[] = self::nextMonday(new \DateTimeImmutable($movable))->format('Y-m-d');
        }
        $easter = (new \DateTimeImmutable("$year-03-21"))->modify('+' . easter_days($year) . ' days');
        $dates[] = $easter->modify('-3 days')->format('Y-m-d');
        $dates[] = $easter->modify('-2 days')->format('Y-m-d');
        foreach ([39, 60, 68] as $offset) {
            $dates[] = self::nextMonday($easter->modify('+' . $offset . ' days'))->format('Y-m-d');
        }
        $dates = array_values(array_unique($dates));
        sort($dates);
        return $dates;
    }

    private static function nextMonday(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return (int) $date->format('N') === 1 ? $date : $date->modify('next monday');
    }
}
