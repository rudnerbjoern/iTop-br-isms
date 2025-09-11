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

class _ISMSAssetReview extends ISMSReview
{

    /**
     * PrefillCreationForm
     * - Default planned_on = today
     * - If empty, preset reviewer to the asset owner (if present)
     *
     * @param [type] $aContextParam
     * @return void
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        if (empty($this->Get('planned_on'))) {
            $this->Set('planned_on', date('Y-m-d'));
        }
        // reviewer = Asset.owner (falls vorhanden)
        try {
            $iAssetId = (int)$this->Get('asset_id');
            if ($iAssetId > 0 && empty($this->Get('reviewer_id'))) {
                $oAsset = MetaModel::GetObject('ISMSAsset', $iAssetId, false);
                if ($oAsset) {
                    $iOwner = (int)$oAsset->Get('assetowner_id');
                    if ($iOwner > 0) {
                        $this->Set('reviewer_id', $iOwner);
                    }
                }
            }
        } catch (\Exception $e) { /* ignore */
        }
    }

    public function EvtAssetReviewCheckToWrite(EventData $oEventData): void
    {
        // Zeitliche Konsistenz
        $sPlanned = (string) $this->Get('planned_on');
        $sStarted = (string) $this->Get('started_on');
        $sCompleted = (string) $this->Get('completed_on');
        if (!empty($sStarted) && !empty($sPlanned) && $sStarted < $sPlanned) {
            $this->AddCheckIssue(Dict::S('Class:ISMSReview/Check:StartedOnIsBeforePlannedOn'));
        }
        if (!empty($sCompleted) && (!empty($sStarted) && $sCompleted < $sStarted)) {
            $this->AddCheckIssue(Dict::S('Class:ISMSReview/Check:StartedOnIsBeforePlannedOn'));
        }
    }

    public function EvtAssetReviewAfterWrite(EventData $oEventData): void
    {
        $sStimulus = (string) $oEventData->Get('stimulus_applied'); // z. B. 'ev_complete'
        $sTarget   = (string) $oEventData->Get('target_state');     // z. B. 'completed'

        if ($sStimulus === 'ev_complete') {

            $oAsset = $this->GetTargetObject();
            if ($oAsset) {
                $sCompleted = $this->Get('completed_on');
                if (empty($sCompleted)) {
                    $sCompleted = date('Y-m-d');
                }

                // last_review aktualisieren
                $oAsset->Set('last_review', $sCompleted);

                // Intervall holen (Fallback 12), next_review berechnen
                $iMonths = (int)$oAsset->Get('review_interval_months');
                if ($iMonths <= 0) {
                    $iMonths = 12;
                }

                $ts = strtotime('+' . $iMonths . ' months', strtotime($sCompleted));
                $oAsset->Set('next_review', date('Y-m-d', $ts));

                $oAsset->DBUpdate();
            }
        }
    }

    public function GetTargetObject()
    {
        $iId = (int)$this->Get('asset_id');
        return ($iId > 0) ? MetaModel::GetObject('ISMSAsset', $iId, false) : null;
    }
}
