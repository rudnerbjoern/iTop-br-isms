<?php

/** Verify the independent catalog relation and reject reversed source definitions. */
declare(strict_types=1);

function checkCatalogRelation(SimpleXMLElement $model): void
{
    $relations = $model->xpath('/itop_design/classes/class/relations/relation[@id="isms_catalog"]');
    $standard = $model->xpath('/itop_design/classes/class[@id="ISMSStandard"]');
    $neighbours = $standard[0]->xpath('relations/relation[@id="isms_catalog"]/neighbours/neighbour');
    if (count($relations) !== 1 || count($neighbours) !== 1
        || (string) $neighbours[0]->attribute !== 'controls_list'
        || (string) $neighbours[0]->direction !== 'both'
        || isset($neighbours[0]->query_down) || isset($neighbours[0]->query_up)) {
        throw new RuntimeException('Define the bidirectional catalog relation on ISMSStandard using native controls_list traversal.');
    }
    $links = $standard[0]->xpath('fields/field[@id="controls_list"]');
    $keys = $model->xpath('/itop_design/classes/class[@id="ISMSStandardControl"]/fields/field[@id="standard_id"]');
    if ((string) $links[0]->linked_class !== 'ISMSStandardControl'
        || (string) $links[0]->ext_key_to_me !== 'standard_id'
        || (string) $keys[0]->target_class !== 'ISMSStandard') {
        throw new RuntimeException('Standards must be upstream of their own requirements.');
    }
}

$model = simplexml_load_file(dirname(__DIR__).'/br-isms-catalog/datamodel.br-isms-catalog.xml', 'SimpleXMLElement', LIBXML_NONET);
if ($model === false) { throw new RuntimeException('Cannot load catalog model.'); }
checkCatalogRelation($model);
foreach (['reverse_owner', 'one_way', 'wrong_attribute', 'wrong_endpoint'] as $case) {
    $broken = new SimpleXMLElement($model->asXML());
    $standard = $broken->xpath('/itop_design/classes/class[@id="ISMSStandard"]')[0];
    $neighbour = $standard->relations->relation->neighbours->neighbour;
    if ($case === 'reverse_owner') {
        unset($standard->relations);
    } elseif ($case === 'one_way') {
        $neighbour->direction = 'down';
    } elseif ($case === 'wrong_attribute') {
        $neighbour->attribute = 'documents_list';
    } else {
        $standard->xpath('fields/field[@id="controls_list"]')[0]->ext_key_to_me = 'id';
    }
    try { checkCatalogRelation($broken); } catch (RuntimeException) { continue; }
    throw new RuntimeException('Catalog relation regression accepted: '.$case);
}
echo "Catalog relation direction and four regression cases passed.\n";
