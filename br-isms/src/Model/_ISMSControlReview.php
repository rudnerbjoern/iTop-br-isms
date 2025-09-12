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
 * ISMS Control Review logic.
 *
 * Responsibilities:
 *  - Prefill review creation (planned date, reviewer from control owner).
 *  - Sanity checks on review dates.
 *  - On completion, update the linked control's last/next review dates.
 */
class _ISMSControlReview extends ISMSReview
{

    /**
     * Prefill the creation form:
     *  - planned_on defaults to today (internal date format)
     *  - reviewer defaults to the linked Control's owner (if present)
     *
     * @param array $aContextParam iTop context (unused)
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        // planned_on = today if empty
        if (empty($this->Get('planned_on'))) {
            $this->Set('planned_on', IsmsUtils::Today());
        }

        // reviewer = Control.owner (if available and reviewer not set)
        try {
            $iControlId = (int) $this->Get('control_id');
            if ($iControlId > 0 && empty($this->Get('reviewer_id'))) {
                $oControl = MetaModel::GetObject('ISMSControl', $iControlId, false);
                if ($oControl) {
                    $iOwner = (int) $oControl->Get('controlowner_id');
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
    public function EvtControlReviewCheckToWrite(EventData $oEventData): void
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
     *  When a review is completed, update the linked Control:
     *   - last_review = completed_on (or today if empty)
     *   - next_review = last_review + review_interval_months (>=1, fallback to module setting / 12)
     *
     * Only persists the Control if values actually changed.
     */
    public function EvtControlReviewAfterWrite(EventData $oEventData): void
    {
        $sStimulus = (string) $oEventData->Get('stimulus_applied'); // e.g. 'ev_complete'
        $sTarget   = (string) $oEventData->Get('target_state');     // e.g. 'completed'

        // Consider both: explicit complete stimulus OR transition to 'completed'
        $bCompleted = ($sStimulus === 'ev_complete') || ($sTarget === 'completed');
        if (!$bCompleted) {
            return;
        }

        $oControl = $this->GetTargetObject();
        if (!$oControl) {
            return;
        }

        // Determine completion date (fallback: today, internal format)
        $sCompleted = (string) $this->Get('completed_on');
        if ($sCompleted === '') {
            $sCompleted = IsmsUtils::Today();
        }

        // Compute next_review based on the control's interval or module default
        $iMonths = (int) $oControl->Get('review_interval_months');
        if ($iMonths <= 0) {
            $iMonths = IsmsUtils::GetDefaultReviewIntervalMonths();
        }
        $ts   = strtotime('+' . $iMonths . ' months', strtotime($sCompleted));
        $sNext = date(AttributeDate::GetInternalFormat(), $ts);

        // Only persist if values actually changed
        $bChanged = false;
        if ((string) $oControl->Get('last_review') !== $sCompleted) {
            $oControl->Set('last_review', $sCompleted);
            $bChanged = true;
        }
        if ((string) $oControl->Get('next_review') !== $sNext) {
            $oControl->Set('next_review', $sNext);
            $bChanged = true;
        }

        if ($bChanged) {
            $oControl->DBUpdate();
        }
    }

    /**
     * Return the reviewed target object (ISMSControl) or null.
     *
     * @return mixed|null ISMSControl instance or null if not found / no id.
     */
    public function GetTargetObject()
    {
        $iId = (int) $this->Get('control_id');
        return ($iId > 0) ? MetaModel::GetObject('ISMSControl', $iId, false) : null;
    }
}
