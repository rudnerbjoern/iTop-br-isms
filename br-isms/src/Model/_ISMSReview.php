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

/**
 * Base class for all ISMS*Review objects.
 *
 * Responsibilities (generic, cross-type):
 *  - Lifecycle date stamping on state transitions:
 *      - when entering in_progress → set started_on (if empty)
 *      - when entering completed  → set completed_on (if empty)
 *  - Generic write checks:
 *      - planned_on cannot be in the past on creation / when in 'planned'
 *      - outcome must be set when completing
 *
 * Notes:
 *  - Child classes (Asset/Risk/Control reviews) may add extra checks.
 *  - Keep this class side-effect free except for setting own attributes.
 *  - Do NOT DBUpdate() here; the platform persists after checks pass.
 */
class _ISMSReview extends cmdbAbstractObject
{


    /**
     * Generic validations before writing:
     * - planned_on must not be in the past when the record is new OR when target state is 'planned'
     * - outcome must be set when completing
     *
     * Emits CheckIssues to block the write if invalid.
     */
    public function EvtReviewCheckToWrite(EventData $oEventData): void
    {
        $sCurrentStatus = (string) $this->Get('status');
        $sTargetState   = (string) $oEventData->Get('target_state'); // may be ''
        $sStatusAfter   = ($sTargetState !== '') ? $sTargetState : $sCurrentStatus;

        // 1) planned_on must not be in the past at creation or when (remaining/going) planned
        if ($oEventData->Get('is_new') === true || $sStatusAfter === 'planned') {
            $sPlanned = (string) $this->Get('planned_on'); // internal 'Y-m-d'
            if ($sPlanned !== '') {
                $tsPlanned = @strtotime($sPlanned);
                $tsToday   = @strtotime(IsmsUtils::Today());
                if ($tsPlanned !== false && $tsPlanned < $tsToday) {
                    $this->AddCheckIssue(Dict::S('Class:ISMSReview/Check:PlannedInPast'));
                }
            }
        }

        // 2) outcome required when completing
        if ($sTargetState === 'completed' && empty($this->Get('outcome'))) {
            $this->AddCheckIssue(Dict::S('Class:ISMSReview/Check:NoOutcome'));
        }
    }

    /**
     * Children override to return the reviewed target object (Asset/Risk/Control).
     *
     * @return mixed|null A DBObject instance or null if not applicable.
     */
    public function GetTargetObject()
    {
        return null;
    }
}
