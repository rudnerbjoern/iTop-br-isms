<?php

/**
 * @copyright   Copyright (C) 2024-2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2025-09-12
 */

namespace BR\Extension\Isms\Model;

use BR\Extension\Isms\Util\IsmsUtils;
use Combodo\iTop\Service\Events\EventData;
use ItopCounter;
use cmdbAbstractObject;

/**
 * ISMS Control business logic.
 *
 * Responsibilities:
 *  - Generate a readable control reference (ref) via ItopCounter.
 *  - Prefill creation defaults (dates, review cadence).
 *  - Maintain meta dates on save.
 *  - Keep selected attributes read-only at all times.
 *
 * Notes:
 *  - No DBUpdate() here; the platform persists after checks.
 *  - Internal date formats use iTop helpers (Y-m-d / Y-m-d H:i:s).
 */
class _ISMSControl extends cmdbAbstractObject
{

    /**
     * Assign a reference if missing and insert without reloading the object.
     *
     * @return int The database primary key of the inserted row.
     */
    public function DBInsertNoReload(): int
    {
        if (empty($this->Get('ref'))) {
            $iNextId = ItopCounter::Inc('ISMSControl');
            $this->Set('ref', $this->MakeControlRef($iNextId));
        }
        return parent::DBInsertNoReload();
    }

    /**
     * Prefill creation with sensible defaults:
     *  - creation_date = today
     *  - last_update   = now
     *  - last_review   = today
     *  - review_interval_months from module settings (fallback 12)
     *  - next_review derived from last_review + interval
     *
     * @param array $aContextParam iTop context (unused)
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        $sToday = IsmsUtils::Today();
        $sNow = IsmsUtils::Now();

        if (empty($this->Get('creation_date'))) {
            $this->Set('creation_date', $sToday);
        }
        if (empty($this->Get('last_update'))) {
            $this->Set('last_update', $sNow);
        }
        if (empty($this->Get('last_review'))) {
            $this->Set('last_review', $sToday);
        }
        if ((int) $this->Get('review_interval_months') <= 0) {
            $this->Set('review_interval_months', IsmsUtils::GetDefaultReviewIntervalMonths());
        }

        if (empty($this->Get('next_review'))) {
            $iMonths = max(1, (int) $this->Get('review_interval_months'));
            $anchor  = (string) $this->Get('last_review') ?: $sToday;
            $this->Set('next_review', IsmsUtils::ComputeNextReviewDate($anchor, $iMonths));
        }
    }

    /** Initial attribute flags at creation time (read-only system fields). */
    public function OnISMSControlSetInitialAttributesFlags(EventData $oEventData): void
    {
        $this->ForceInitialAttributeFlags('ref',                 OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('creation_date',       OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('last_update',         OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('approval_date',       OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('implementation_date', OPT_ATT_READONLY);
    }

    /** Runtime attribute flags (keep read-only at all times). */
    public function OnISMSControlSetAttributesFlags(EventData $oEventData): void
    {
        $this->ForceAttributeFlags('ref',                 OPT_ATT_READONLY);
        $this->ForceAttributeFlags('creation_date',       OPT_ATT_READONLY);
        $this->ForceAttributeFlags('last_update',         OPT_ATT_READONLY);
        $this->ForceAttributeFlags('approval_date',       OPT_ATT_READONLY);
        $this->ForceAttributeFlags('implementation_date', OPT_ATT_READONLY);
    }

    /**
     * Before write:
     *  - On first insert: ensure creation_date is set.
     *  - Always: update last_update (now).
     *  - Keep next_review coherent with last_review + interval when both present.
     */
    public function OnISMSControlBeforeWrite(EventData $oEventData): void
    {
        $sToday = IsmsUtils::Today();
        $sNow   = IsmsUtils::Now();

        if ($oEventData->Get('is_new') === true && empty($this->Get('creation_date'))) {
            $this->Set('creation_date', $sToday);
        }

        $this->Set('last_update', $sNow);

        $sLastReview = (string) $this->Get('last_review');
        $iMonths     = (int) $this->Get('review_interval_months');
        if ($sLastReview !== '' && $iMonths > 0) {
            $this->Set('next_review', IsmsUtils::ComputeNextReviewDate($sLastReview, $iMonths));
        }
    }

    /** Reference format for 'ref' (e.g., CTR-0001). */
    public static function GetControlRefFormat(): string
    {
        return 'CTR-%04d';
    }

    /** Build a control reference from a sequential id. */
    protected function MakeControlRef(int $iNextId): string
    {
        return sprintf(static::GetControlRefFormat(), $iNextId);
    }
}
