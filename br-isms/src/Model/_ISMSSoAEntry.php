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
use cmdbAbstractObject;
use MetaModel;

class _ISMSSoAEntry extends cmdbAbstractObject
{

    /** @var array<int,bool> SoA IDs to recompute after deletions (deduplicated per request). */
    protected static array $aSoaToRecompute = [];

    public function OnISMSSoAEntryCheckToWrite(EventData $oEventData): void
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

    /**
     * After write (insert or update): recompute current SoA KPIs.
     * If the entry was moved to a different SoA, recompute the previous one as well.
     */
    public function OnISMSSoAEntryAfterWrite(EventData $oEventData): void
    {
        $iSoaId = (int) $this->Get('soa_id');
        if ($iSoaId > 0) {
            $oSoa = MetaModel::GetObject('ISMSSoA', $iSoaId, false);
            if ($oSoa && $oSoa->RecomputeKpis()) {
                $oSoa->DBUpdate();
            }
        }

        // Also recompute old SoA if the link has changed
        $aPrev = (array) $oEventData->Get('previous_values');
        $iPrevSoaId = (int) ($aPrev['soa_id'] ?? 0);
        if ($iPrevSoaId > 0 && $iPrevSoaId !== $iSoaId) {
            $oPrev = MetaModel::GetObject('ISMSSoA', $iPrevSoaId, false);
            if ($oPrev && $oPrev->RecomputeKpis()) {
                $oPrev->DBUpdate();
            }
        }
    }

    /**
     * About to delete: remember the owning SoA ID.
     * After deletion we can no longer read the row; we recompute in EvtAfterDelete().
     */
    public function OnISMSSoAEntryAboutToDelete(EventData $oEventData): void
    {
        $iSoaId = (int) $this->Get('soa_id');
        if ($iSoaId > 0) {
            self::$aSoaToRecompute[$iSoaId] = true;
        }
    }

    /**
     * After delete: recompute KPIs for all SoAs that had entries removed in this request.
     * We deduplicate via a static array to be efficient during mass deletes.
     */
    public function OnISMSSoAEntryAfterDelete(EventData $oEventData): void
    {
        if (empty(self::$aSoaToRecompute)) {
            return;
        }
        foreach (array_keys(self::$aSoaToRecompute) as $iSoaId) {
            $oSoa = MetaModel::GetObject('ISMSSoA', $iSoaId, false);
            if ($oSoa && $oSoa->RecomputeKpis()) {
                $oSoa->DBUpdate();
            }
        }
        self::$aSoaToRecompute = [];
    }
}
