<?php

namespace BR_isms\Extension\Framework\Model;

use Combodo\iTop\Service\Events\EventData;
use DBObjectSearch;
use DBObjectSet;
use Dict;
use ISMSReview;
use WebPage;
use ItopCounter;
use MetaModel;
use AttributeDate;
use AttributeDateTime;

class _ISMSControlReview extends ISMSReview
{

    /**
     * PrefillCreationForm
     * - Default planned_on = today
     * - If empty, preset reviewer to the control owner (if present)
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        if (empty($this->Get('planned_on'))) {
            $this->Set('planned_on', date('Y-m-d'));
        }
        // reviewer = Control.owner (falls vorhanden)
        try {
            $iControlId = (int)$this->Get('control_id');
            if ($iControlId > 0 && empty($this->Get('reviewer_id'))) {
                $oControl = MetaModel::GetObject('ISMSControl', $iControlId, false);
                if ($oControl) {
                    $iOwner = (int)$oControl->Get('controlowner_id');
                    if ($iOwner > 0) {
                        $this->Set('reviewer_id', $iOwner);
                    }
                }
            }
        } catch (\Exception $e) { /* ignore */
        }
    }

    public function EvtControlReviewCheckToWrite(EventData $oEventData): void
    {
        // Zeitliche Konsistenz
        $sPlanned = (string) $this->Get('planned_on');
        $sStarted = (string) $this->Get('started_on');
        $sCompleted = (string) $this->Get('completed_on');
        if (!empty($sStarted) && !empty($sPlanned) && $sStarted < $sPlanned) {
            $this->AddCheckIssue(Dict::S('Class:ISMSReview/Check:StartedOnIsBeforePlannedOn'));
        }
        if (!empty($sCompleted) && (!empty($sStarted) && $sCompleted < $sStarted)) {
            $this->AddCheckIssue(Dict::S('Class:ISMSReview/Check:CompletedOnIsBeforeStartedOn'));
        }
    }

    public function EvtControlReviewAfterWrite(EventData $oEventData): void
    {
        $sStimulus = (string) $oEventData->Get('stimulus_applied'); // z. B. 'ev_complete'
        $sTarget   = (string) $oEventData->Get('target_state');     // z. B. 'completed'

        if ($sStimulus === 'ev_complete') {

            $oControl = $this->GetTargetObject();
            if ($oControl) {
                $sCompleted = $this->Get('completed_on');
                if (empty($sCompleted)) {
                    $sCompleted = date('Y-m-d');
                }

                // last_review aktualisieren
                $oControl->Set('last_review', $sCompleted);

                // Intervall holen (Fallback 12), next_review berechnen
                $iMonths = (int)$oControl->Get('review_interval_months');
                if ($iMonths <= 0) {
                    $iMonths = 12;
                }

                $ts = strtotime('+' . $iMonths . ' months', strtotime($sCompleted));
                $oControl->Set('next_review', date('Y-m-d', $ts));

                $oControl->DBUpdate();
            }
        }
    }

    public function GetTargetObject()
    {
        $iId = (int)$this->Get('control_id');
        return ($iId > 0) ? MetaModel::GetObject('ISMSControl', $iId, false) : null;
    }
}
