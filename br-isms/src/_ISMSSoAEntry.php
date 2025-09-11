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

class _ISMSSoAEntry extends cmdbAbstractObject
{


    public function EvtISMSSoAEntryCheckToWrite(EventData $oEventData): void
    {
        $app = (string)$this->Get('applicability');
        if ($app === 'not_applicable' && trim((string)$this->Get('justification')) === '') {
            $this->AddCheckIssue(Dict::S('Class:ISMSSoAEntry/Check:NoJustificationForNA'));
        }
        if (($app === 'applicable' || $app === 'partial')
            && (string)$this->Get('implementation_status') === 'implemented'
            && trim((string)$this->Get('evidence')) === ''
        ) {
            $this->AddCheckIssue(Dict::S('Class:ISMSSoAEntry/Check:NoEvidence'));
        }
    }
}
