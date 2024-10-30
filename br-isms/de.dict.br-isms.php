<?php

/**
 * @copyright   Copyright (C) 2024 Björn Rudner
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2024-10-30
 *
 * Localized data
 */

//
// Application Menu
//
Dict::Add('DE DE', 'German', 'Deutsch', array(
    'Menu:ISMSManagement' => 'ISMS Management',
    'Menu:ISMSSpace' => 'ISMS Raum',
    'Menu:ISMSSpace:Assets' => 'Assets',
));

//
// Class: ISMSAsset
//
Dict::Add('DE DE', 'German', 'Deutsch', array(
    'ISMSAsset:Contacts' => 'Kontakte',
    'Class:ISMSAsset' => 'Asset',
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
    'Class:ISMSAsset/Attribute:last_update' => 'Letzte Änderung',
    'Class:ISMSAsset/Attribute:next_revision' => 'Nächste Revision',
    'Class:ISMSAsset/Attribute:supportingassets_list' => 'Unterstützende Assets',
    'Class:ISMSAsset/Attribute:supportedassets_list' => 'Unterstützte Assets',
    'Class:ISMSAssetType' => 'Asset Typ',
    'Class:ISMSAssetType/Attribute:name' => 'Name',
    'Class:ISMSAssetType/Attribute:description' => 'Beschreibung',
    'Class:ISMSAssetType/Attribute:ismsassets_list' => 'Assets',
));
