<?php

namespace BR_isms\Extension\Framework\Model;

use Combodo\iTop\Service\Events\EventData;
use DBObjectSearch;
use DBObjectSet;
use Dict;
use cmdbAbstractObject;
use WebPage;
use ItopCounter;
use MetaModel;
use AttributeDate;
use AttributeDateTime;

class _ISMSReview extends cmdbAbstractObject
{

    public function EvtOnStateTransition(EventData $oEventData): void
    {
        $sTo = (string) $oEventData->Get('to_state');

        if ($sTo === 'in_progress' && empty($this->Get('started_on'))) {
            $this->Set('started_on', date('Y-m-d'));
        }
        if ($sTo === 'completed' && empty($this->Get('completed_on'))) {
            $this->Set('completed_on', date('Y-m-d'));
        }
        if ($sTo === 'completed' && method_exists($this, 'OnReviewCompleted')) {
            $this->OnReviewCompleted();
        }
    }

    public function EvtReviewCheckToWrite(EventData $oEventData): void
    {
        $sCurrentStatus = (string) $this->Get('status');
        $sTargetState   = (string) $oEventData->Get('target_state'); // '' or 'planned'|'in_progress'|'completed'|'cancelled'
        $sStatusAfter   = $sTargetState !== '' ? $sTargetState : $sCurrentStatus;

        if (($oEventData->Get('is_new') === true) || ($sStatusAfter === 'planned')) {
            $sPlanned = (string) $this->Get('planned_on'); // 'Y-m-d'
            if ($sPlanned !== '') {
                $tsPlanned = @strtotime($sPlanned);
                $tsToday   = @strtotime(date(AttributeDate::GetInternalFormat())); // 'Y-m-d' today
                if ($tsPlanned !== false && $tsPlanned < $tsToday) {
                    $this->AddCheckIssue(Dict::S('Class:ISMSReview/Check:PlannedInPast'));
                }
            }
        }

        $sTarget = (string) $oEventData->Get('target_state');
        if ($sTarget === 'completed' && empty($this->Get('outcome'))) {
            $this->AddCheckIssue(Dict::S('Class:ISMSReview/Check:NoOutcome'));
        }
    }

    public function GetTargetObject()
    {
        return null;
    }
}
