<?php

/**
 * @copyright   Copyright (C) 2024 Björn Rudner
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2024-10-30
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
    'Class:ISMSAssetType' => 'Asset Typ',
    'Class:ISMSAssetType/Attribute:name' => 'Name',
    'Class:ISMSAssetType/Attribute:description' => 'Beschreibung',
    'Class:ISMSAssetType/Attribute:ismsassets_list' => 'Assets',
));
