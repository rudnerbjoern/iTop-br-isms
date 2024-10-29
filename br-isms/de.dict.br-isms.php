<?php

/**
 * @copyright   Copyright (C) 2024 Björn Rudner
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2024-02-29
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
    'Class:ISMSAsset/Attribute:assettype' => 'Type',
    'Class:ISMSAsset/Attribute:assettype/Value:information' => 'Information/Daten',
    'Class:ISMSAsset/Attribute:assettype/Value:person' => 'Person/Wissen',
    'Class:ISMSAsset/Attribute:assettype/Value:facility' => 'Einrichtung',
    'Class:ISMSAsset/Attribute:assettype/Value:hardware' => 'Hardware',
    'Class:ISMSAsset/Attribute:assettype/Value:software' => 'Software',
    'Class:ISMSAsset/Attribute:assettype/Value:service' => 'Dienst',
    'Class:ISMSAsset/Attribute:assettype/Value:network' => 'Netzwerk',
    'Class:ISMSAsset/Attribute:assettype/Value:finance' => 'Finanziell',
    'Class:ISMSAsset/Attribute:assettype/Value:intangible' => 'Immateriell',
    'Class:ISMSAsset/Attribute:description' => 'Beschreibung',
));
