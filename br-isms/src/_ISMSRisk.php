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

class _ISMSRisk extends cmdbAbstractObject
{

    public function DBInsertNoReload()
    {
        $iNextId = ItopCounter::Inc('ISMSRisk');
        $sRef = $this->MakeRiskRef($iNextId);
        $this->SetIfNull('ref', $sRef);
        $iKey = parent::DBInsertNoReload();
        return $iKey;
    }

    public function PrefillCreationForm(&$aContextParam): void
    {
        $sToday = date(AttributeDate::GetInternalFormat());
        $sNow   = date(AttributeDateTime::GetInternalFormat());

        if (empty($this->Get('creation_date')))
            $this->Set('creation_date', $sToday);

        if (empty($this->Get('last_update')))
            $this->Set('last_update', $sNow);

        if (empty($this->Get('last_review')))
            $this->Set('last_review', $sToday);

        if (empty($this->Get('review_interval_months')))
            $this->Set('review_interval_months', _ISMSAsset::GetConfiguredReviewIntervalMonths());

        if (empty($this->Get('next_review'))) {
            $iMonths = (int) $this->Get('review_interval_months');
            $ts = strtotime('+' . $iMonths . ' months', strtotime($sToday));
            $this->Set('next_review', date('Y-m-d', $ts));
        }
    }

    public function EvtSetInitialISMSRiskAttributeFlags(EventData $oEventData): void
    {
        $this->ForceInitialAttributeFlags('ref', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('pre_score', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('pre_level', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('res_score', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('res_level', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('tgt_score', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('tgt_level', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('creation_date', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('last_update', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('publish_date', OPT_ATT_READONLY);
    }

    public function EvtSetISMSRiskAttributeFlags(EventData $oEventData): void
    {
        $this->ForceAttributeFlags('ref', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('pre_score', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('pre_level', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('res_score', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('res_level', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('tgt_score', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('tgt_level', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('creation_date', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('last_update', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('publish_date', OPT_ATT_READONLY);

        // Optional: res_likelihood/res_impact sperren, sobald Kontrollen wirken
        try {
            $aAgg = $this->AggregateControlEffects();
            $bHasAnyEffect = (!is_null($aAgg['like']) || !is_null($aAgg['impact']));
            if ($bHasAnyEffect) {
                $this->ForceAttributeFlags('res_likelihood', OPT_ATT_READONLY);
                $this->ForceAttributeFlags('res_impact',     OPT_ATT_READONLY);
            }
        } catch (\Exception $e) { /* ignore */
        }
    }

    public function EvtISMSRiskLinksChanged(EventData $oEventData): void
    {
        $this->RecomputeRiskScores();
    }

    public function EvtBeforeISMSRiskWrite(EventData $oEventData): void
    {
        $sToday = date(AttributeDate::GetInternalFormat());
        $sNow   = date(AttributeDateTime::GetInternalFormat());

        // set creation date on initial write
        if ($oEventData->Get('is_new') === true && !$this->Get('creation_date'))
            $this->Set('creation_date', $sToday);

        // set last update date on every write
        $this->Set('last_update', $sNow);

        $this->RecomputeRiskScores();
    }

    public function EvtComputeISMSRiskValues(EventData $oEventData): void
    {
        $this->RecomputeRiskScores();
    }

    /**
     * EVENT_DB_CHECK_TO_WRITE
     *
     * Purpose:
     *  - Add non-blocking warnings (via AddCheckWarning) before write, based on
     *    treatment decision, dates, and consistency between inherent/residual/target.
     *
     * Notes:
     *  - We do NOT modify attributes here (no side effects).
     *  - For new objects (is_new), we skip link-based checks (no links exist yet).
     *  - If you want to block saving instead of warning, use AddCheckIssue().
     */
    public function EvtCheckISMSRiskToWrite(EventData $oEventData): void
    {
        $bIsNew = (bool) $oEventData->Get('is_new');

        // --- (1) Mitigate: require either linked effective controls OR target values ---
        if ($this->Get('treatment_decision') === 'mitigate') {
            $bHasControls = false;

            // For a brand new object, there cannot be links yet → skip link check
            if (!$bIsNew) {
                try {
                    $oSearch = DBObjectSearch::FromOQL("SELECT lnkISMSRiskToISMSControl WHERE risk_id = :rid");
                    $oSet    = new DBObjectSet($oSearch, array(), array('rid' => (int) $this->GetKey()));
                    // COUNT(*) is efficient enough and supported in all iTop versions
                    $bHasControls = ($oSet->Count() > 0);
                } catch (\Exception $e) {
                    // Fail silently: if link lookup fails, don't block the save here
                }
            }

            // Target residual present only if BOTH dimensions are filled
            $tgtL = $this->Get('tgt_likelihood');
            $tgtI = $this->Get('tgt_impact');
            $bHasTarget = ($tgtL !== '' && $tgtL !== null && $tgtI !== '' && $tgtI !== null);

            if (!$bHasControls && !$bHasTarget) {
                $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:MitigateNoPlanOrTarget'));
            }
        }

        // --- (2) Accept: require formal acceptance fields ---
        if ($this->Get('treatment_decision') === 'accept') {
            if ($this->Get('acceptance_status') !== 'accepted') {
                $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:AcceptNotAccepted'));
            }
            $iAccBy   = (int) $this->Get('accepted_by_id');
            $sAccDate = $this->Get('acceptance_date');
            if ($iAccBy <= 0 || empty($sAccDate)) {
                $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:AcceptMissingWhoWhen'));
            }
        }

        // --- (3) Treatment due date in the past ---
        $sDue = $this->Get('treatment_due');
        if (!empty($sDue)) {
            $ts = @strtotime($sDue); // relies on server timezone
            if ($ts !== false && $ts < strtotime('today')) {
                $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:DueInPast'));
            }
        }

        // --- (4) Plausibility: residual score should not exceed inherent score ---
        $iPreScore = (int) $this->Get('pre_score');
        $iResScore = (int) $this->Get('res_score');
        if ($iResScore > 0 && $iPreScore > 0 && $iResScore > $iPreScore) {
            $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:ResidualGtInherent'));
        }

        // --- (5) Plausibility: target vs. inherent/residual consistency ---
        // Use current values; if score is missing, compute a local fallback from dimensions.
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
        if (!is_null($tgtScoreLocal) && !is_null($preScoreLocal) && $tgtScoreLocal > $preScoreLocal) {
            $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:TargetGtInherent'));
        }

        // Target should not be worse than current residual
        if (!is_null($tgtScoreLocal) && !is_null($resScoreLocal) && $tgtScoreLocal > $resScoreLocal) {
            $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:TargetGtResidual'));
        }

        // 'accept' normally implies keeping current residual; a lower target contradicts acceptance
        if (
            $this->Get('treatment_decision') === 'accept'
            && !is_null($tgtScoreLocal) && !is_null($resScoreLocal)
            && $tgtScoreLocal < $resScoreLocal
        ) {
            $this->AddCheckWarning(Dict::S('Class:ISMSRisk/Check:AcceptButTargetBelowResidual'));
        }
    }

    public static function GetRiskRefFormat(): string
    {
        return 'RSK-%04d';
    }

    protected function MakeRiskRef($iNextId): string
    {
        return sprintf(static::GetRiskRefFormat(), $iNextId);
    }

    public static function MapScoreToLevel($iScore): string
    {
        if (!is_int($iScore) || $iScore <= 0) return null;
        if ($iScore >= 16) return 'extreme';
        if ($iScore >= 10) return 'high';
        if ($iScore >= 5)  return 'medium';
        return 'low';
    }

    public static function GetAggregationModeDefault(): string
    {
        // Can be 'max' or 'sum_capped'
        return MetaModel::GetConfig()->GetModuleSetting('br-isms', 'risk_aggregation_mode', 'max');
    }

    public function GetAggregationMode(): string
    {
        $s = (string) $this->Get('aggregation_mode');
        if ($s && $s !== 'inherit') return $s;
        try {
            $def = self::GetAggregationModeDefault();
            if ($def === 'sum_capped') return 'sum_capped';
        } catch (Exception $e) {
        }
        return 'max';
    }

    /**
     * Aggregate the risk-specific effectiveness of linked controls.
     *
     * Semantics:
     * - We only consider links with link_status = 'effective'.
     * - Each link contributes integer "steps" of reduction for likelihood/impact (0..N),
     *   where 0 means "no effect". These are *relative* decrements, not absolute target values.
     * - Aggregation mode:
     *     - 'max'        : take the maximum effect across all links (per dimension)
     *     - 'sum_capped' : sum effects and cap so that residual cannot go below 1 step
     *                      (i.e., effect ≤ pre_value - 1)
     *   Any other/unknown mode falls back to 'max'.
     *
     * Return:
     *   array{like: ?int, impact: ?int}
     *   null means "no computable effect" (e.g., no effective links *or* pre_* not set yet).
     */
    public function AggregateControlEffects(): array
    {
        $iRiskId = (int) $this->GetKey();
        if ($iRiskId <= 0) {
            return array('like' => null, 'impact' => null);
        }

        // Resolve aggregation mode. Expected: 'max' | 'sum_capped' | 'inherit'(resolved upstream)
        $sMode = (string) $this->GetAggregationMode();

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
            $oSet = new DBObjectSet($oSearch, array(), array('rid' => $iRiskId));

            while ($oLink = $oSet->Fetch()) {
                // Raw values from link (can be '', null, or int-ish)
                $vL = $oLink->Get('effect_on_likelihood');
                $vI = $oLink->Get('effect_on_impact');
                $nL = ($vL === '' || $vL === null) ? null : (int) $vL;
                $nI = ($vI === '' || $vI === null) ? null : (int) $vI;

                // Skip empty contributions
                if ($nL !== null) {
                    if ($sMode === 'sum_capped') {
                        $accL = is_null($accL) ? $nL : ($accL + $nL);
                    } else {
                        // default/fallback = 'max'
                        $accL = is_null($accL) ? $nL : max($accL, $nL);
                    }
                }
                if ($nI !== null) {
                    if ($sMode === 'sum_capped') {
                        $accI = is_null($accI) ? $nI : ($accI + $nI);
                    } else {
                        $accI = is_null($accI) ? $nI : max($accI, $nI);
                    }
                }
            }
        } catch (\Exception $e) {
            // On any DB error, behave as "no computed effect"
            return array('like' => null, 'impact' => null);
        }

        // Apply capping for sum-mode so residual can't go below 1:
        //   max effect = pre_value - 1  (because residual = max(1, pre - effect))
        if ($sMode === 'sum_capped') {
            if (!is_null($accL) && $preL > 0) {
                $accL = min($accL, max(0, $preL - 1));
            }
            if (!is_null($accI) && $preI > 0) {
                $accI = min($accI, max(0, $preI - 1));
            }
        }

        // If inherent (pre_*) is not set yet (0), report no computed effect (null)
        // so that UI rules (e.g., dynamic read-only on res_*) do not kick in prematurely.
        if ($preL <= 0) {
            $accL = null;
        }
        if ($preI <= 0) {
            $accI = null;
        }

        return array('like' => $accL, 'impact' => $accI);
    }

    /**
     * Recompute inherent (pre_*), residual (res_*), and target (tgt_*) risk scores & levels.
     *
     * Rules / semantics:
     * - pre_* (inherent): computed iff both pre_likelihood and pre_impact > 0, else set to null.
     * - res_* (residual):
     *     * If AggregateControlEffects() returns any effect (like/impact not null), we compute
     *       res_likelihood/res_impact as max(1, pre - effect) per dimension (only if pre_* > 0, else null).
     *     * If there is NO computed effect (both null), we DO NOT touch res_likelihood/res_impact
     *       so they can be maintained manually.
     *     * res_score/level are always recomputed from the current res_* values (if both > 0), else null.
     * - tgt_* (target): computed iff both tgt_likelihood and tgt_impact > 0, else set to null.
     *
     * Notes:
     * - AggregateControlEffects() already applies the chosen aggregation mode (e.g. max / sum_capped)
     *   and returns per-dimension integer step reductions or null (no computable effect).
     * - static::MapScoreToLevel($score) must exist on this class and return an enum value (e.g. low/medium/high/extreme).
     * - This method should be invoked from EVENT_DB_BEFORE_WRITE / EVENT_DB_COMPUTE_VALUES / EVENT_DB_LINKS_CHANGED.
     */
    public function RecomputeRiskScores(): bool
    {
        // ---------- Inherent (pre_*) ----------
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

        // ---------- Residual (res_*) ----------
        // Aggregate effective control impacts (per-dimension step reductions or null)
        $aAgg = $this->AggregateControlEffects();
        $bHasAnyEffect = (!is_null($aAgg['like']) || !is_null($aAgg['impact']));

        if ($bHasAnyEffect) {
            // Treat null effect as 0 in each dimension when computing (be explicit)
            $effL = is_null($aAgg['like']) ? 0 : (int) $aAgg['like']; // 0..N
            $effI = is_null($aAgg['impact']) ? 0 : (int) $aAgg['impact']; // 0..N

            // Only compute if corresponding inherent dimension is present (>0)
            if ($preL > 0) {
                $this->Set('res_likelihood', max(1, $preL - $effL));
            } else {
                $this->Set('res_likelihood', null);
            }

            if ($preI > 0) {
                $this->Set('res_impact', max(1, $preI - $effI));
            } else {
                $this->Set('res_impact', null);
            }
        }
        // If there is NO effect, we leave res_likelihood/res_impact untouched (manual values allowed)

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

        // ---------- Target (tgt_*) ----------
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
