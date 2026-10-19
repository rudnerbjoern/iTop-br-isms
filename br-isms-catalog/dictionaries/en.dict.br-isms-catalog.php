<?php

/**
 * English translations for the ISMS reference catalog.
 *
 * @copyright   Copyright (C) 2024-2026 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 */

// Class: ISMSStandard
Dict::Add('EN US', 'English', 'English', array(
    'Class:ISMSStandard' => 'ISMS Standard',
    'Class:ISMSStandard+' => 'Security or compliance standard, e.g. ISO/IEC 27001.',
    'Class:ISMSStandard/Name' => '%1$s:%2$s',
    'Class:ISMSStandard/Attribute:name' => 'Name',
    'Class:ISMSStandard/Attribute:version' => 'Version',
    'Class:ISMSStandard/Attribute:publisher' => 'Publisher',
    'Class:ISMSStandard/Attribute:url' => 'Reference (URL)',
    'Class:ISMSStandard/Attribute:controls_list' => 'Control(s)',
    'Class:ISMSStandard/Attribute:controls_list+' => 'Standard controls (e.g. Annex A).',
    'Class:ISMSStandard/Attribute:documents_list' => 'Documents',
    'Class:ISMSStandard/UniquenessRule:no_duplicate' => 'This standard/version already exists',
));

// Class: ISMSStandardControl
Dict::Add('EN US', 'English', 'English', array(
    'Class:ISMSStandardControl' => 'Standard control',
    'Class:ISMSStandardControl+' => 'Control as defined by the selected standard.',
    'Class:ISMSStandardControl/Name' => '%1$s:%2$s / %3$s - %4$s',
    'Class:ISMSStandardControl/Attribute:standard_id' => 'Standard',
    'Class:ISMSStandardControl/Attribute:code' => 'Code',
    'Class:ISMSStandardControl/Attribute:title' => 'Title',
    'Class:ISMSStandardControl/Attribute:domain' => 'Domain',
    'Class:ISMSStandardControl/Attribute:description' => 'Description',
    'Class:ISMSStandardControl/UniquenessRule:no_duplicate' => 'This control already exists for the standard "$this->standard_id_friendlyname$".',
));

// Menus.
Dict::Add('EN US', 'English', 'English', array(
    'Menu:ISMSCatalog' => 'ISMS Catalog',
    'Menu:SearchISMSStandards' => 'Standards',
    'Menu:SearchISMSStandardControls' => 'Standard controls',
));

// Relations.
Dict::Add('EN US', 'English', 'English', array(
    'Relation:isms_catalog/DownStream' => 'Standard controls',
    'Relation:isms_catalog/DownStream+' => 'Requirements contained in this reference standard.',
    'Relation:isms_catalog/UpStream' => 'Reference standard',
    'Relation:isms_catalog/UpStream+' => 'The reference standard containing this requirement.',
));
