<?php

namespace BR_isms\Extension\Framework\Model;

use BR_isms\Extension\Framework\Util\IsmsReviewUtils;
use Combodo\iTop\Service\Events\EventData;
use DBObjectSearch;
use DBObjectSet;
use Dict;
use cmdbAbstractObject;
use ISMSSoAEntry;

/**
 * Class _ISMSSoA
 *
 * KPIs summary fields (expected on the object):
 *  - kpi_total        : int
 *  - kpi_applicable   : int
 *  - kpi_implemented  : int
 *  - kpi_gaps         : int
 *
 * Related rows are ISMSSoAEntry with:
 *  - soa_id (FK to ISMSSoA)
 *  - applicability            enum: applicable | partial | not_applicable | (empty)
 *  - implementation_status    enum: planned | in_progress | implemented | not_implemented | (empty)
 */
class _ISMSSoA extends cmdbAbstractObject
{

    /**
     * Attribute flags (initial): make KPIs & effective_from read-only at creation time.
     * This runs before the object is displayed/edited the first time.
     */
    public function EvtSetInitialISMSoAAttributeFlags(EventData $oEventData): void
    {
        $this->ForceInitialAttributeFlags('effective_from',   OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('kpi_total',        OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('kpi_applicable',   OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('kpi_implemented',  OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('kpi_gaps',         OPT_ATT_READONLY);
    }

    /**
     * Attribute flags (runtime): keep KPIs & effective_from read-only at all times.
     * This is evaluated repeatedly (eg. on state changes).
     */
    public function EvtSetISMSSoaAttributeFlags(EventData $oEventData): void
    {
        $this->ForceAttributeFlags('effective_from',   OPT_ATT_READONLY);
        $this->ForceAttributeFlags('kpi_total',        OPT_ATT_READONLY);
        $this->ForceAttributeFlags('kpi_applicable',   OPT_ATT_READONLY);
        $this->ForceAttributeFlags('kpi_implemented',  OPT_ATT_READONLY);
        $this->ForceAttributeFlags('kpi_gaps',         OPT_ATT_READONLY);
    }

    /**
     * Compute derived values: recompute KPIs.
     * Keep this side-effect free (no DB writes here); caller decides if/when to save.
     */
    public function EvtISMSSoAComputeValues(EventData $oEventData): void
    {
        $this->RecomputeKpis(); // method returns bool, but here we simply compute
    }

    /**
     * Prevent approval unless all entries have a decision (applicability set).
     * Emits a CheckIssue to block the write if condition is not met.
     */
    public function EvtISMSSoACheckToWrite(EventData $oEventData): void
    {
        $sTarget = (string) $oEventData->Get('target_state');
        if ($sTarget === 'approved') {
            $oSet = new DBObjectSet(
                DBObjectSearch::FromOQL('SELECT ISMSSoAEntry WHERE soa_id = :soa AND applicability IS NULL'),
                array(),
                array('soa' => $this->GetKey())
            );
            if ($oSet->CountExceeds(0)) {
                $this->AddCheckIssue(Dict::S('Class:ISMSSoA/Check:AllEntriesDecided'));
            }
        }
    }

    /** Reentrancy guard to avoid loops if we DBUpdate() inside AFTER_WRITE */
    protected static bool $bPostCreateInProgress = false;


    public function EvtISMSSoAAfterWrite(EventData $oEventData): void
    {
        $bIsNew = (bool) $oEventData->Get('is_new');
        if (!$bIsNew) {
            return;
        }
        if (self::$bPostCreateInProgress) {
            return; // avoid re-entrancy loops
        }

        self::$bPostCreateInProgress = true;

        try {
            $iCreated = 0;
            try {
                // Create only missing entries; harmless if already populated
                $iCreated = (int) $this->PopulateEntriesFromStandard(true);
            } catch (\Exception $e) {
                // swallow to avoid breaking object creation; error is visible in logs
            }

            // Append a short note (if 'notes' is a CaseLog)
            if (
                $iCreated > 0 && MetaModel::IsValidAttCode(get_class($this), 'notes')
                && (MetaModel::GetAttributeDef(get_class($this), 'notes') instanceof AttributeCaseLog)
            ) {
                $this->Set('notes', Dict::Format('ISMSSoA:PopulatedEntries', $iCreated));
            }

            // Recompute KPIs and save only if anything changed
            $bChanged = $this->RecomputeKpis();
            // If we added a note, that's also a change
            if ($iCreated > 0) {
                $bChanged = true;
            }

            if ($bChanged) {
                $this->DBUpdate();
            }
        } finally {
            self::$bPostCreateInProgress = false;
        }
    }

    /**
     * Recompute KPIs from linked entries.
     *
     * Computes:
     *  - total:          all entries for this SoA
     *  - applicable:     entries with applicability in {applicable, partial}
     *  - implemented:    applicable entries with implementation_status = implemented
     *  - gaps:           applicable entries with implementation_status ∈ { '', planned, in_progress }
     *
     * @return bool True if at least one KPI value has changed, false otherwise.
     */
    public function RecomputeKpis(): bool
    {
        $iTotal      = 0;
        $iApplicable = 0;
        $iImpl       = 0;
        $iGaps       = 0;

        // Pull all entries for this SoA (one pass).
        $oSearch = DBObjectSearch::FromOQL('SELECT ISMSSoAEntry WHERE soa_id = :soa');
        $oSet    = new DBObjectSet($oSearch, array(), array('soa' => $this->GetKey()));

        while ($oEntry = $oSet->Fetch()) {
            $iTotal++;

            $sApp  = (string) $oEntry->Get('applicability');         // applicable | partial | not_applicable | ''
            $sImpl = (string) $oEntry->Get('implementation_status'); // planned | in_progress | implemented | not_implemented | ''

            $bApplicable = ($sApp === 'applicable' || $sApp === 'partial');
            if ($bApplicable) {
                $iApplicable++;

                if ($sImpl === 'implemented') {
                    $iImpl++;
                }

                // “Gaps” := applicable but not done yet
                if ($sImpl === '' || $sImpl === 'planned' || $sImpl === 'in_progress' || $sImpl === 'not_implemented') {
                    $iGaps++;
                }
            }
        }

        // Apply only when values actually change (prevents useless DB writes & events)
        $bChanged = false;

        if ((int) $this->Get('kpi_total') !== $iTotal) {
            $this->Set('kpi_total', $iTotal);
            $bChanged = true;
        }
        if ((int) $this->Get('kpi_applicable') !== $iApplicable) {
            $this->Set('kpi_applicable', $iApplicable);
            $bChanged = true;
        }
        if ((int) $this->Get('kpi_implemented') !== $iImpl) {
            $this->Set('kpi_implemented', $iImpl);
            $bChanged = true;
        }
        if ((int) $this->Get('kpi_gaps') !== $iGaps) {
            $this->Set('kpi_gaps', $iGaps);
            $bChanged = true;
        }

        return $bChanged;
    }

    /**
     * Create missing ISMSSoAEntry rows for every standard control of the selected standard.
     *
     * Assumptions:
     *  - The SoA has a way to reference the standard (eg. attribute `standard_id`).
     *  - The entry class is ISMSSoAEntry, with FK `standardcontrol_id` to ISMSStandardControl.
     *
     * @param bool $onlyMissing If true, do not touch existing entries (default).
     * @return int Number of entries created.
     *
     * @throws \Exception If standard cannot be determined or on DB errors.
     */
    public function PopulateEntriesFromStandard(bool $onlyMissing = true): int
    {
        // 1) Figure out which standard to use.
        //    Adjust the attcode if your datamodel differs (e.g. `standard_id` / `standard_ref`).
        $iStandardId = (int) $this->Get('standard_id');
        if ($iStandardId <= 0) {
            throw new \Exception('PopulateEntriesFromStandard: missing or invalid standard on SoA.');
        }

        // 2) Fetch all standard controls for that standard.
        $oCtlSearch = DBObjectSearch::FromOQL('SELECT ISMSStandardControl WHERE standard_id = :std');
        $oCtlSet    = new DBObjectSet($oCtlSearch, array(), array('std' => $iStandardId));

        // 3) Preload existing entries to avoid N×OQL lookups.
        //    Build a set of control IDs that already exist for this SoA.
        $aExisting = $this->ListExistingEntryControlIds();

        $iCreated = 0;

        while ($oCtl = $oCtlSet->Fetch()) {
            $iCtrlId = (int) $oCtl->GetKey();

            if ($onlyMissing && isset($aExisting[$iCtrlId])) {
                continue; // skip already present
            }

            // Create new entry, with the minimum viable data.
            /** @var ISMSSoAEntry $oEntry */
            $oEntry = MetaModel::NewObject('ISMSSoAEntry');
            $oEntry->Set('soa_id',             $this->GetKey());
            $oEntry->Set('standardcontrol_id', $iCtrlId);

            // Optional defaults (adapt to your process):
            $oEntry->Set('applicability', 'applicable');
            $oEntry->Set('implementation_status', '');

            // Insert without reloading the whole object graph
            $oEntry->DBInsertNoReload();
            $iCreated++;
        }

        return $iCreated;
    }

    /**
     * Return an associative array of existing entry control IDs for this SoA.
     * Format: [ control_id => true, ... ]
     *
     * @return array<int,bool>
     */
    protected function ListExistingEntryControlIds(): array
    {
        $aExisting = array();

        $oSearch = DBObjectSearch::FromOQL('SELECT ISMSSoAEntry WHERE soa_id = :soa');
        $oSet    = new DBObjectSet($oSearch, array(), array('soa' => $this->GetKey()));

        while ($oEntry = $oSet->Fetch()) {
            $aExisting[(int) $oEntry->Get('standardcontrol_id')] = true;
        }

        return $aExisting;
    }
}
