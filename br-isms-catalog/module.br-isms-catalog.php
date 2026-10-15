<?php

/**
 * Register the standalone ISMS reference catalog module.
 *
 * @copyright   Copyright (C) 2024-2026 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2026-10-15
 */

/** @disregard P1009 Undefined type SetupWebPage */
SetupWebPage::AddModule(
    __FILE__,
    'br-isms-catalog/3.2.6',
    array(
        'label' => 'ISMS Reference Catalog',
        'category' => 'business',
        'dependencies' => array('itop-structure/3.2.0'),
        'mandatory' => false,
        'visible' => true,
        'auto_select' => 'SetupInfo::ModuleIsSelected("br-isms")',
        'datamodel' => array(),
        'webservice' => array(),
        'data.struct' => array(),
        'data.sample' => array('data/data.sample.standard.xml'),
        'doc.manual_setup' => '',
        'doc.more_information' => '',
        'settings' => array(),
    )
);
