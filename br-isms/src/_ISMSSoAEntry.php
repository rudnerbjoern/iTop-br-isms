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

    /** @var array<int,bool> SoA-IDs, die nach Löschung recomputed werden sollen (pro Request) */
    protected static array $aSoaToRecompute = [];

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

    public function EvtISMSSoAEntryAfterWrite(EventData $oEventData): void
    {
        // aktuelle SoA neu berechnen
        $iSoaId = (int) $this->Get('soa_id');
        if ($iSoaId > 0) {
            $oSoa = MetaModel::GetObject('ISMSSoA', $iSoaId, false);
            if ($oSoa && $oSoa->RecomputeKpis()) {
                $oSoa->DBUpdate();
            }
        }

        // falls der Eintrag die SoA gewechselt hat: alte SoA ebenfalls neu berechnen
        $aPrev = (array) $oEventData->Get('previous_values');
        $iPrevSoaId = (int) ($aPrev['soa_id'] ?? 0);
        if ($iPrevSoaId > 0 && $iPrevSoaId !== $iSoaId) {
            $oPrev = MetaModel::GetObject('ISMSSoA', $iPrevSoaId, false);
            if ($oPrev && $oPrev->RecomputeKpis()) {
                $oPrev->DBUpdate();
            }
        }
    }

    public function EvtISMSSoAEntryAboutToDelete(EventData $oEventData): void
    {
        $iSoaId = (int) $this->Get('soa_id');
        if ($iSoaId > 0) {
            self::$aSoaToRecompute[$iSoaId] = true;
        }
    }
    public function EvtISMSSoAEntryAfterDelete(EventData $oEventData): void
    {
        if (empty(self::$aSoaToRecompute)) {
            return;
        }
        foreach (array_keys(self::$aSoaToRecompute) as $iSoaId) {
            $oSoa = MetaModel::GetObject('ISMSSoA', (int)$iSoaId, false);
            if ($oSoa && $oSoa->RecomputeKpis()) {
                $oSoa->DBUpdate();
            }
        }
        self::$aSoaToRecompute = [];
    }
}
