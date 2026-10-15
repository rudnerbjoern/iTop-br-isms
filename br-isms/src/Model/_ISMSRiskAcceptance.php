<?php

/**
 * Manage risk acceptance dates and protect approved risk assignments.
 *
 * @copyright   Copyright (C) 2024-2026 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2026-10-15
 */

namespace BR\Extension\Isms\Model;

use BR\Extension\Isms\Util\IsmsUtils;
use Combodo\iTop\Service\Events\EventData;
use Dict;
use cmdbAbstractObject;

class _ISMSRiskAcceptance extends cmdbAbstractObject
{

    /**
     * Prefill creation with sensible defaults:
     *  - creation_date = today
     *  - last_update   = now
     *
     * @param array $aContextParam iTop context (unused)
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        $sToday = IsmsUtils::Today();
        $sNow = IsmsUtils::Now();

        if (empty($this->Get('creation_date'))) {
            $this->Set('creation_date', $sToday);
        }
        if (empty($this->Get('last_update'))) {
            $this->Set('last_update', $sNow);
        }
    }


    /** Initial attribute flags at creation time (read-only system fields). */
    public function OnISMSRiskAcceptanceSetInitialAttributesFlags(EventData $oEventData): void
    {
        $this->ForceInitialAttributeFlags('approval_date', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('creation_date', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('last_update',   OPT_ATT_READONLY);
    }

    /** Runtime attribute flags (keep read-only at all times). */
    public function OnISMSRiskAcceptanceSetAttributesFlags(EventData $oEventData): void
    {
        $this->AddAttributeFlags('approval_date', OPT_ATT_READONLY);
        $this->AddAttributeFlags('creation_date', OPT_ATT_READONLY);
        $this->AddAttributeFlags('last_update',   OPT_ATT_READONLY);
    }

    /**
     * Before write:
     *  - On first insert: ensure creation_date is set.
     *  - Always: update last_update (now).
     */
    public function OnISMSRiskAcceptanceBeforeWrite(EventData $oEventData): void
    {
        $sToday = IsmsUtils::Today();
        $sNow   = IsmsUtils::Now();

        if ($oEventData->Get('is_new') === true && empty($this->Get('creation_date'))) {
            $this->Set('creation_date', $sToday);
        }

        $this->Set('last_update', $sNow);
    }

    /** Validate approval metadata and the frozen risk membership before writing. */
    public function OnISMSRiskAcceptanceCheckToWrite(EventData $oEventData): void
    {
        $aChanges = $this->ListChanges();
        $sState = (string) $this->Get('status');
        $sOriginalState = (string) ($this->GetOriginal('status') ?? $sState);
        $sStimulus = (string) $oEventData->Get('stimulus_applied');

        if (array_key_exists('approval_date', $aChanges)) {
            $sApprovalDate = (string) $this->Get('approval_date');
            $bApproving = $oEventData->Get('is_new') !== true
                && $sOriginalState === 'proposed' && $sState === 'approved'
                && $sStimulus === 'ev_approve' && $sApprovalDate === IsmsUtils::Today();
            $bResetting = $oEventData->Get('is_new') !== true
                && in_array($sOriginalState, ['revoked', 'expired'], true)
                && $sState === 'draft' && $sStimulus === 'ev_reset' && $sApprovalDate === '';
            if (!$bApproving && !$bResetting && ($sApprovalDate !== '' || $oEventData->Get('is_new') !== true)) {
                $this->AddCheckIssue(Dict::S('Class:ISMSRiskAcceptance/Check:ApprovalDateManaged'));
            }
        }

        $aFrozenStates = ['approved', 'revoked', 'expired'];
        // Only the reset transition may reopen a frozen acceptance for assignment changes.
        if ($oEventData->Get('is_new') !== true && in_array($sOriginalState, $aFrozenStates, true)
            && !in_array($sState, $aFrozenStates, true)
            && !($sState === 'draft' && in_array($sOriginalState, ['revoked', 'expired'], true) && $sStimulus === 'ev_reset')) {
            $this->AddCheckIssue(Dict::S('Class:ISMSRiskAcceptance/Check:RiskMembershipFrozen'));
        }
        if ($oEventData->Get('is_new') !== true
            && (in_array($sOriginalState, $aFrozenStates, true) || in_array($sState, $aFrozenStates, true))
            && array_key_exists('risks_list', $aChanges)) {
            $oRisks = $this->Get('risks_list');
            $oOriginalRisks = $oRisks->GetOriginalSet();
            $aOriginalIds = $oOriginalRisks === null ? [] : array_values($oOriginalRisks->GetColumnAsArray('id'));
            $aCurrentIds = array_values($oRisks->GetColumnAsArray('id'));
            sort($aOriginalIds, SORT_NUMERIC);
            sort($aCurrentIds, SORT_NUMERIC);
            // A linked risk may still be edited; only membership additions/removals are blocked.
            if ($aOriginalIds != $aCurrentIds) {
                $this->AddCheckIssue(Dict::S('Class:ISMSRiskAcceptance/Check:RiskMembershipFrozen'));
            }
        }
    }
}
