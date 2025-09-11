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

class _ISMSControl extends cmdbAbstractObject
{

    public function DBInsertNoReload()
    {
        $iNextId = ItopCounter::Inc('ISMSControl');
        $sRef = $this->MakeControlRef($iNextId);
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

    public function EvtSetInitialISMSControlAttributeFlags(EventData $oEventData): void
    {
        $this->ForceInitialAttributeFlags('ref', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('creation_date', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('last_update', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('approval_date', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('implementation_date', OPT_ATT_READONLY);
    }

    public function EvtSetISMSControlAttributeFlags(EventData $oEventData): void
    {
        $this->ForceAttributeFlags('ref', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('creation_date', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('last_update', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('approval_date', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('implementation_date', OPT_ATT_READONLY);
    }

    public function EvtBeforeISMSControlWrite(EventData $oEventData): void
    {
        $sToday = date(AttributeDate::GetInternalFormat());
        $sNow   = date(AttributeDateTime::GetInternalFormat());

        // set creation date on initial write
        if ($oEventData->Get('is_new') === true && !$this->Get('creation_date'))
            $this->Set('creation_date', $sToday);

        // set last update date on every write
        $this->Set('last_update', $sNow);
    }

    public static function GetControlRefFormat(): string
    {
        return 'CTR-%04d';
    }

    protected function MakeControlRef($iNextId): string
    {
        return sprintf(static::GetControlRefFormat(), $iNextId);
    }
}
