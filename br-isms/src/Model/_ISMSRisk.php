<?php

/**
 * @copyright   Copyright (C) 2024-2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2025-09-12
 */

namespace BR\Extension\Isms\Model;

use BR\Extension\Isms\Util\IsmsUtils;
use Combodo\iTop\Service\Events\EventData;
use DBObjectSearch;
use DBObjectSet;
use Dict;
use cmdbAbstractObject;
use ItopCounter;
use MetaModel;

/**
 * ISMS Risk business logic.
 *
 * Responsibilities:
 *  - Generate readable risk reference (ref) via ItopCounter.
 *  - Prefill creation defaults (dates, review cadence).
 *  - Maintain meta dates on save.
 *  - Keep computed fields read-only (scores/levels, refs, system dates).
 *  - Recompute scores/levels (inherent/residual/target) deterministically.
 *  - Aggregate control effectiveness with configurable modes.
 */
class _ISMSRisk extends cmdbAbstractObject
{

    /**
     * Assign a reference if missing and insert without reloading the object.
     *
     * @return int The database primary key of the inserted row.
     */
    public function DBInsertNoReload(): int
    {
        if ($this->Get('ref') === '') {
            $iNextId = ItopCounter::Inc('ISMSRisk');
            $this->Set('ref', $this->MakeRiskRef($iNextId));
        }
        return parent::DBInsertNoReload();
    }

    /**
     * Prefill creation with sensible defaults:
     *  - creation_date = today
     *  - last_update   = now
     *  - last_review   = today
     *  - review_interval_months from module settings (fallback 12)
     *  - next_review derived from last_review + interval
     *
     * @param array $aContextParam iTop context (unused)
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        $sToday = IsmsUtils::Today();
        $sNow = IsmsUtils::Now();

        if ($this->Get('creation_date') === '') {
            $this->Set('creation_date', $sToday);
        }
        if ($this->Get('last_update') === '') {
            $this->Set('last_update', $sNow);
        }
        if ($this->Get('last_review') === '') {
            $this->Set('last_review', $sToday);
        }
        if ((int) $this->Get('review_interval_months') <= 0) {
            $this->Set('review_interval_months', IsmsUtils::GetDefaultReviewIntervalMonths());
        }

        if ($this->Get('next_review') === '') {
            $iMonths = max(1, (int) $this->Get('review_interval_months'));
            $anchor  = (string) $this->Get('last_review') ?: $sToday;
            $this->Set('next_review', IsmsUtils::ComputeNextReviewDate($anchor, $iMonths));
        }
    }

    /** Initial attribute flags at creation time (read-only computed/system fields). */
    public function EvtSetInitialISMSRiskAttributeFlags(EventData $oEventData): void
    {
        $this->ForceInitialAttributeFlags('ref',           OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('pre_score',     OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('pre_level',     OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('res_score',     OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('res_level',     OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('tgt_score',     OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('tgt_level',     OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('creation_date', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('last_update',   OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('publish_date',  OPT_ATT_READONLY);
    }

    /** Runtime attribute flags (keep read-only at all times; optionally lock residual inputs when effective controls exist). */
    public function EvtSetISMSRiskAttributeFlags(EventData $oEventData): void
    {
        $this->ForceAttributeFlags('ref',           OPT_ATT_READONLY);
        $this->ForceAttributeFlags('pre_score',     OPT_ATT_READONLY);
        $this->ForceAttributeFlags('pre_level',     OPT_ATT_READONLY);
        $this->ForceAttributeFlags('res_score',     OPT_ATT_READONLY);
        $this->ForceAttributeFlags('res_level',     OPT_ATT_READONLY);
        $this->ForceAttributeFlags('tgt_score',     OPT_ATT_READONLY);
        $this->ForceAttributeFlags('tgt_level',     OPT_ATT_READONLY);
        $this->ForceAttributeFlags('creation_date', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('last_update',   OPT_ATT_READONLY);
        $this->ForceAttributeFlags('publish_date',  OPT_ATT_READONLY);

        // Optional: once effective controls influence residuals, lock res_likelihood/res_impact
        try {
            $aAgg = $this->AggregateControlEffects();
            $bHasAnyEffect = (!is_null($aAgg['like']) || !is_null($aAgg['impact']));
            if ($bHasAnyEffect) {
                $this->ForceAttributeFlags('res_likelihood', OPT_ATT_READONLY);
                $this->ForceAttributeFlags('res_impact',     OPT_ATT_READONLY);
            }
        } catch (\Exception $e) {
            // ignore (flagging must not break the UI)
        }
    }

    /** When links change, recompute scores. */
    public function EvtISMSRiskLinksChanged(EventData $oEventData): void
    {
        $this->RecomputeRiskScores();
    }

    /**
     * Before write:
     *  - On first insert: ensure creation_date is set.
     *  - Always: update last_update (now).
     *  - Recompute scores to persist consistent derived values.
     */
    public function EvtBeforeISMSRiskWrite(EventData $oEventData): void
    {
        $sToday = IsmsUtils::Today();
        $sNow = IsmsUtils::Now();

        if ($oEventData->Get('is_new') === true && $this->Get('creation_date') === '') {
            $this->Set('creation_date', $sToday);
        }

        $this->Set('last_update', $sNow);

        $this->RecomputeRiskScores();
    }

    /** Compute values hook (safe to call same recompute). */
    public function EvtComputeISMSRiskValues(EventData $oEventData): void
    {
        $this->RecomputeRiskScores();
    }

    /**
     * EVENT_DB_CHECK_TO_WRITE: add warnings (non-blocking) about treatment, dates, and plausibility.
     * Use AddCheckIssue() instead of AddCheckWarning() if you want to block saving.
     */
    public function EvtCheckISMSRiskToWrite(EventData $oEventData): void
    {
        $bIsNew = (bool) $oEventData->Get('is_new');

        // (1) Mitigate: require either linked controls or target values
        if ($this->Get('treatment_decision') === 'mitigate') {
            $bHasControls = false;
            if (!$bIsNew) {
                try {
                    $oSearch = DBObjectSearch::FromOQL("SELECT lnkISMSRiskToISMSControl WHERE risk_id = :rid");
                    $oSet    = new DBObjectSet($oSearch, array(), array('rid' => (int) $this->GetKey()));
                    $bHasControls = ($oSet->Count() > 0);
                } catch (\Exception $e) {
                    // do not block on lookup failure
                }
            }
            $tgtL = $this->Get('tgt_likelihood');
            $tgtI = $this->Get('tgt_impact');
            $bHasTarget = ($tgtL !== '' && $tgtL !== null && $tgtI !== '' && $tgtI !== null);
            if (!$bHasControls && !$bHasTarget) {
                $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:MitigateNoPlanOrTarget'));
            }
        }

        // (2) Accept: require formal acceptance fields
        if ($this->Get('treatment_decision') === 'accept') {
            if ($this->Get('acceptance_status') !== 'accepted') {
                $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:AcceptNotAccepted'));
            }
            $iAccBy   = (int) $this->Get('accepted_by_id');
            $sAccDate = (string) $this->Get('acceptance_date');
            if ($iAccBy <= 0 || $sAccDate === '') {
                $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:AcceptMissingWhoWhen'));
            }
        }

        // (3) Treatment due date in the past
        $sDue = (string) $this->Get('treatment_due');
        if ($sDue !== '') {
            $ts = @strtotime($sDue);
            if ($ts !== false && $ts < strtotime('today')) {
                $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:DueInPast'));
            }
        }

        // (4) Plausibility: residual score should not exceed inherent
        $iPreScore = (int) $this->Get('pre_score');
        $iResScore = (int) $this->Get('res_score');
        if ($iResScore > 0 && $iPreScore > 0 && $iResScore > $iPreScore) {
            $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:ResidualGtInherent'));
        }

        // (5) Plausibility: target vs inherent/residual consistency
        $preL = (int) $this->Get('pre_likelihood');
        $preI = (int) $this->Get('pre_impact');
        $resL = (int) $this->Get('res_likelihood');
        $resI = (int) $this->Get('res_impact');
        $tgtL = $this->Get('tgt_likelihood'); // may be ''/null
        $tgtI = $this->Get('tgt_impact');     // may be ''/null

        $hasTgtL = ($tgtL !== '' && $tgtL !== null);
        $hasTgtI = ($tgtI !== '' && $tgtI !== null);

        // Partial target input (only one dimension) → ask for both
        if ($hasTgtL xor $hasTgtI) {
            $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:TargetPartial'));
        }

        // Compute local scores for comparisons, without touching attributes
        $preScoreLocal = ($iPreScore > 0) ? $iPreScore : (($preL > 0 && $preI > 0) ? $preL * $preI : null);
        $resScoreLocal = ($iResScore > 0) ? $iResScore : (($resL > 0 && $resI > 0) ? $resL * $resI : null);
        $tgtScoreLocal = ($hasTgtL && $hasTgtI) ? ((int) $tgtL * (int) $tgtI) : null;

        // Target should not be worse than inherent
        if ($tgtScoreLocal !== null && $preScoreLocal !== null && $tgtScoreLocal > $preScoreLocal) {
            $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:TargetGtInherent'));
        }

        // Target should not be worse than current residual
        if ($tgtScoreLocal !== null && $resScoreLocal !== null && $tgtScoreLocal > $resScoreLocal) {
            $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:TargetGtResidual'));
        }

        // 'accept' normally implies keeping current residual; a lower target contradicts acceptance
        if (
            $this->Get('treatment_decision') === 'accept'
            && $tgtScoreLocal !== null && $resScoreLocal !== null
            && $tgtScoreLocal < $resScoreLocal
        ) {
            $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:AcceptButTargetBelowResidual'));
        }
    }

    /** Reference format for 'ref' (e.g., RSK-0001). */
    public static function GetRiskRefFormat(): string
    {
        return 'RSK-%04d';
    }

    /** Build a risk reference from a sequential id. */
    protected function MakeRiskRef(int $iNextId): string
    {
        return sprintf(static::GetRiskRefFormat(), $iNextId);
    }

    /**
     * Map a numeric score (likelihood * impact) to level enum.
     *
     * @param int $iScore
     * @return string|null 'low'|'medium'|'high'|'extreme' or null if invalid
     */
    public static function MapScoreToLevel(int $iScore): ?string
    {
        if ($iScore <= 0) return null;
        if ($iScore >= 16) return 'extreme';
        if ($iScore >= 10) return 'high';
        if ($iScore >= 5)  return 'medium';
        return 'low';
    }

    /** Read default aggregation mode from module settings; returns 'max' or 'sum_capped'. */
    public static function GetAggregationModeDefault(): string
    {
        // Prefer MetaModel::GetModuleSetting for module-scoped settings
        $s = (string) MetaModel::GetModuleSetting('br-isms', 'risk_aggregation_mode', 'max');
        return ($s === 'sum_capped') ? 'sum_capped' : 'max';
    }

    /** Resolve the effective aggregation mode for this object (handles 'inherit'). */
    public function GetAggregationMode(): string
    {
        $s = (string) $this->Get('aggregation_mode');
        if ($s !== '' && $s !== 'inherit') {
            return ($s === 'sum_capped') ? 'sum_capped' : 'max';
        }
        return self::GetAggregationModeDefault();
    }

    /**
     * Aggregate the risk-specific effectiveness of linked controls.
     *
     * Semantics:
     * - Consider links with link_status = 'effective' only.
     * - Each link contributes integer "steps" of reduction (0..N) per dimension.
     * - Modes:
     *     - 'max'        : maximum step reduction per dimension
     *     - 'sum_capped' : sum reductions, but cap so residual can't drop below 1
     *                       (i.e., effect ≤ pre_value - 1)
     *
     * @return array{like: ?int, impact: ?int} Nulls mean "no computable effect".
     */
    public function AggregateControlEffects(): array
    {
        $iRiskId = (int) $this->GetKey();
        if ($iRiskId <= 0) {
            return ['like' => null, 'impact' => null];
        }

        // Resolve aggregation mode. Expected: 'max' | 'sum_capped' | 'inherit'(resolved upstream)
        $sMode = $this->GetAggregationMode();

        // Current inherent values (used for capping in 'sum_capped')
        $preL = (int) $this->Get('pre_likelihood');
        $preI = (int) $this->Get('pre_impact');

        // Accumulators (null = no effect yet)
        $accL = null;
        $accI = null;

        // Fetch effective links; robust against transient DB issues
        try {
            $oSearch = DBObjectSearch::FromOQL(
                "SELECT lnkISMSRiskToISMSControl WHERE risk_id = :rid AND link_status = 'effective'"
            );
            $oSet = new DBObjectSet($oSearch, [], ['rid' => $iRiskId]);

            while ($oLink = $oSet->Fetch()) {
                // Raw values from link (can be '', null, or int-ish)
                $vL = $oLink->Get('effect_on_likelihood');
                $vI = $oLink->Get('effect_on_impact');
                $nL = ($vL === '' || $vL === null) ? null : (int) $vL;
                $nI = ($vI === '' || $vI === null) ? null : (int) $vI;

                // Skip empty contributions
                if ($nL !== null) {
                    $accL = ($sMode === 'sum_capped')
                        ? (is_null($accL) ? $nL : $accL + $nL)
                        : (is_null($accL) ? $nL : max($accL, $nL));
                }
                if ($nI !== null) {
                    $accI = ($sMode === 'sum_capped')
                        ? (is_null($accI) ? $nI : $accI + $nI)
                        : (is_null($accI) ? $nI : max($accI, $nI));
                }
            }
        } catch (\Exception $e) {
            // On any DB error, behave as "no computed effect"
            return ['like' => null, 'impact' => null];
        }

        // Apply capping for sum-mode so residual can't go below 1:
        //   max effect = pre_value - 1  (because residual = max(1, pre - effect))
        if ($sMode === 'sum_capped') {
            if ($accL !== null && $preL > 0) {
                $accL = min($accL, max(0, $preL - 1));
            }
            if ($accI !== null && $preI > 0) {
                $accI = min($accI, max(0, $preI - 1));
            }
        }

        // If inherent is not set, do not report an effect (prevents premature locking/UI rules)
        if ($preL <= 0) $accL = null;
        if ($preI <= 0) $accI = null;

        return ['like' => $accL, 'impact' => $accI];
    }

    /**
     * Recompute inherent (pre_*), residual (res_*), and target (tgt_*) scores & levels.
     *
     * Rules:
     *  - Inherent: set pre_score/level if pre_likelihood & pre_impact > 0; else null.
     *  - Residual:
     *      • If AggregateControlEffects() yields any effect, compute
     *        res_likelihood/res_impact = max(1, pre - effect) per dimension.
     *      • If no effect, keep res_likelihood/res_impact as-is (manual allowed).
     *      • Always recompute res_score/level from current res_* (or null if missing).
     *  - Target: set tgt_score/level if tgt_likelihood & tgt_impact > 0; else null.
     *
     * @return bool Always true (for event hooks that expect a return value).
     */
    public function RecomputeRiskScores(): bool
    {
        // Inherent
        $preL = (int) $this->Get('pre_likelihood');
        $preI = (int) $this->Get('pre_impact');
        if ($preL > 0 && $preI > 0) {
            $preScore = $preL * $preI;
            $this->Set('pre_score', $preScore);
            $this->Set('pre_level', static::MapScoreToLevel($preScore));
        } else {
            $this->Set('pre_score', null);
            $this->Set('pre_level', null);
        }

        // Residual (apply control effects if any)
        $aAgg = $this->AggregateControlEffects();
        $bHasAnyEffect = (!is_null($aAgg['like']) || !is_null($aAgg['impact']));
        if ($bHasAnyEffect) {
            // Treat null effect as 0 in each dimension when computing (be explicit)
            $effL = is_null($aAgg['like'])   ? 0 : (int) $aAgg['like'];
            $effI = is_null($aAgg['impact']) ? 0 : (int) $aAgg['impact'];

            // Only compute if corresponding inherent dimension is present (>0)
            $this->Set('res_likelihood', ($preL > 0) ? max(1, $preL - $effL) : null);
            $this->Set('res_impact', ($preI > 0) ? max(1, $preI - $effI) : null);
        }

        // Compute residual score/level from current res_* (regardless of whether they were just changed)
        $resL = (int) $this->Get('res_likelihood');
        $resI = (int) $this->Get('res_impact');
        if ($resL > 0 && $resI > 0) {
            $resScore = $resL * $resI;
            $this->Set('res_score', $resScore);
            $this->Set('res_level', static::MapScoreToLevel($resScore));
        } else {
            $this->Set('res_score', null);
            $this->Set('res_level', null);
        }

        // Target
        $tgtL = (int) $this->Get('tgt_likelihood');
        $tgtI = (int) $this->Get('tgt_impact');
        if ($tgtL > 0 && $tgtI > 0) {
            $tgtScore = $tgtL * $tgtI;
            $this->Set('tgt_score', $tgtScore);
            $this->Set('tgt_level', static::MapScoreToLevel($tgtScore));
        } else {
            $this->Set('tgt_score', null);
            $this->Set('tgt_level', null);
        }

        return true;
    }
}
