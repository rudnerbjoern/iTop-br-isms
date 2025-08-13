<?php

/**
 * @copyright   Copyright (C) 2024-2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2025-08-12
 *
 * Localized data
 */

//
// Application Menu
//
/** @disregard P1009 Undefined type Dict */
Dict::Add('DE DE', 'German', 'Deutsch', array(
    'Menu:ISMSManagement' => 'ISMS Management',
    'Menu:ISMSSpace' => 'ISMS Raum',
    'Menu:ISMSSpace:Assets' => 'Assets',
    'Menu:ISMSSpace:Options' => 'Optionen',
    'Menu:ISMSSpace:Risks' => 'Risiken',
));

//
// Class: ISMSAsset
//
/** @disregard P1009 Undefined type Dict */
Dict::Add('DE DE', 'German', 'Deutsch', array(
    'ISMSAsset:Contacts' => 'Kontakte',
    'Class:ISMSAsset' => 'Asset',
    'Class:ISMSAsset/Name' => '%1$s %2$s',
    'Class:ISMSAsset/Attribute:name' => 'Name',
    'Class:ISMSAsset/Attribute:organization_name' => 'Organisation',
    'Class:ISMSAsset/Attribute:status' => 'Status',
    'Class:ISMSAsset/Attribute:status/Value:draft' => 'Entwurf',
    'Class:ISMSAsset/Attribute:status/Value:published' => 'Veröffentlicht',
    'Class:ISMSAsset/Attribute:status/Value:obsolete' => 'Obsolet',
    'Class:ISMSAsset/Attribute:category' => 'Kategorie',
    'Class:ISMSAsset/Attribute:category/Value:primary' => 'Primär',
    'Class:ISMSAsset/Attribute:category/Value:secondary' => 'Sekundär',
    'Class:ISMSAsset/Attribute:description' => 'Beschreibung',
    'Class:ISMSAsset/Attribute:assettype_id' => 'Typ',
    'Class:ISMSAsset/Attribute:assettype_name' => 'Typ Name',
    'Class:ISMSAsset/Attribute:assetowner_id' => 'Eigentümer',
    'Class:ISMSAsset/Attribute:assetowner_id+' => 'Der Name des Asset Eigentümers.',
    'Class:ISMSAsset/Attribute:assetowner_name' => 'Eigentümer Name',
    'Class:ISMSAsset/Attribute:assetguardian_id' => 'Betreiber',
    'Class:ISMSAsset/Attribute:assetguardian_id+' => 'Der Name des Betreibers des Assets.',
    'Class:ISMSAsset/Attribute:assetguardian_name' => 'Betreiber Name',
    'Class:ISMSAsset/Attribute:assetusers' => 'Anwender',
    'Class:ISMSAsset/Attribute:assetusers+' => 'Informationen über die Anwender dieses Assets.',
    'Class:ISMSAsset/Attribute:creation_date' => 'Erstelldatum',
    'Class:ISMSAsset/Attribute:publish_date' => 'Veröffentlichungsdatum',
    'Class:ISMSAsset/Attribute:last_update' => 'Letzte Änderung',
    'Class:ISMSAsset/Attribute:next_revision' => 'Nächste Revision',
    'Class:ISMSAsset/Attribute:supportingassets_list' => 'Unterstützende Assets',
    'Class:ISMSAsset/Attribute:supportedassets_list' => 'Unterstützte Assets',
    'Class:ISMSAsset/Stimulus:ev_publish' => 'Veröffentlichen',
    'Class:ISMSAsset/Stimulus:ev_publish+' => '',
    'Class:ISMSAsset/Stimulus:ev_obsolete' => 'Obsolet',
    'Class:ISMSAsset/Stimulus:ev_obsolete+' => '',
    'Class:ISMSAsset/Stimulus:ev_draft' => 'Entwurf',
    'Class:ISMSAsset/Stimulus:ev_draft+' => '',
));

//
// Class: ISMSAssetType
//
/** @disregard P1009 Undefined type Dict */
Dict::Add('DE DE', 'German', 'Deutsch', array(
    'Class:ISMSAssetType' => 'Asset Typ',
    'Class:ISMSAssetType/Attribute:name' => 'Name',
    'Class:ISMSAssetType/Attribute:description' => 'Beschreibung',
    'Class:ISMSAssetType/Attribute:ismsassets_list' => 'Assets',
));
