<?php

/**
 * @copyright   Copyright (C) 2024-2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2025-09-12
 */

namespace BR\Extension\Isms\Model;

use BR\Extension\Isms\Util\IsmsUtils;
use Combodo\iTop\Service\Events\EventData;
use Dict;
use ISMSReview;
use MetaModel;
use AttributeDate;

/**
 * ISMS Asset Review logic.
 *
 * Responsibilities:
 *  - Prefill review creation (planned date, reviewer from asset owner).
 *  - Sanity checks on review dates.
 *  - On completion, update the linked asset's last/next review dates.
 */
class _ISMSAssetReview extends ISMSReview
{

    /**
     * Prefill the creation form:
     *  - planned_on defaults to today (internal date format)
     *  - reviewer defaults to the linked Asset's owner (if present)
     *
     * @param array $aContextParam iTop context (unused)
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        // planned_on = today if empty
        if (empty($this->Get('planned_on'))) {
            $this->Set('planned_on', IsmsUtils::Today());
        }

        // reviewer = Asset.owner (if available and reviewer not set)
        try {
            $iAssetId = (int) $this->Get('asset_id');
            if ($iAssetId > 0 && empty($this->Get('reviewer_id'))) {
                $oAsset = MetaModel::GetObject('ISMSAsset', $iAssetId, false);
                if ($oAsset) {
                    $iOwner = (int) $oAsset->Get('assetowner_id');
                    if ($iOwner > 0) {
                        $this->Set('reviewer_id', $iOwner);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silent by design (prefill shouldn't break form)
        }
    }

    /**
     * Consistency checks on dates:
     *  - started_on >= planned_on (if both set)
     *  - completed_on >= started_on (if both set)
     *
     * Emits CheckIssues to block the write if invalid.
     */
    public function OnISMSAssetReviewCheckToWrite(EventData $oEventData): void
    {
        $sPlanned   = (string) $this->Get('planned_on');
        $sStarted   = (string) $this->Get('started_on');
        $sCompleted = (string) $this->Get('completed_on');

        // iTop internal date format is Y-m-d => lexical compare is safe
        if ($sStarted !== '' && $sPlanned !== '' && $sStarted < $sPlanned) {
            $this->AddCheckIssue(Dict::S('Class:ISMSReview/Check:StartedOnIsBeforePlannedOn'));
        }
        if ($sCompleted !== '' && $sStarted !== '' && $sCompleted < $sStarted) {
            // FIX: use the proper message key for completed < started
            $this->AddCheckIssue(Dict::S('Class:ISMSReview/Check:CompletedOnIsBeforeStartedOn'));
        }
    }

    /**
     * After write:
     *  When a review is completed, update the linked Asset:
     *   - last_review = completed_on (or today if empty)
     *   - next_review = last_review + review_interval_months (>=1, default 12)
     *
     * Only persists the Asset if values actually changed.
     */
    public function OnISMSAssetReviewAfterWrite(EventData $oEventData): void
    {
        $sStimulus = (string) $oEventData->Get('stimulus_applied'); // e.g. 'ev_complete'
        $sTarget   = (string) $oEventData->Get('target_state');     // e.g. 'completed'

        // Consider both: explicit complete stimulus OR transition to 'completed'
        $bCompleted = ($sStimulus === 'ev_complete') || ($sTarget === 'completed');
        if (!$bCompleted) {
            return;
        }

        $oAsset = $this->GetTargetObject();
        if (!$oAsset) {
            return;
        }

        // Determine completion date (fallback: today)
        $sCompleted = (string) $this->Get('completed_on');
        if ($sCompleted === '') {
            $sCompleted = IsmsUtils::Today();
        }

        // Compute next_review: use asset's interval or fallback 12
        $iMonths = (int) $oAsset->Get('review_interval_months');
        if ($iMonths <= 0) {
            $iMonths = IsmsUtils::GetDefaultReviewIntervalMonths();
        }
        $ts = strtotime('+' . $iMonths . ' months', strtotime($sCompleted));
        $sNext = date(AttributeDate::GetInternalFormat(), $ts);

        // Only persist if there's an actual change
        $bChanged = false;
        if ((string) $oAsset->Get('last_review') !== $sCompleted) {
            $oAsset->Set('last_review', $sCompleted);
            $bChanged = true;
        }
        if ((string) $oAsset->Get('next_review') !== $sNext) {
            $oAsset->Set('next_review', $sNext);
            $bChanged = true;
        }

        if ($bChanged) {
            $oAsset->DBUpdate();
        }
    }

    /**
     * Return the reviewed target object (ISMSAsset) or null.
     *
     * @return mixed|null ISMSAsset instance or null if not found / no id.
     */
    public function GetTargetObject()
    {
        $iId = (int) $this->Get('asset_id');
        return ($iId > 0) ? MetaModel::GetObject('ISMSAsset', $iId, false) : null;
    }
}
