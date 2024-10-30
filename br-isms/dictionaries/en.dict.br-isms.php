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
    'Class:ISMSAsset/Attribute:description' => 'Description',
    'Class:ISMSAssetType' => 'Asset Type',
    'Class:ISMSAssetType/Attribute:name' => 'Name',
    'Class:ISMSAssetType/Attribute:description' => 'Description',
    'Class:ISMSAssetType/Attribute:ismsassets_list' => 'Assets',
));
