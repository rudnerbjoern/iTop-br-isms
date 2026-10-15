<?php

/**
 * German translations for the ISMS reference catalog.
 *
 * @copyright   Copyright (C) 2024-2026 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2026-10-15
 */

Dict::Add('DE DE', 'German', 'Deutsch', array(
    'Class:ISMSStandard' => 'ISMS-Standard',
    'Class:ISMSStandard+' => 'Sicherheits-/Compliance-Standard, z. B. ISO/IEC 27001.',
    'Class:ISMSStandard/Name' => '%1$s:%2$s',
    'Class:ISMSStandard/Attribute:name' => 'Name',
    'Class:ISMSStandard/Attribute:version' => 'Version',
    'Class:ISMSStandard/Attribute:publisher' => 'Herausgeber',
    'Class:ISMSStandard/Attribute:url' => 'Referenz (URL)',
    'Class:ISMSStandard/Attribute:controls_list' => 'Kontrollen',
    'Class:ISMSStandard/Attribute:controls_list+' => 'Standard-Kontrollen (z. B. Anhang A).',
    'Class:ISMSStandard/Attribute:documents_list' => 'Dokumente',
    'Class:ISMSStandard/UniquenessRule:no_duplicate' => 'Dieser Standard/Version existiert bereits.',
    'Class:ISMSStandardControl' => 'Standard-Kontrolle',
    'Class:ISMSStandardControl+' => 'Kontrolle gemäß gewähltem Standard.',
    'Class:ISMSStandardControl/Name' => '%1$s:%2$s / %3$s - %4$s',
    'Class:ISMSStandardControl/Attribute:standard_id' => 'Standard',
    'Class:ISMSStandardControl/Attribute:code' => 'Code',
    'Class:ISMSStandardControl/Attribute:title' => 'Titel',
    'Class:ISMSStandardControl/Attribute:domain' => 'Domäne',
    'Class:ISMSStandardControl/Attribute:description' => 'Beschreibung',
    'Class:ISMSStandardControl/UniquenessRule:no_duplicate' => 'Diese Kontrolle existiert bereits für den Standard "$this->standard_id_friendlyname$".',
    'Menu:ISMSCatalog' => 'ISMS-Katalog',
    'Menu:SearchISMSStandards' => 'Standards',
    'Menu:SearchISMSStandardControls' => 'Standard-Anforderungen',
));
