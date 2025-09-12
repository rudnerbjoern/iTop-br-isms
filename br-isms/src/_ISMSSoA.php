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
        $this->RecomputeKpis();
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

    public function RecomputeKpis(): bool
    {
        $iTotal = 0;
        $iApplicable = 0;
        $iImpl = 0;
        $iGaps = 0;

        $oSearch = DBObjectSearch::FromOQL('SELECT ISMSSoAEntry WHERE soa_id = :soa');
        $oSet = new DBObjectSet($oSearch, array(), array('soa' => $this->GetKey()));

        while ($o = $oSet->Fetch()) {
            $iTotal++;
            $app  = (string) $o->Get('applicability');         // applicable | partial | not_applicable | null/""
            $impl = (string) $o->Get('implementation_status'); // planned | in_progress | implemented | not_implemented | null/""

            if ($app === 'applicable' || $app === 'partial') {
                $iApplicable++;
                if ($impl === 'implemented') {
                    $iImpl++;
                }
                // „Gaps“ = anwendbar, aber noch nicht fertig (leer / geplant / in Bearbeitung / nicht implementiert)
                if ($impl === '' || $impl === 'planned' || $impl === 'in_progress' || $impl === 'not_implemented') {
                    $iGaps++;
                }
            }
        }

        // nur setzen, wenn sich wirklich etwas geändert hat
        $bChanged = false;
        if ((int)$this->Get('kpi_total') !== $iTotal) {
            $this->Set('kpi_total', $iTotal);
            $bChanged = true;
        }
        if ((int)$this->Get('kpi_applicable') !== $iApplicable) {
            $this->Set('kpi_applicable', $iApplicable);
            $bChanged = true;
        }
        if ((int)$this->Get('kpi_implemented') !== $iImpl) {
            $this->Set('kpi_implemented', $iImpl);
            $bChanged = true;
        }
        if ((int)$this->Get('kpi_gaps') !== $iGaps) {
            $this->Set('kpi_gaps', $iGaps);
            $bChanged = true;
        }

        return $bChanged;
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
        $this->RecomputeKpis();
        $this->DBUpdate();

        return $iCreated;
    }
}
