<?php

/**
 * @copyright   Copyright (C) 2024-2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2025-09-12
 */

namespace BR\Extension\Isms\Util;

use MetaModel;
use AttributeDate;
use AttributeDateTime;
use DateTimeImmutable;
use DateInterval;

final class IsmsUtils
{
    private const MODULE = 'br-isms';

    private function __construct() {}

    /** Today's date in iTop's internal Y-m-d format. */
    public static function Today(): string
    {
        return date(AttributeDate::GetInternalFormat());
    }

    /** Current timestamp in iTop's internal Y-m-d H:i:s format. */
    public static function Now(): string
    {
        return date(AttributeDateTime::GetInternalFormat());
    }

    /** Module setting `review_interval_months` with sane fallback. */
    public static function GetDefaultReviewIntervalMonths(): int
    {
        try {
            $i = (int) MetaModel::GetModuleSetting(self::MODULE, 'review_interval_months', 12);
            return ($i > 0) ? $i : 12;
        } catch (\Throwable $e) {
            return 12;
        }
    }

    /**
     * Add N months to an anchor date (Y-m-d). If the anchor is the last day
     * of its month, we clamp the result to the last day of the target month.
     */
    public static function ComputeNextReviewDate(string $anchorYmd, int $months, bool $clampEom = true): string
    {
        $months = max(1, (int) $months);
        $anchorYmd = $anchorYmd !== '' ? $anchorYmd : self::Today();

        $dt   = new DateTimeImmutable($anchorYmd);
        $next = $dt->add(new DateInterval("P{$months}M"));

        if ($clampEom) {
            $isEom = ((int) $dt->format('j') === (int) $dt->format('t'));
            if ($isEom) {
                $next = $next->setDate((int) $next->format('Y'), (int) $next->format('n'), (int) $next->format('t'));
            }
        }
        return $next->format(AttributeDate::GetInternalFormat());
    }
}
