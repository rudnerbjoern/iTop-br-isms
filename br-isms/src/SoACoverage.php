<?php

use Combodo\iTop\Application\UI\Base\Layout\UIContentBlockUIBlockFactory;

class SoACoverageUIExtension implements iApplicationUIExtension
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
        if (!($oObject instanceof ISMSSoA)) return;

        // Minimaler Tab-Titel (sprachabhängig)
        $oPage->SetCurrentTab(Dict::S('ISMSSoA:Tab:Dashboard', 'Dashboard'));

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
        $oBlock = $this->BuildDashboardBlock($oObject);
        $oPage->AddUiBlock($oBlock);
    }

    private function BuildDashboardBlock(ISMSSoA $oSoa)
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
            if ($domain === '') $domain = Dict::S('UI:CSVImport:Undefined', 'Undefined');

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
        $oBlock->AddHtml('<div class="ibo-panel--title">' . Dict::S('ISMSSoA:Coverage') . '</div>');

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
                '<span class="soa-chip">' . Dict::Format('ISMSSoA:StdControls:Total', $iTotalStdCtrls) . '</span>'
            );
        }
        $oBlock->AddHtml('<span class="soa-chip">' . sprintf('%s: %d%%', Dict::S('ISMSSoA:Coverage'), $coveragePct) . '</span>');
        $oBlock->AddHtml('</div>');

        // Tabelle nach Domain
        ksort($byDomain, SORT_NATURAL | SORT_FLAG_CASE);
        $oBlock->AddHtml('<table class="soa-grid"><thead><tr>'
            . '<th>' . Dict::S('Class:ISMSStandardControl/Attribute:domain') . '</th>'
            . '<th>' . Dict::S('Class:ISMSSoA/Attribute:kpi_applicable') . '</th>'
            . '<th>' . Dict::S('Class:ISMSSoA/Attribute:kpi_implemented') . '</th>'
            . '<th>' . Dict::S('ISMSSoA:EntriesCount') . '</th>'
            . '<th>' . Dict::S('ISMSSoA:Coverage') . '</th>'
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
