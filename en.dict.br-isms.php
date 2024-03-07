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
Dict::Add('EN US', 'English', 'English', array(
    'Menu:ISMSManagement' => 'ISMS Management',
    'Menu:ISMSSpace' => 'ISMS Space',
    'Menu:ISMSSpace:Assets' => 'Assets',
));

//
// Class: ISMSAsset
//
Dict::Add('EN US', 'English', 'English', array(
    'Class:ISMSAsset' => 'Asset',
    'Class:ISMSAsset/Attribute:name' => 'Name',
    'Class:ISMSAsset/Attribute:organization_name' => 'Organization',
    'Class:ISMSAsset/Attribute:status' => 'Status',
    'Class:ISMSAsset/Attribute:status/Value:draft' => 'Draft',
    'Class:ISMSAsset/Attribute:status/Value:published' => 'Published',
    'Class:ISMSAsset/Attribute:status/Value:obsolete' => 'Obsolete',
    'Class:ISMSAsset/Attribute:category' => 'Category',
    'Class:ISMSAsset/Attribute:category/Value:primary' => 'Primary',
    'Class:ISMSAsset/Attribute:category/Value:secondary' => 'Secondary',
    'Class:ISMSAsset/Attribute:assettype' => 'Type',
    'Class:ISMSAsset/Attribute:assettype/Value:information' => 'Information/Data',
    'Class:ISMSAsset/Attribute:assettype/Value:person' => 'Person/Knowledge',
    'Class:ISMSAsset/Attribute:assettype/Value:facility' => 'Facility',
    'Class:ISMSAsset/Attribute:assettype/Value:hardware' => 'Hardware',
    'Class:ISMSAsset/Attribute:assettype/Value:software' => 'Software',
    'Class:ISMSAsset/Attribute:assettype/Value:service' => 'Service',
    'Class:ISMSAsset/Attribute:assettype/Value:network' => 'Network',
    'Class:ISMSAsset/Attribute:assettype/Value:finance' => 'Finance',
    'Class:ISMSAsset/Attribute:assettype/Value:intangible' => 'Intangible',
    'Class:ISMSAsset/Attribute:description' => 'Description',
));
