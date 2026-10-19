<?php

/**
 * Manage scope creation defaults, system dates, and attribute flags.
 *
 * @copyright   Copyright (C) 2024-2026 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 */

namespace BR\Extension\Isms\Model;

use BR\Extension\Isms\Util\IsmsUtils;
use Combodo\iTop\Service\Events\EventData;
use cmdbAbstractObject;

class _ISMSScope extends cmdbAbstractObject
{
    /**
     * Prefill creation with sensible defaults:
     *  - creation_date = today
     *  - last_update   = now
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

    /** Initial attribute flags at creation time (read-only computed/system fields). */
    public function OnISMSScopeSetInitialAttributesFlags(EventData $oEventData): void
    {
        $this->ForceInitialAttributeFlags('creation_date', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('last_update',   OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('publish_date',  OPT_ATT_READONLY);
    }

    /** Runtime attribute flags (keep read-only at all times; optionally lock residual inputs when effective controls exist). */
    public function OnISMSScopeSetAttributesFlags(EventData $oEventData): void
    {
        $this->AddAttributeFlags('creation_date', OPT_ATT_READONLY);
        $this->AddAttributeFlags('last_update',   OPT_ATT_READONLY);
        $this->AddAttributeFlags('publish_date',  OPT_ATT_READONLY);
    }

    /**
     * Before write:
     *  - On first insert: ensure creation_date is set.
     *  - Always: update last_update (now).
     */
    public function OnISMSScopeBeforeWrite(EventData $oEventData): void
    {
        $sToday = IsmsUtils::Today();
        $sNow = IsmsUtils::Now();

        if ($oEventData->Get('is_new') === true && empty($this->Get('creation_date'))) {
            $this->Set('creation_date', $sToday);
        }

        $this->Set('last_update', $sNow);
    }
}
