<?php

namespace BR_isms\Extension\Framework\Model;

use Combodo\iTop\Service\Events\EventData;
use DBObjectSearch;
use DBObjectSet;
use Dict;
use cmdbAbstractObject;
use ISMSSoAEntry;
use WebPage;
use ItopCounter;
use MetaModel;
use AttributeDate;
use AttributeDateTime;

class _ISMSSoA extends cmdbAbstractObject
{

    public function EvtSetInitialISMSoAAttributeFlags(EventData $oEventData): void
    {
        $this->ForceInitialAttributeFlags('effective_from', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('kpi_total', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('kpi_applicable', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('kpi_implemented', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('kpi_gaps', OPT_ATT_READONLY);
    }

    public function EvtSetISMSSoaAttributeFlags(EventData $oEventData): void
    {
        $this->ForceAttributeFlags('effective_from', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('kpi_total', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('kpi_applicable', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('kpi_implemented', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('kpi_gaps', OPT_ATT_READONLY);
    }

    public function EvtISMSSoAComputeValues(EventData $oEventData): void
    {
        $this->RecomputeKPIs();
    }

    public function EvtISMSSoACheckToWrite(EventData $oEventData): void
    {
        $target = (string)$oEventData->Get('target_state');
        if ($target === 'approved') {
            // Alle Einträge müssen entschieden sein
            $oSet = new DBObjectSet(DBObjectSearch::FromOQL(
                'SELECT ISMSSoAEntry WHERE soa_id = :soa AND applicability IS NULL'
            ), array(), array('soa' => $this->GetKey()));
            if ($oSet->CountExceeds(0)) {
                $this->AddCheckIssue(Dict::S('Class:ISMSSoA/Check:AllEntriesDecided'));
            }
        }
    }

    public function EvtISMSSoAAfterWrite(EventData $oEventData): void
    {
        if ($oEventData->Get('is_new') === true) {
            //try {
            (int)$iCreated = $this->PopulateEntriesFromStandard(true);
            // Optional: Log/Note
            if ($iCreated > 0) {

                $this->Set('notes', Dict::Format('ISMSSoA:PopulatedEntries', $iCreated));
                $this->DBUpdate();
            }
            //} catch (\Exception $e) {
            //    // swallow to avoid breaking object creation; admin can check logs
            //}
        }
    }

    public function RecomputeKPIs(): void
    {
        (int)$iTotal = 0;
        (int)$iApplicable = 0;
        (int)$iImpl = 0;
        (int)$iGaps = 0;
        $oSearch = DBObjectSearch::FromOQL('SELECT ISMSSoAEntry WHERE soa_id = :soa');
        $oSet = new DBObjectSet($oSearch, array(), array('soa' => $this->GetKey()));
        while ($o = $oSet->Fetch()) {
            $iTotal++;
            $app = (string)$o->Get('applicability');
            $impl = (string)$o->Get('implementation_status');
            if ($app === 'applicable' || $app === 'partial') {
                $iApplicable++;
                if ($impl === 'implemented')
                    $iImpl++;
                if ($impl === '' || $impl === 'planned' || $impl === 'in_progress')
                    $iGaps++;
            }
        }
        $this->Set('kpi_total', $iTotal);
        $this->Set('kpi_applicable', $iApplicable);
        $this->Set('kpi_implemented', $iImpl);
        $this->Set('kpi_gaps', $iGaps);
    }

    public function PopulateEntriesFromStandard($bOnlyMissing = true): int
    {
        $iCreated = 0;
        $iSoaId = (int) $this->GetKey();
        $iStdId = (int) $this->Get('standard_id');
        if ($iStdId <= 0) return 0;

        $oCtrlSet = new DBObjectSet(
            DBObjectSearch::FromOQL('SELECT ISMSStandardControl WHERE standard_id = :std'),
            array(),
            array('std' => $iStdId)
        );

        while ($oCtrl = $oCtrlSet->Fetch()) {
            $iCtrlId = (int) $oCtrl->GetKey();

            if ($bOnlyMissing) {
                $oExists = new DBObjectSet(
                    DBObjectSearch::FromOQL('SELECT ISMSSoAEntry WHERE soa_id = :soa AND standardcontrol_id = :ctrl'),
                    array(),
                    array('soa' => $iSoaId, 'ctrl' => $iCtrlId)
                );
                if ($oExists->CountExceeds(0)) {
                    continue;
                }
            }

            $oEntry = new ISMSSoAEntry();
            $oEntry->Set('soa_id', $iSoaId);
            $oEntry->Set('standardcontrol_id', $iCtrlId);
            $oEntry->Set('applicability', 'applicable');
            $oEntry->Set('implementation_status', 'planned');
            $oEntry->DBInsert();
            $iCreated++;
        }

        // KPIs neu berechnen
        $this->RecomputeKPIs();
        $this->DBUpdate();

        return $iCreated;
    }
}
