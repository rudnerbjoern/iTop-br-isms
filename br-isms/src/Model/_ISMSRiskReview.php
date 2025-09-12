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
 * ISMS Risk Review logic.
 *
 * Responsibilities:
 *  - Prefill review creation (planned date, reviewer from risk owner).
 *  - Sanity checks on review dates.
 *  - On completion, update the linked risk's last/next review dates.
 */
class _ISMSRiskReview extends ISMSReview
{

    /**
     * Prefill the creation form:
     *  - planned_on defaults to today (internal date format)
     *  - reviewer defaults to the linked Risk's owner (if present)
     *
     * @param array $aContextParam iTop context (unused)
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        // planned_on = today if empty
        if (empty($this->Get('planned_on'))) {
            $this->Set('planned_on', IsmsUtils::Today());
        }

        // reviewer = Risk.owner (if available and reviewer not set)
        try {
            $iRiskId = (int) $this->Get('risk_id');
            if ($iRiskId > 0 && empty($this->Get('reviewer_id'))) {
                $oRisk = MetaModel::GetObject('ISMSRisk', $iRiskId, false);
                if ($oRisk) {
                    $iOwner = (int) $oRisk->Get('riskowner_id');
                    if ($iOwner > 0) {
                        $this->Set('reviewer_id', $iOwner);
                    }
                }
            }
        } catch (\Exception $e) {
            // Prefill must not break the form; ignore.
        }
    }

    /**
     * Consistency checks on dates:
     *  - started_on >= planned_on (if both set)
     *  - completed_on >= started_on (if both set)
     *
     * Emits CheckIssues to block the write if invalid.
     */
    public function EvtRiskReviewCheckToWrite(EventData $oEventData): void
    {
        $sPlanned   = (string) $this->Get('planned_on');
        $sStarted   = (string) $this->Get('started_on');
        $sCompleted = (string) $this->Get('completed_on');

        // Internal dates are Y-m-d → lexical compare is fine
        if ($sStarted !== '' && $sPlanned !== '' && $sStarted < $sPlanned) {
            $this->AddCheckIssue(Dict::S('Class:ISMSReview/Check:StartedOnIsBeforePlannedOn'));
        }
        if ($sCompleted !== '' && $sStarted !== '' && $sCompleted < $sStarted) {
            $this->AddCheckIssue(Dict::S('Class:ISMSReview/Check:CompletedOnIsBeforeStartedOn'));
        }
    }

    /**
     * After write:
     *  When a review is completed, update the linked Risk:
     *   - last_review = completed_on (or today if empty)
     *   - next_review = last_review + review_interval_months (>=1, fallback to module setting / 12)
     *
     * Only persists the Risk if values actually changed.
     */
    public function EvtRiskReviewAfterWrite(EventData $oEventData): void
    {
        $sStimulus = (string) $oEventData->Get('stimulus_applied'); // e.g. 'ev_complete'
        $sTarget   = (string) $oEventData->Get('target_state');     // e.g. 'completed'

        // Consider both: explicit complete stimulus OR transition to 'completed'
        $bCompleted = ($sStimulus === 'ev_complete') || ($sTarget === 'completed');
        if (!$bCompleted) {
            return;
        }

        $oRisk = $this->GetTargetObject();
        if (!$oRisk) {
            return;
        }

        // Determine completion date (fallback: today, internal format)
        $sCompleted = (string) $this->Get('completed_on');
        if ($sCompleted === '') {
            $sCompleted = IsmsUtils::Today();
        }

        // Compute next_review based on the risk's interval or module default
        $iMonths = (int) $oRisk->Get('review_interval_months');
        if ($iMonths <= 0) {
            $iMonths = IsmsUtils::GetDefaultReviewIntervalMonths();
        }
        $ts    = strtotime('+' . $iMonths . ' months', strtotime($sCompleted));
        $sNext = date(AttributeDate::GetInternalFormat(), $ts);

        // Only persist if values actually changed
        $bChanged = false;
        if ((string) $oRisk->Get('last_review') !== $sCompleted) {
            $oRisk->Set('last_review', $sCompleted);
            $bChanged = true;
        }
        if ((string) $oRisk->Get('next_review') !== $sNext) {
            $oRisk->Set('next_review', $sNext);
            $bChanged = true;
        }

        if ($bChanged) {
            $oRisk->DBUpdate();
        }
    }

    /**
     * Return the reviewed target object (ISMSRisk) or null.
     *
     * @return mixed|null ISMSRisk instance or null if not found / no id.
     */
    public function GetTargetObject()
    {
        $iId = (int) $this->Get('risk_id');
        return ($iId > 0) ? MetaModel::GetObject('ISMSRisk', $iId, false) : null;
    }
}
