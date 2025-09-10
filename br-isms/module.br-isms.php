<?php

/**
 * @copyright   Copyright (C) 2024-2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2025-09-10
 *
 * iTop module definition file
 */

/** @disregard P1009 Undefined type SetupWebPage */
SetupWebPage::AddModule(
    __FILE__, // Path to the current file, all other file names are relative to the directory containing this file
    'br-isms/3.2.1',
    array(
        // Identification
        //
        'label' => 'ISMS in iTop',
        'category' => 'business',

        // Setup
        //
        'dependencies' => array(
            'itop-config-mgmt/3.2.0',
            'br-riskassessment/3.2.0',
        ),
        'mandatory' => false,
        'visible' => true,
        'installer' => 'ISMSInstaller',

        // Components
        //
        'datamodel' => array(
            'src/dashletriskmatrix.class.inc.php',
        ),
        'webservice' => array(),
        'data.struct' => array(
            // add your 'structure' definition XML files here,
        ),
        'data.sample' => array(
            // add your sample data XML files here,
        ),

        // Documentation
        //
        'doc.manual_setup' => '', // hyperlink to manual setup documentation, if any
        'doc.more_information' => '', // hyperlink to more information, if any

        // Default settings
        //
        'settings' => array(
            // Module specific settings go here, if any
        ),
    )
);

if (!class_exists('ISMSInstaller')) {
    /**
     * Class ISMSInstaller
     *
     * @since v0.0.2
     */
    class ISMSInstaller extends ModuleInstallerAPI
    {

        public static function BeforeWritingConfig(Config $oConfiguration)
        {
            // If you want to override/force some configuration values, do it here
            return $oConfiguration;
        }
        public static function AfterDatabaseCreation(Config $oConfiguration, $sPreviousVersion, $sCurrentVersion)
        {
            if (version_compare($sPreviousVersion, '0.0.2', '<')) {

                SetupLog::Info("|- Upgrading br-isms from '$sPreviousVersion' to '$sCurrentVersion'.");

                $aISMSAssetTypeNames = array(
                    'Information/Data',
                    'Facility',
                    'Person/Knowledge',
                    'Hardware',
                    'Software',
                    'IT-Service',
                    'Network',
                    'Finance',
                    'Intangible'
                );
                foreach ($aISMSAssetTypeNames as $sISMSAssetTypeName) {
                    $oSearch = DBSearch::FromOQL('SELECT ISMSAssetType WHERE name = :name');
                    $oSet = new DBObjectSet($oSearch, array(), array('name' => $sISMSAssetTypeName));
                    if ($oSet->Count() == 0) {
                        $oPM = MetaModel::NewObject('ISMSAssetType', array('name' => $sISMSAssetTypeName));
                        $oPM->DBInsert();
                        SetupLog::Info("|  |- ISMSAssetType '$sISMSAssetTypeName' created.");
                    }
                }
            }
        }
    }
}
