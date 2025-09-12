<?php

namespace BR\Extension\Isms\UI\Extension;

use Combodo\iTop\Application\UI\Base\Layout\UIContentBlockUIBlockFactory;
use Combodo\iTop\Application\WebPage\WebPage;
use iApplicationUIExtension;
use ISMSSoA;
use Dict;
use ISMSRisk;
use DBObjectSet;
use DBObjectSearch;
use utils;
use MetaModel;
use AttributeEnum;

class IsmsUIExtension implements iApplicationUIExtension
{

    public function OnDisplayProperties($oObject, WebPage $oPage, $bEditMode = false)
    {
        // NICHTS hier ausgeben, sonst erscheint das Dashboard zusätzlich auf der Hauptseite.
        return;
    }

    /**
     * @inheritDoc
     */
    public function OnDisplayRelations($oObject, WebPage $oPage, $bEditMode = false)
    {
        if ($bEditMode) return;
        if ($oObject instanceof ISMSSoA) {

            // Minimaler Tab-Titel (sprachabhängig)
            $oPage->SetCurrentTab(Dict::S('ISMSSoA:Dashboard', 'Dashboard'));

            // Kleines CSS (inline)
            $oPage->add_style(<<<CSS
.soa-dash {margin-top:12px}
.soa-kpis {display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px}
.soa-chip {border-radius:8px; padding:6px 10px; background:#f6f7f8; font-weight:600}
.soa-grid {width:100%; border-collapse:collapse; margin-top:6px}
.soa-grid th, .soa-grid td {border:1px solid #e0e0e0; padding:6px 8px; text-align:left}
.soa-bar {height:10px; background:#e8f5e9; border-radius:5px; overflow:hidden}
.soa-bar > span {display:block; height:10px; background:#66bb6a}
.soa-subtle {color:#666; font-size:12px}
CSS);

            // Block bauen und einhängen
            $oBlock = $this->BuildSoaDashboardBlock($oObject);
            $oPage->AddUiBlock($oBlock);
        } elseif ($oObject instanceof ISMSRisk) {
            //Is Risk
            // Minimaler Tab-Titel (sprachabhängig)
            $oPage->SetCurrentTab(Dict::S('ISMSRisk:Dashboard', 'Dashboard'));

            // Kleines CSS (inline)
            $oPage->add_style(<<<CSS
.risk-dash { margin-top:12px }
.risk-kpis { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px}
.risk-chip { border-radius:8px; padding:6px 10px; background:#f6f7f8; font-weight:600 }
.risk-legend { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:8px }
.risk-dot { display:inline-block; min-width:1.8em; text-align:center; font-weight:700; border-radius:6px; padding:2px 6px; box-shadow:0 0 0 1px rgba(0,0,0,.08) inset; }
.risk-dot.pre { background:rgb(227,242,253); color:rgb(13,71,161) }        /* P (pre)  */
.risk-dot.res { background:rgb(255,243,205); color:rgb(102,60,0) }         /* R (res)  */
.risk-dot.tgt { background:rgb(232,245,233); color:rgb(27,94,32) }         /* T (tgt)  */

.risk-matrix { border-collapse:collapse; width:100%; table-layout:fixed; margin-top:6px }
.risk-matrix th, .risk-matrix td { border:1px solid #ddd; padding:6px; text-align:center; vertical-align:middle }
.risk-matrix thead th { background:#f6f7f8; font-weight:600 }
.risk-matrix .axis-title { background:#eef1f4; font-weight:600 }
.risk-cell { height:42px; position:relative }
.risk-badges { display:flex; gap:4px; justify-content:center; align-items:center; flex-wrap:wrap }
.risk-note { color:#666; font-size:12px; margin-top:6px }
CSS);

            // Block bauen und einhängen
            $oBlock = $this->BuildRiskDashboardBlock($oObject);
            $oPage->AddUiBlock($oBlock);
        }
    }

    private function BuildSoaDashboardBlock(ISMSSoA $oSoa)
    {
        $iSoaId = (int)$oSoa->GetKey();
        $iStdId = (int)$oSoa->Get('standard_id');

        // Gesamtzahl Controls im Standard
        $iTotalStdCtrls = 0;
        if ($iStdId > 0) {
            $oStdSet = new DBObjectSet(
                DBObjectSearch::FromOQL('SELECT ISMSStandardControl WHERE standard_id = :std'),
                [],
                ['std' => $iStdId]
            );
            $iTotalStdCtrls = $oStdSet->Count();
        }

        // Alle SoA-Entries (KEIN JOIN nötig, wir nutzen ExternalFields)
        $oSet = new DBObjectSet(
            DBObjectSearch::FromOQL('SELECT ISMSSoAEntry WHERE soa_id = :soa'),
            [],
            ['soa' => $iSoaId]
        );

        $tot = 0;
        $applicable = 0;
        $partial = 0;
        $na = 0;
        $impl = ['planned' => 0, 'in_progress' => 0, 'implemented' => 0, 'not_implemented' => 0, '' => 0];
        $byDomain = []; // domain => ['app'=>0,'impl'=>0,'total'=>0]

        while ($o = $oSet->Fetch()) {
            $tot++;
            $domain = (string)$o->Get('standardcontrol_domain');
            if ($domain === '') $domain = Dict::S('ISMSSoA:Dashboard:Undefined', 'Undefined');

            if (!isset($byDomain[$domain])) $byDomain[$domain] = ['app' => 0, 'impl' => 0, 'total' => 0];

            $app = (string)$o->Get('applicability');          // applicable | partial | not_applicable | null
            $st  = (string)$o->Get('implementation_status');  // planned | in_progress | implemented | not_implemented | null

            $byDomain[$domain]['total']++;

            if ($app === 'applicable') {
                $applicable++;
                $byDomain[$domain]['app']++;
            } elseif ($app === 'partial') {
                $partial++;
                $byDomain[$domain]['app']++;
            } elseif ($app === 'not_applicable') {
                $na++;
            }

            $impl[$st] = ($impl[$st] ?? 0) + 1;

            if (($app === 'applicable' || $app === 'partial') && $st === 'implemented') {
                $byDomain[$domain]['impl']++;
            }
        }

        $applicableTotal = $applicable + $partial;
        $implementedAcrossDomains = array_sum(array_column($byDomain, 'impl'));
        $coveragePct = ($applicableTotal > 0) ? round(100 * $implementedAcrossDomains / $applicableTotal) : 0;

        // Render
        $oBlock = UIContentBlockUIBlockFactory::MakeStandard(null, ['soa-dash']);
        $oBlock->AddHtml('<div class="soa-dash">');
        $oBlock->AddHtml('<div class="ibo-panel--title">' . Dict::S('ISMSSoA:Dashboard:Title') . '</div>');

        // KPI-Chips
        $oBlock->AddHtml('<div class="soa-kpis">');
        $oBlock->AddHtml(
            '<span class="soa-chip">' . Dict::S('ISMSSoA:KPIs') . ' • '
                . Dict::S('Class:ISMSSoA/Attribute:kpi_total') . ': ' . (int)$oSoa->Get('kpi_total')
                . ' | ' . Dict::S('Class:ISMSSoA/Attribute:kpi_applicable') . ': ' . $applicableTotal
                . ' | ' . Dict::S('Class:ISMSSoA/Attribute:kpi_implemented') . ': ' . $implementedAcrossDomains
                . ' | ' . Dict::S('Class:ISMSSoA/Attribute:kpi_gaps') . ': ' . (int)$oSoa->Get('kpi_gaps')
                . '</span>'
        );
        if ($iTotalStdCtrls > 0) {
            $oBlock->AddHtml(
                '<span class="soa-chip">' . Dict::Format('ISMSSoA:Dashboard:StdControls:Total', $iTotalStdCtrls) . '</span>'
            );
        }
        $oBlock->AddHtml('<span class="soa-chip">' . sprintf('%s: %d%%', Dict::S('ISMSSoA:Dashboard:Coverage'), $coveragePct) . '</span>');
        $oBlock->AddHtml('</div>');

        // Tabelle nach Domain
        ksort($byDomain, SORT_NATURAL | SORT_FLAG_CASE);
        $oBlock->AddHtml('<table class="soa-grid"><thead><tr>'
            . '<th>' . Dict::S('Class:ISMSStandardControl/Attribute:domain') . '</th>'
            . '<th>' . Dict::S('Class:ISMSSoA/Attribute:kpi_applicable') . '</th>'
            . '<th>' . Dict::S('Class:ISMSSoA/Attribute:kpi_implemented') . '</th>'
            . '<th>' . Dict::S('ISMSSoA:Dashboard:EntriesCount') . '</th>'
            . '<th>' . Dict::S('ISMSSoA:Dashboard:Coverage') . '</th>'
            . '</tr></thead><tbody>');

        foreach ($byDomain as $domain => $row) {
            $app    = (int)$row['app'];
            $implOK = (int)$row['impl'];
            $total  = (int)$row['total'];
            $pct    = ($app > 0) ? round(100 * $implOK / $app) : 0;
            $barW   = max(0, min(100, $pct));

            // OQL sicher zusammenbauen (Domain in Anführungszeichen entgehen)
            $sDom = addslashes($domain); // einfache Absicherung für " und \

            // Basiskriterium: SoA + Domain
            $oqlBase = 'SELECT ISMSSoAEntry WHERE soa_id = ' . $iSoaId . ' AND standardcontrol_domain = "' . $sDom . '"';

            // Links:
            $urlAll         = $this->MakeSearchUrl('ISMSSoAEntry', $oqlBase);
            $urlApplicable  = $this->MakeSearchUrl('ISMSSoAEntry', $oqlBase . ' AND applicability IN ("applicable","partial")');
            $urlImplemented = $this->MakeSearchUrl('ISMSSoAEntry', $oqlBase . ' AND applicability IN ("applicable","partial") AND implementation_status = "implemented"');

            $oBlock->AddHtml('<tr>'
                // Domain klickbar → alle Einträge dieser Domain
                . '<td><a href="' . $urlAll . '" style="text-decoration:none;color:inherit">' . utils::HtmlEntities($domain) . '</a></td>'

                // Applicable klickbar
                . '<td><a href="' . $urlApplicable . '" title="' . Dict::S('Class:ISMSSoA/Attribute:kpi_applicable') . '"'
                . ' style="text-decoration:none;color:inherit">' . $app . '</a></td>'

                // Implemented (nur aus den anwendbaren) klickbar
                . '<td><a href="' . $urlImplemented . '" title="' . Dict::S('Class:ISMSSoA/Attribute:kpi_implemented') . '"'
                . ' style="text-decoration:none;color:inherit">' . $implOK . '</a></td>'

                // Total klickbar
                . '<td><a href="' . $urlAll . '" style="text-decoration:none;color:inherit">' . $total . '</a> '
                . '<span class="soa-subtle">' . Dict::S('ISMSSoA:Entries') . '</span></td>'

                // Coverage-Balken (ohne Link)
                . '<td><div class="soa-bar"><span style="width:' . $barW . '%"></span></div> ' . $pct . '%</td>'
                . '</tr>');
        }
        $oBlock->AddHtml('</tbody></table>');

        // Status-Breakdown
        $oBlock->AddHtml('<div class="soa-subtle" style="margin-top:6px;">'
            . Dict::S('Class:ISMSSoAEntry/Attribute:implementation_status') . ': '
            . 'planned ' . ($impl['planned'] ?? 0) . ' • '
            . Dict::S('Class:ISMSReview/Attribute:status/Value:in_progress') . ' ' . ($impl['in_progress'] ?? 0) . ' • '
            . 'implemented ' . ($impl['implemented'] ?? 0) . ' • '
            . 'not_implemented ' . ($impl['not_implemented'] ?? 0)
            . '</div>');

        $oBlock->AddHtml('</div>');
        return $oBlock;
    }

    private function BuildRiskDashboardBlock(ISMSRisk $oRisk)
    {
        // Werte holen
        $vals = function (string $l, string $i, string $s, string $lvl) use ($oRisk) {
            return array(
                'L' => (string)$oRisk->Get($l),
                'I' => (string)$oRisk->Get($i),
                'S' => (string)$oRisk->Get($s),
                'LVL' => (string)$oRisk->Get($lvl),
            );
        };
        $pre = $vals('pre_likelihood', 'pre_impact', 'pre_score', 'pre_level');
        $res = $vals('res_likelihood', 'res_impact', 'res_score', 'res_level');
        $tgt = $vals('tgt_likelihood', 'tgt_impact', 'tgt_score', 'tgt_level');

        // Hilfen
        $axisL = Dict::S('ISMSRisk:Dashboard:Axis:Likelihood', 'Likelihood');
        $axisI = Dict::S('ISMSRisk:Dashboard:Axis:Impact', 'Impact');

        $fmtLvl = function (string $att, string $code): string {
            if ($code === '') return '';
            // Versuch: Enum-Label aus Attributedefinition holen
            try {
                $oDef = MetaModel::GetAttributeDef('ISMSRisk', $att);
                if ($oDef instanceof AttributeEnum && method_exists($oDef, 'GetLabelForValue')) {
                    $s = $oDef->GetLabelForValue($code);
                    if ($s !== '') return $s;
                }
            } catch (\Exception $e) { /* ignore */
            }
            $k = sprintf('Class:%s/Attribute:%s/Value:%s', 'ISMSRisk', $att, $code);
            $s = Dict::S($k, '');
            return ($s !== '') ? $s : $code;
        };

        // Zell-Hintergrund (Schweregrad) nach Produkt L*I
        $cellStyle = function (int $L, int $I): string {
            $p = $L * $I;
            if ($p >= 17) return 'background:rgb(198,40,40); color:#fff;';      // extreme
            if ($p >= 10) return 'background:rgb(251,140,0); color:#000;';      // high
            if ($p >= 6)  return 'background:rgb(240,244,195); color:#3E4A00;'; // medium
            return 'background:rgb(232,245,233); color:#1B5E20;';               // low
        };

        // Table bauen
        $oBlock = UIContentBlockUIBlockFactory::MakeStandard(null, ['risk-dash']);
        $oBlock->AddHtml('<div class="risk-dash">');
        $oBlock->AddHtml('<div class="ibo-panel--title">' . Dict::S('ISMSRisk:Dashboard:Title', 'Risk evaluation matrix') . '</div>');

        // Score-KPIs
        $oBlock->AddHtml('<div class="risk-kpis">');
        $oBlock->AddHtml('<span class="risk-chip"><span class="risk-dot pre">P</span> '
            . sprintf(
                'L=%s, I=%s, %s=%s (%s)',
                utils::HtmlEntities($pre['L'] ?: '-'),
                utils::HtmlEntities($pre['I'] ?: '-'),
                utils::HtmlEntities(Dict::S('ISMSRisk:Dashboard:Score', 'score')),
                utils::HtmlEntities($pre['S'] ?: '-'),
                utils::HtmlEntities($fmtLvl('pre_level', $pre['LVL']) ?: '-')
            ) . '</span>');
        $oBlock->AddHtml('<span class="risk-chip"><span class="risk-dot res">R</span> '
            . sprintf(
                'L=%s, I=%s, %s=%s (%s)',
                utils::HtmlEntities($res['L'] ?: '-'),
                utils::HtmlEntities($res['I'] ?: '-'),
                utils::HtmlEntities(Dict::S('ISMSRisk:Dashboard:Score', 'score')),
                utils::HtmlEntities($res['S'] ?: '-'),
                utils::HtmlEntities($fmtLvl('res_level', $res['LVL']) ?: '-')
            ) . '</span>');
        $oBlock->AddHtml('<span class="risk-chip"><span class="risk-dot tgt">T</span> '
            . sprintf(
                'L=%s, I=%s, %s=%s (%s)',
                utils::HtmlEntities($tgt['L'] ?: '-'),
                utils::HtmlEntities($tgt['I'] ?: '-'),
                utils::HtmlEntities(Dict::S('ISMSRisk:Dashboard:Score', 'score')),
                utils::HtmlEntities($tgt['S'] ?: '-'),
                utils::HtmlEntities($fmtLvl('tgt_level', $tgt['LVL']) ?: '-')
            ) . '</span>');
        $oBlock->AddHtml('</div>');

        // Matrix
        $oBlock->AddHtml('<table class="risk-matrix">');

        // Erste Kopfzeile mit Achsentiteln
        $oBlock->AddHtml('<thead><tr>'
            . '<th class="axis-title">' . utils::HtmlEntities($axisL) . '</th>'
            . '<th class="axis-title" colspan="5">' . utils::HtmlEntities($axisI) . '</th>'
            . '</tr>');

        // Spaltenkopf Impact 1..5
        $oBlock->AddHtml('<tr><th></th>');
        for ($i = 1; $i <= 5; $i++) {
            $oBlock->AddHtml('<th>' . (string)$i . '</th>');
        }
        $oBlock->AddHtml('</tr></thead><tbody>');

        // Zellen 5..1 (Likelihood als Zeilen, invertiert: 5 → 1)
        for ($L = 5; $L >= 1; $L--) {
            $oBlock->AddHtml('<tr>');
            $oBlock->AddHtml('<th>' . $L . '</th>');
            for ($I = 1; $I <= 5; $I++) {
                $style = $cellStyle($L, $I);
                // Marker sammeln
                $badges = '';
                $mk = function ($tag, $cls, $valL, $valI, $score, $label) {
                    if ($valL === (string)$GLOBALS['L'] && $valI === (string)$GLOBALS['I']) {
                        $t = Dict::Format('ISMSRisk:Dashboard:Tooltip:Point', $label, $valL, $valI, ($score !== '' ? $score : '-'));
                        return '<span class="risk-dot ' . $cls . '" title="' . utils::HtmlEntities($t) . '">' . $tag . '</span>';
                    }
                    return '';
                };
                // Kleiner Trick: aktuelle Zellkoordinaten global bereitstellen für den Closure-Vergleich
                $GLOBALS['L'] = (string)$L;
                $GLOBALS['I'] = (string)$I;

                $badges .= $mk('P', 'pre', $pre['L'], $pre['I'], $pre['S'], Dict::S('ISMSRisk:Dashboard:Legend:Pre', 'Pre'));
                $badges .= $mk('R', 'res', $res['L'], $res['I'], $res['S'], Dict::S('ISMSRisk:Dashboard:Legend:Residual', 'Residual'));
                $badges .= $mk('T', 'tgt', $tgt['L'], $tgt['I'], $tgt['S'], Dict::S('ISMSRisk:Dashboard:Legend:Target', 'Target'));

                $oBlock->AddHtml('<td class="risk-cell" style="' . $style . '"><div class="risk-badges">' . $badges . '</div></td>');
            }
            $oBlock->AddHtml('</tr>');
        }
        $oBlock->AddHtml('</tbody></table>');

        // Legende
        $oBlock->AddHtml('<div class="risk-legend" style="margin-top:6px">'
            . '<span class="risk-dot pre">P</span> ' . Dict::S('ISMSRisk:Dashboard:Legend:Pre', 'Pre')
            . ' &nbsp; <span class="risk-dot res">R</span> ' . Dict::S('ISMSRisk:Dashboard:Legend:Residual', 'Residual')
            . ' &nbsp; <span class="risk-dot tgt">T</span> ' . Dict::S('ISMSRisk:Dashboard:Legend:Target', 'Target')
            . '</div>');

        $oBlock->AddHtml('<div class="risk-note">' . Dict::S('ISMSRisk:Legend:Note', 'Cell background shows severity by L×I') . '</div>');

        $oBlock->AddHtml('</div>');

        return $oBlock;
    }


    private function MakeSearchUrl(string $sClass, string $sOql): string
    {
        // filter ist ein JSON-Array: [ "<OQL>", [], [] ] – dann 2x urlencode
        $aFilter = array($sOql, array(), array());
        $sFilter = urlencode(urlencode(json_encode($aFilter)));
        // c[menu] ist optional; kannst du bei Bedarf auf euer Menü mappen
        return utils::GetAbsoluteUrlAppRoot()
            . 'pages/UI.php?operation=search&filter=' . $sFilter;
        // .'&c[menu]=Search'.$sClass.'s'   // optional
    }

    /**
     * @inheritDoc
     */
    public function OnFormSubmit($oObject, $sFormPrefix = '') {}

    /**
     * @inheritDoc
     */
    public function OnFormCancel($sTempId) {}

    /**
     * @inheritDoc
     */
    public function EnumUsedAttributes($oObject)
    {
        return array();
    }

    /**
     * @inheritDoc
     */
    public function GetIcon($oObject)
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function GetHilightClass($oObject)
    {
        return HILIGHT_CLASS_NONE;
    }

    /**
     * @inheritDoc
     */
    public function EnumAllowedActions(DBObjectSet $oSet)
    {
        return array();
    }
}
