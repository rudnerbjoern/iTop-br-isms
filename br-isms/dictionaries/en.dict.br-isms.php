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
Dict::Add('EN US', 'English', 'English', array(
    'Menu:ISMSManagement' => 'ISMS Management',
    'Menu:ISMSSpace' => 'ISMS Space',
    'Menu:ISMSSpace:Assets' => 'Assets',
    'Menu:ISMSSpace:Options' => 'Options',
    'Menu:ISMSSpace:Risks' => 'Risks',
));

//
// Class: ISMSAssetType
//
/** @disregard P1009 Undefined type Dict */
Dict::Add('EN US', 'English', 'English', array(
    'Class:ISMSAssetType' => 'Asset Type',
    'Class:ISMSAssetType/Attribute:name' => 'Name',
    'Class:ISMSAssetType/Attribute:description' => 'Description',
    'Class:ISMSAssetType/Attribute:ismsassets_list' => 'Assets',
));

//
// Class: ISMSAsset
//
/** @disregard P1009 Undefined type Dict */
Dict::Add('EN US', 'English', 'English', array(
    'ISMSAsset:Contacts' => 'Contacts',
    'Class:ISMSAsset' => 'Asset',
    'Class:ISMSAsset/Name' => '%1$s %2$s',
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
    'Class:ISMSAsset/Attribute:assettype_id' => 'Type',
    'Class:ISMSAsset/Attribute:assettype_name' => 'Type Name',
    'Class:ISMSAsset/Attribute:assetowner_id' => 'Owner',
    'Class:ISMSAsset/Attribute:assetowner_id+' => 'The name of the asset owner.',
    'Class:ISMSAsset/Attribute:assetowner_name' => 'Owner Name',
    'Class:ISMSAsset/Attribute:assetguardian_id' => 'Guardian',
    'Class:ISMSAsset/Attribute:assetguardian_id+' => 'The name of the asset owner.',
    'Class:ISMSAsset/Attribute:assetguardian_name' => 'Guardian Name',
    'Class:ISMSAsset/Attribute:assetusers' => 'Users',
    'Class:ISMSAsset/Attribute:assetusers+' => 'Additional information on asset users.',
    'Class:ISMSAsset/Attribute:creation_date' => 'Creation Date',
    'Class:ISMSAsset/Attribute:publish_date' => 'Publish Date',
    'Class:ISMSAsset/Attribute:last_update' => 'Last Update',
    'Class:ISMSAsset/Attribute:next_revision' => 'Next Revision',
    'Class:ISMSAsset/Attribute:supportingassets_list' => 'Supporting Assets',
    'Class:ISMSAsset/Attribute:supportedassets_list' => 'Supported Assets',
    'Class:ISMSAsset/Stimulus:ev_publish' => 'Publish',
    'Class:ISMSAsset/Stimulus:ev_publish+' => '',
    'Class:ISMSAsset/Stimulus:ev_obsolete' => 'Obsolete',
    'Class:ISMSAsset/Stimulus:ev_obsolete+' => '',
    'Class:ISMSAsset/Stimulus:ev_draft' => 'Draft',
    'Class:ISMSAsset/Stimulus:ev_draft+' => '',
));

//
// Class: lnkSupportingAssetToAsset
//
/** @disregard P1009 Undefined type Dict */
Dict::Add('EN US', 'English', 'English', array(
    'Class:lnkSupportingAssetToAsset' => 'Link Supporting Asset / Asset',
    'Class:lnkSupportingAssetToAsset+' => 'Association indicating that the first asset supports the second asset.',
    'Class:lnkSupportingAssetToAsset/Name' => '%1$s - %2$s',

    'Class:lnkSupportingAssetToAsset/Attribute:supportingasset_id' => 'Supporting asset',
    'Class:lnkSupportingAssetToAsset/Attribute:supportingasset_name' => 'Supporting asset',
    'Class:lnkSupportingAssetToAsset/Attribute:asset_id' => 'Supported asset',
    'Class:lnkSupportingAssetToAsset/Attribute:asset_name' => 'Supported asset',
));

//
// Class: ISMSRisk
//
/** @disregard P1009 Undefined type Dict */
Dict::Add('EN US', 'English', 'English', array(
    'Class:ISMSRisk' => 'ISMS Risk',
    'Class:ISMSRisk+' => 'Information security risk with inherent/residual/target evaluation and treatment data.',

    'ISMSRisk:Base' => 'Basics',
    'ISMSRisk:Context' => 'Context',
    'ISMSRisk:Summary' => 'Summary',
    'ISMSRisk:Preliminary' => 'Inherent (gross)',
    'ISMSRisk:Residual' => 'Residual (net)',
    'ISMSRisk:TargetResidual' => 'Target residual',
    'ISMSRisk:Dates' => 'Dates',
    'ISMSRisk:Treatment' => 'Treatment',
    'ISMSRisk:Acceptance' => 'Acceptance',

    'Class:ISMSRisk/Name' => '%1$s %2$s', // ref + name

    'Class:ISMSRisk/Attribute:ref' => 'Reference',
    'Class:ISMSRisk/Attribute:name' => 'Name',
    'Class:ISMSRisk/Attribute:org_id' => 'Organization',
    'Class:ISMSRisk/Attribute:organization_name' => 'Organization',
    'Class:ISMSRisk/Attribute:status' => 'Status',
    'Class:ISMSRisk/Attribute:status/Value:draft' => 'Draft',
    'Class:ISMSRisk/Attribute:status/Value:published' => 'Published',
    'Class:ISMSRisk/Attribute:status/Value:obsolete' => 'Obsolete',

    'Class:ISMSRisk/Attribute:riskowner_id' => 'Risk owner',
    'Class:ISMSRisk/Attribute:riskowner_name' => 'Risk owner',

    'Class:ISMSRisk/Attribute:creation_date' => 'Creation date',
    'Class:ISMSRisk/Attribute:last_update' => 'Last update',
    'Class:ISMSRisk/Attribute:next_revision' => 'Next review',
    'Class:ISMSRisk/Attribute:publish_date' => 'Publish date',

    'Class:ISMSRisk/Attribute:description' => 'Description',

    'Class:ISMSRisk/Attribute:risk_category' => 'Risk category',
    'Class:ISMSRisk/Attribute:risk_cause' => 'Cause',
    'Class:ISMSRisk/Attribute:risk_event' => 'Event',
    'Class:ISMSRisk/Attribute:risk_consequence' => 'Consequence',

    'Class:ISMSRisk/Attribute:pre_likelihood' => 'Likelihood (inherent)',
    'Class:ISMSRisk/Attribute:pre_impact' => 'Impact (inherent)',
    'Class:ISMSRisk/Attribute:pre_score' => 'Inherent score',
    'Class:ISMSRisk/Attribute:pre_level' => 'Inherent level',
    'Class:ISMSRisk/Attribute:pre_level/Value:low' => 'low',
    'Class:ISMSRisk/Attribute:pre_level/Value:medium' => 'medium',
    'Class:ISMSRisk/Attribute:pre_level/Value:high' => 'high',
    'Class:ISMSRisk/Attribute:pre_level/Value:extreme' => 'extreme',

    'Class:ISMSRisk/Attribute:res_likelihood' => 'Likelihood (residual)',
    'Class:ISMSRisk/Attribute:res_impact' => 'Impact (residual)',
    'Class:ISMSRisk/Attribute:res_score' => 'Residual score',
    'Class:ISMSRisk/Attribute:res_level' => 'Residual level',
    'Class:ISMSRisk/Attribute:res_level/Value:low' => 'low',
    'Class:ISMSRisk/Attribute:res_level/Value:medium' => 'medium',
    'Class:ISMSRisk/Attribute:res_level/Value:high' => 'high',
    'Class:ISMSRisk/Attribute:res_level/Value:extreme' => 'extreme',

    'Class:ISMSRisk/Attribute:tgt_likelihood' => 'Likelihood (target)',
    'Class:ISMSRisk/Attribute:tgt_impact' => 'Impact (target)',
    'Class:ISMSRisk/Attribute:tgt_score' => 'Target score',
    'Class:ISMSRisk/Attribute:tgt_level' => 'Target level',
    'Class:ISMSRisk/Attribute:tgt_level/Value:low' => 'low',
    'Class:ISMSRisk/Attribute:tgt_level/Value:medium' => 'medium',
    'Class:ISMSRisk/Attribute:tgt_level/Value:high' => 'high',
    'Class:ISMSRisk/Attribute:tgt_level/Value:extreme' => 'extreme',

    'Class:ISMSRisk/Attribute:aggregation_mode' => 'Aggregation mode',
    'Class:ISMSRisk/Attribute:aggregation_mode/Value:inherit' => 'inherit (default)',
    'Class:ISMSRisk/Attribute:aggregation_mode/Value:max' => 'maximum effect',
    'Class:ISMSRisk/Attribute:aggregation_mode/Value:sum_capped' => 'sum (capped)',

    'Class:ISMSRisk/Attribute:treatment_decision' => 'Treatment decision',
    'Class:ISMSRisk/Attribute:treatment_decision/Value:mitigate' => 'Mitigate',
    'Class:ISMSRisk/Attribute:treatment_decision/Value:accept' => 'Accept',
    'Class:ISMSRisk/Attribute:treatment_decision/Value:avoid' => 'Avoid',
    'Class:ISMSRisk/Attribute:treatment_decision/Value:transfer' => 'Transfer',

    'Class:ISMSRisk/Attribute:treatment_owner_id' => 'Treatment owner',
    'Class:ISMSRisk/Attribute:treatment_owner_name' => 'Treatment owner',
    'Class:ISMSRisk/Attribute:treatment_due' => 'Treatment due',
    'Class:ISMSRisk/Attribute:treatment_plan' => 'Treatment plan',

    'Class:ISMSRisk/Attribute:acceptance_status' => 'Acceptance status',
    'Class:ISMSRisk/Attribute:acceptance_status/Value:pending' => 'Pending',
    'Class:ISMSRisk/Attribute:acceptance_status/Value:accepted' => 'Accepted',
    'Class:ISMSRisk/Attribute:acceptance_status/Value:rejected' => 'Rejected',
    'Class:ISMSRisk/Attribute:accepted_by_id' => 'Accepted by',
    'Class:ISMSRisk/Attribute:accepted_by_name' => 'Accepted by',
    'Class:ISMSRisk/Attribute:acceptance_date' => 'Acceptance date',
    'Class:ISMSRisk/Attribute:acceptance_rationale' => 'Acceptance rationale',

    'Class:ISMSRisk/Attribute:assets_list' => 'Asset(s)',
    'Class:ISMSRisk/Attribute:controls_list' => 'Control(s)',

    'Class:ISMSRisk/Stimulus:ev_publish' => 'Publish',
    'Class:ISMSRisk/Stimulus:ev_draft' => 'Draft',
    'Class:ISMSRisk/Stimulus:ev_obsolete' => 'Obsolete',
    'Class:ISMSRisk/Stimulus:ev_reopen' => 'Reopen',

    'Class:ISMSRisk/Check:MitigateNoPlanOrTarget' => "Treatment 'mitigate' but neither linked controls nor target residual (tgt_*) set.",
    'Class:ISMSRisk/Check:AcceptNotAccepted' => "Treatment 'accept' but 'acceptance_status' is not 'accepted'.",
    'Class:ISMSRisk/Check:AcceptMissingWhoWhen' => "Treatment 'accept': please set 'accepted by' and 'acceptance date'.",
    'Class:ISMSRisk/Check:DueInPast' => "Treatment due date is in the past.",
    'Class:ISMSRisk/Check:ResidualGtInherent' => "Residual score is greater than inherent score. Please review.",
    'Class:ISMSRisk/Check:TargetPartial' => "Target residual: please set both 'likelihood' and 'impact'.",
    'Class:ISMSRisk/Check:TargetGtInherent' => 'Target score is greater than the inherent score. Please review.',
    'Class:ISMSRisk/Check:TargetGtResidual' => 'Target score is greater than the current residual score. Please review.',
    'Class:ISMSRisk/Check:AcceptButTargetBelowResidual' => "Treatment 'accept' but target residual is lower than current residual. Consider 'mitigate' instead.",


));

//
// Class: ISMSControl
//
/** @disregard P1009 Undefined type Dict */
Dict::Add('EN US', 'English', 'English', array(
    'Class:ISMSControl' => 'ISMS Control',
    'Class:ISMSControl+' => 'Reusable security control (organizational / people / physical / technological).',
    'ISMSControl:Basics' => 'Basics',
    'ISMSControl:Classification' => 'Classification',
    'ISMSControl:OwnershipSchedule' => 'Ownership & schedule',
    'ISMSControl:Description' => 'Description',
    'Class:ISMSControl/Attribute:name' => 'Name',
    'Class:ISMSControl/Attribute:org_id' => 'Organization',
    'Class:ISMSControl/Attribute:organization_name' => 'Organization',
    'Class:ISMSControl/Attribute:status' => 'Status',
    'Class:ISMSControl/Attribute:status/Value:draft' => 'Draft',
    'Class:ISMSControl/Attribute:status/Value:active' => 'Active',
    'Class:ISMSControl/Attribute:status/Value:retired' => 'Retired',
    'Class:ISMSControl/Attribute:owner_id' => 'Owner',
    'Class:ISMSControl/Attribute:owner_name' => 'Owner',
    'Class:ISMSControl/Attribute:control_domain' => 'Control domain',
    'Class:ISMSControl/Attribute:control_domain/Value:organizational' => 'Organizational',
    'Class:ISMSControl/Attribute:control_domain/Value:people' => 'People',
    'Class:ISMSControl/Attribute:control_domain/Value:physical' => 'Physical',
    'Class:ISMSControl/Attribute:control_domain/Value:technological' => 'Technological',
    'Class:ISMSControl/Attribute:control_type' => 'Control type',
    'Class:ISMSControl/Attribute:control_type/Value:preventive' => 'Preventive',
    'Class:ISMSControl/Attribute:control_type/Value:detective' => 'Detective',
    'Class:ISMSControl/Attribute:control_type/Value:corrective' => 'Corrective',
    'Class:ISMSControl/Attribute:description' => 'Description',
    'Class:ISMSControl/Attribute:implemented_on' => 'Implemented on',
    'Class:ISMSControl/Attribute:next_review' => 'Next review',
    'Class:ISMSControl/Attribute:risks_list' => 'Risk(s)',
));

//
// Class: lnkISMSRiskToISMSAsset
//
/** @disregard P1009 Undefined type Dict */
Dict::Add('EN US', 'English', 'English', array(
    'Class:lnkISMSRiskToISMSAsset' => 'Link Risk / Asset',
    'Class:lnkISMSRiskToISMSAsset+' => 'Association between an ISMS risk and an ISMS asset.',
    'Class:lnkISMSRiskToISMSAsset/Name' => '%1$s - %2$s',

    'Class:lnkISMSRiskToISMSAsset/Attribute:risk_id'   => 'Risk',
    'Class:lnkISMSRiskToISMSAsset/Attribute:risk_name' => 'Risk',
    'Class:lnkISMSRiskToISMSAsset/Attribute:asset_id'  => 'Asset',
    'Class:lnkISMSRiskToISMSAsset/Attribute:asset_name' => 'Asset',
));

//
// Class: lnkISMSRiskToISMSControl
//
/** @disregard P1009 Undefined type Dict */
Dict::Add('EN US', 'English', 'English', array(
    'Class:lnkISMSRiskToISMSControl' => 'Link Risk / Control',
    'Class:lnkISMSRiskToISMSControl+' => 'Association between an ISMS risk and an ISMS control.',
    'Class:lnkISMSRiskToISMSControl/Name' => '%1$s - %2$s',

    'Class:lnkISMSRiskToISMSControl/Attribute:risk_id' => 'Risk',
    'Class:lnkISMSRiskToISMSControl/Attribute:risk_name' => 'Risk',
    'Class:lnkISMSRiskToISMSControl/Attribute:control_id' => 'Control',
    'Class:lnkISMSRiskToISMSControl/Attribute:control_name' => 'Control',

    'lnkISMSRiskToISMSControl:Link' => 'Link',
    'lnkISMSRiskToISMSControl:ControlSnapshot' => 'Control snapshot',
    'lnkISMSRiskToISMSControl:RiskSnapshot'   => 'Risk snapshot',
    'lnkISMSRiskToISMSControl:Effect' => 'Effect',
    'lnkISMSRiskToISMSControl:Dates' => 'Dates',
    'lnkISMSRiskToISMSControl:Notes' => 'Notes',

    'Class:lnkISMSRiskToISMSControl/Attribute:effect_on_likelihood' => 'Effect on likelihood',
    'Class:lnkISMSRiskToISMSControl/Attribute:effect_on_likelihood/Value:0' => 'none',
    'Class:lnkISMSRiskToISMSControl/Attribute:effect_on_likelihood/Value:1' => 'low',
    'Class:lnkISMSRiskToISMSControl/Attribute:effect_on_likelihood/Value:2' => 'medium',
    'Class:lnkISMSRiskToISMSControl/Attribute:effect_on_likelihood/Value:3' => 'high',

    'Class:lnkISMSRiskToISMSControl/Attribute:effect_on_impact' => 'Effect on impact',
    'Class:lnkISMSRiskToISMSControl/Attribute:effect_on_impact/Value:0' => 'none',
    'Class:lnkISMSRiskToISMSControl/Attribute:effect_on_impact/Value:1' => 'low',
    'Class:lnkISMSRiskToISMSControl/Attribute:effect_on_impact/Value:2' => 'medium',
    'Class:lnkISMSRiskToISMSControl/Attribute:effect_on_impact/Value:3' => 'high',

    'Class:lnkISMSRiskToISMSControl/Attribute:link_status' => 'Status',
    'Class:lnkISMSRiskToISMSControl/Attribute:link_status/Value:planned' => 'Planned',
    'Class:lnkISMSRiskToISMSControl/Attribute:link_status/Value:in_progress' => 'In progress',
    'Class:lnkISMSRiskToISMSControl/Attribute:link_status/Value:effective' => 'Effective',
    'Class:lnkISMSRiskToISMSControl/Attribute:link_status/Value:ineffective' => 'Ineffective',

    'Class:lnkISMSRiskToISMSControl/Attribute:due_date' => 'Due date',
    'Class:lnkISMSRiskToISMSControl/Attribute:comment' => 'Comment',

    'Class:lnkISMSRiskToISMSControl/Attribute:control_status' => 'Control status',
    'Class:lnkISMSRiskToISMSControl/Attribute:control_org_ro' => 'Control organization',
    'Class:lnkISMSRiskToISMSControl/Attribute:control_owner_ro' => 'Control owner',
    'Class:lnkISMSRiskToISMSControl/Attribute:control_domain_ro' => 'Control domain',
    'Class:lnkISMSRiskToISMSControl/Attribute:control_type_ro' => 'Control type',
    'Class:lnkISMSRiskToISMSControl/Attribute:risk_ref' => 'Risk ref',
    'Class:lnkISMSRiskToISMSControl/Attribute:risk_pre_level_ro' => 'Inherent level',
    'Class:lnkISMSRiskToISMSControl/Attribute:risk_pre_score_ro' => 'Inherent score',
    'Class:lnkISMSRiskToISMSControl/Attribute:risk_res_level_ro' => 'Residual level',
    'Class:lnkISMSRiskToISMSControl/Attribute:risk_res_score_ro' => 'Residual score',
));
