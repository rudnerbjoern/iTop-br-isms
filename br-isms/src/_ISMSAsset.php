<?php

namespace BR_isms\Extension\Framework\Model;

use BR_isms\Extension\Framework\Util\IsmsReviewUtils;
use AttributeDate;
use AttributeDateTime;
use Combodo\iTop\Service\Events\EventData;
use DBObjectSearch;
use DBObjectSet;
use FunctionalCI;
use ItopCounter;
use MetaModel;
use WebPage;

/**
 * ISMS Asset business logic and UI tweaks.
 *
 * Responsibilities:
 *  - Prefill creation defaults (dates, review cadence).
 *  - Keep meta dates consistent (creation_date, last_update).
 *  - Generate a readable asset reference (ref) using ItopCounter.
 *  - Tweak relations tab layout (remove opened tickets tab).
 *
 * Notes:
 *  - Keep event handlers idempotent (safe on repeated calls).
 *  - Avoid DBUpdate() in compute/prefill; let the platform persist on save.
 */
class _ISMSAsset extends FunctionalCI
{

    /**
     * Prefill the creation form with sensible defaults.
     * - creation_date = today (internal date format)
     * - last_update   = now   (internal datetime format)
     * - last_review   = today (so next_review can be derived)
     * - review_interval_months from module settings (fallback 12)
     * - next_review computed from last_review + interval
     *
     * @param array $aContextParam iTop context (unused)
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        $sToday = IsmsReviewUtils::Today();
        $sNow = IsmsReviewUtils::Now();

        if (empty($this->Get('creation_date'))) {
            $this->Set('creation_date', $sToday);
        }

        if (empty($this->Get('last_update'))) {
            $this->Set('last_update', $sNow);
        }

        if (empty($this->Get('last_review'))) {
            $this->Set('last_review', $sToday);
        }

        if (empty($this->Get('review_interval_months'))) {
            $this->Set('review_interval_months', IsmsReviewUtils::GetDefaultReviewIntervalMonths());
        }

        // next_review: compute if empty, using last_review as the anchor
        if (empty($this->Get('next_review'))) {
            $iMonths = max(1, (int) $this->Get('review_interval_months'));
            $anchor  = $this->Get('last_review') ?: $sToday;
            $this->Set('next_review', IsmsReviewUtils::ComputeNextReviewDate($anchor, $iMonths));
        }
    }

    /**
     * Hide tabs we do not use for ISMS assets.
     * Currently removes FunctionalCI's "Opened Tickets" tab.
     */
    public function DisplayBareRelations(WebPage $oPage, $bEditMode = false): void
    {
        parent::DisplayBareRelations($oPage, $bEditMode);

        // Tab code is the Dict key (expected by RemoveTab)
        $oPage->RemoveTab('Class:FunctionalCI/Tab:OpenedTickets');
    }

    /**
     * Assign a reference if missing and insert without reloading the object.
     *
     * @return int The database primary key of the inserted row.
     */
    public function DBInsertNoReload(): int
    {
        // Generate a sequential id and format the ref, only if ref is empty
        if (empty($this->Get('ref'))) {
            $iNextId = ItopCounter::Inc('ISMSAsset');
            $this->Set('ref', $this->MakeAssetRef($iNextId));
        }
        return parent::DBInsertNoReload();
    }

    /**
     * Initial attribute flags at creation time.
     * Make identity and system dates read-only in the initial form.
     */
    public function EvtSetInitialISMSAssetAttributeFlags(EventData $oEventData): void
    {
        $this->ForceInitialAttributeFlags('ref',           OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('creation_date', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('last_update',   OPT_ATT_READONLY);
    }

    /**
     * Runtime attribute flags (evaluated repeatedly).
     * Keep identity and system dates read-only at all times.
     */
    public function EvtSetISMSAssetAttributeFlags(EventData $oEventData): void
    {
        $this->ForceAttributeFlags('ref',           OPT_ATT_READONLY);
        $this->ForceAttributeFlags('creation_date', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('last_update',   OPT_ATT_READONLY);
    }

    /**
     * Before write: maintain meta dates and keep next_review coherent.
     * - On first insert: ensure creation_date is set.
     * - On every save:   update last_update (now).
     * - If last_review or review_interval_months are present, ensure next_review is aligned.
     */
    public function EvtBeforeISMSAssetWrite(EventData $oEventData): void
    {
        $sToday = IsmsReviewUtils::Today();
        $sNow = IsmsReviewUtils::Now();

        // set creation date on initial write
        if ($oEventData->Get('is_new') === true && empty($this->Get('creation_date'))) {
            $this->Set('creation_date', $sToday);
        }

        // Always bump last_update
        $this->Set('last_update', $sNow);

        // Keep next_review coherent (cheap to recompute; avoids stale dates)
        $sLastReview = (string) $this->Get('last_review');
        $iMonths     = (int) $this->Get('review_interval_months');

        if ($sLastReview !== '' && $iMonths > 0) {
            $this->Set('next_review', IsmsReviewUtils::ComputeNextReviewDate($sLastReview, $iMonths));
        }
    }

    /**
     * Reference format used for the 'ref' attribute.
     * Example: ASS-0001
     */
    public static function GetAssetRefFormat(): string
    {
        return 'ASS-%04d';
    }

    /**
     * Build an asset reference label from a sequential id.
     */
    protected function MakeAssetRef(int $iNextId): string
    {
        return sprintf(static::GetAssetRefFormat(), $iNextId);
    }
}
