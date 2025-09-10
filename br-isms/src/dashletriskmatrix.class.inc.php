<?php

use Combodo\iTop\Application\UI\Base\Layout\UIContentBlock;
use Combodo\iTop\Application\UI\Base\Layout\UIContentBlockUIBlockFactory;

class DashletRiskMatrix extends Dashlet
{

    static protected array $aAttributeList = [];

    public function __construct($oModelReflection, $sId)
    {
        parent::__construct($oModelReflection, $sId);
        $this->aProperties['title'] = Dict::S('UI:DashletRiskMatrix:Title');
        $this->aProperties['query'] = 'SELECT ISMSRisk WHERE status = "published"';
        $this->aProperties['x_axis'] = 'res_impact';     // 1..5
        $this->aProperties['y_axis'] = 'res_likelihood'; // 1..5
        $this->aProperties['link'] = false; // drill down
        $this->aProperties['totals'] = true; // show row/col totals
        $this->aProperties['color_mode']  = 'count'; // 'count'|'severity'|'level'
        $this->aProperties['show_percent'] =  false;   // bool
        $this->aProperties['include_na']  = false;   // bool
    }

    public function Render($oPage, $bEditMode = false, $aExtraParams = array())
    {
        $sTitle  = $this->aProperties['title'];
        $sOQL    = $this->aProperties['query'];
        $x       = $this->aProperties['x_axis'];
        $y       = $this->aProperties['y_axis'];
        $bLink   = (bool)($this->aProperties['link'] ?? false);
        $bTotals = (bool)($this->aProperties['totals'] ?? true);
        $colorMode  = $this->aProperties['color_mode']  ?? 'count';
        $showPct    = (bool)($this->aProperties['show_percent'] ?? false);
        $includeNA  = (bool)($this->aProperties['include_na']   ?? false);

        $sId = sprintf('riskmatrix_%s%s', (string)$this->sId, $bEditMode ? '_edit' : '');

        // CSS
        $oPage->add_style(
            <<<STYLE
.risk-matrix-table{border-collapse:collapse;width:100%;table-layout:fixed}
.risk-matrix-table th,.risk-matrix-table td{border:1px solid #ddd;padding:6px;text-align:center}
.risk-matrix-table th{background:#f6f7f8;font-weight:600}
.risk-matrix-table thead th { position: sticky; top: 0; z-index: 2; }
.risk-matrix-cell a{display:block;text-decoration:none;color:inherit}
.risk-matrix-badge{border-radius:6px;padding:4px 6px;display:inline-block;min-width:2.4em;font-weight: 600;}
.risk-matrix-badge.is-na{background: #f2f2f2;color: #666;}
.risk-matrix-axis-title{background: #eef1f4;font-weight:600;text-align:center}
STYLE
        );

        $oBlock = UIContentBlockUIBlockFactory::MakeStandard(null, ["dashlet-content"]);

        // Klasse aus der Query bestimmen (für die Dictionary-Lookups)
        $sClass = 'ISMSRisk';
        try {
            $sClass = $this->oModelReflection->GetQuery($sOQL)->GetClass();
        } catch (\Exception $e) {
        }

        // Codes in gewünschter Reihenfolge: 1..5 ODER low..extreme
        $fnCodes = static function (string $att): array {
            return preg_match('/_level$/', $att) ? ['low', 'medium', 'high', 'extreme'] : ['1', '2', '3', '4', '5'];
        };
        $aXCodes = $fnCodes($x);
        $aYCodes = $fnCodes($y);

        // „(n/a)“-Bucket ergänzen
        if ($includeNA) {
            array_unshift($aXCodes, '(n/a)');
            array_unshift($aYCodes, '(n/a)');
        }

        // Grid + Max-Level je Zelle initialisieren
        $grid = [];
        $rowTotals = [];
        $colTotals = [];
        $grand = 0;
        $maxLevelInCell = [];
        foreach ($aYCodes as $vy) {
            $rowTotals[$vy] = 0;
            $maxLevelInCell[$vy] = [];
            foreach ($aXCodes as $vx) {
                $grid[$vy][$vx] = 0;
                $maxLevelInCell[$vy][$vx] = 0;
            }
        }
        foreach ($aXCodes as $vx) {
            $colTotals[$vx] = 0;
        }

        $levelScoreMap = ['low' => 1, 'medium' => 2, 'high' => 3, 'extreme' => 4];

        // Daten holen
        try {
            $oSearch = DBObjectSearch::FromOQL($sOQL);
        } catch (\Exception $e) {
            $oBlock->AddHtml('<div class="ibo-alert ibo-is-error">' . utils::HtmlEntities($e->getMessage()) . '</div>');
            return $oBlock;
        }
        $oSet    = new DBObjectSet($oSearch);

        while ($o = $oSet->Fetch()) {
            $vx = (string)$o->Get($x);
            $vy = (string)$o->Get($y);

            // (n/a)-Handling
            if ($vx === '' || !in_array($vx, $aXCodes, true)) {
                $vx = $includeNA ? '(n/a)' : '';
            }
            if ($vy === '' || !in_array($vy, $aYCodes, true)) {
                $vy = $includeNA ? '(n/a)' : '';
            }

            if ($vx === '' || $vy === '') continue;
            if (!isset($grid[$vy][$vx])) continue;

            $grid[$vy][$vx]++;
            $rowTotals[$vy]++;
            $colTotals[$vx]++;
            $grand++;

            // Max-Level (aus tatsächlichem res_level) für color_mode='level'
            $sCellLevel = (string)$o->Get('res_level');
            if (isset($levelScoreMap[$sCellLevel])) {
                $maxLevelInCell[$vy][$vx] = max($maxLevelInCell[$vy][$vx], $levelScoreMap[$sCellLevel]);
            }
        }

        if ($grand === 0) {
            $oBlock->AddHtml('<div class="ibo-alert ibo-is-info">' . Dict::S('UI:DashletRiskMatrix:NoData', 'No data for current scope') . '</div>');
            return $oBlock;
        }

        // Max für Heat
        $max = 0;
        foreach ($aYCodes as $vy) {
            foreach ($aXCodes as $vx) {
                $max = max($max, $grid[$vy][$vx]);
            }
        }

        // Heatmap nach Anzahl
        $countColor = function (int $count, int $max): string {
            if ($max <= 0) return 'background: #f5f7fa;color: #555;';
            if ($count === 0) return 'background: #f5f7fa;color: #777;'; // << HELL & LESBAR

            $t = $count / $max; // 0..1
            // HSL-Verlauf: von sehr hell (t≈0) zu kräftig (t≈1), Grün->Rot
            $h = 120 * (1 - $t);
            $s = 30 + 50 * $t;      // Sättigung wächst mit t (30%..80%)
            $l = 95 - 55 * $t;      // Helligkeit sinkt mit t (95%..40%)

            // HSL -> RGB (inline, reicht fürs Dashlet)
            $C = (1 - abs(2 * $l / 100 - 1)) * ($s / 100);
            $X = $C * (1 - abs(fmod($h / 60, 2) - 1));
            $m = $l / 100 - $C / 2;
            $r = $g = $b = 0;
            if ($h < 60) {
                $r = $C;
                $g = $X;
            } elseif ($h < 120) {
                $r = $X;
                $g = $C;
            }
            $R = (int) round(255 * ($r + $m));
            $G = (int) round(255 * ($g + $m));
            $B = (int) round(255 * ($b + $m));

            // Lesbarer Vordergrund per Luminanz
            $lum = 0.2126 * $R + 0.7152 * $G + 0.0722 * $B;
            $fg  = ($lum < 140) ? '#fff' : '#111';
            return "background:rgb($R,$G,$B);color:$fg;";
        };

        // Ampel je nach Produkt (vx*vy)
        $severityColor = function (string $vx, string $vy): string {
            if ($vx === '(n/a)' || $vy === '(n/a)') return 'background: #f2f2f2;color: #666;';
            $p = ((int)$vx) * ((int)$vy);
            if ($p >= 17) return 'background:rgb(198,40,40);color:rgb(255,255,255);'; // extreme
            if ($p >= 10) return 'background:rgb(251,140,0);color:rgb(0,0,0);';      // high
            if ($p >= 6)  return 'background:rgb(240,244,195);color:rgb(62,74,0);';  // medium
            return 'background:rgb(232,245,233);color:rgb(27,94,32);';               // low
        };

        // Farbe aus tatsächlichem res_level in der Zelle (max. vorkommendes Level)
        $levelColor = function (int $score): string {
            switch ($score) {
                case 4:
                    return 'background:rgb(198,40,40);color: #fff;';    // extreme
                case 3:
                    return 'background:rgb(251,140,0);color: #000;';    // high
                case 2:
                    return 'background:rgb(240,244,195);color: #3E4A00;'; // medium
                case 1:
                    return 'background:rgb(232,245,233);color: #1B5E20;'; // low
                default:
                    return 'background:rgba(0,0,0,0.02);color: #333;';    // keine Daten
            }
        };

        // Enum-/Code-zu-Label Formatter (inkl. (n/a)-Behandlung)
        $fmtCode = function (string $att, string $code) use ($sClass): string {
            if ($code === '(n/a)') return Dict::S('UI:DashletRiskMatrix:NA', 'N/A');
            try {
                $oDef = MetaModel::GetAttributeDef($sClass, $att);
                if (($oDef instanceof AttributeEnum) && method_exists($oDef, 'GetLabelForValue')) {
                    $s = $oDef->GetLabelForValue($code);
                    if ($s !== '') return $s;
                }
            } catch (\Exception $e) { /* ignore */
            }
            $key = sprintf('Class:%s/Attribute:%s/Value:%s', $sClass, $att, $code);
            $s = Dict::S($key, '');
            return ($s !== '') ? $s : $code;
        };

        $buildPredicate = function (string $att, string $code): string {
            if ($code === '(n/a)') {
                return sprintf('(%1$s IS NULL OR %1$s = "")', $att);
            }
            return $att . " = '" . addslashes($code) . "'";
        };

        $makeUrl = function (string $oql) use ($sClass) {
            return utils::GetAbsoluteUrlAppRoot() . 'pages/UI.php?operation=search&class=' . $sClass . '&filter=' . rawurlencode(json_encode([$oql, [], []]));
        };

        // Render
        $oBlock->AddHtml('<div id="' . utils::HtmlEntities($sId) . '" class="ibo-panel--body">');
        $oBlock->AddHtml('<div class="risk-matrix-dashlet">');
        $oBlock->AddHtml('<h3>' . utils::HtmlEntities($sTitle) . '</h3>');

        // Klasse & Achsen-Attributlabels ermitteln
        try {
            $xAttrLabel = MetaModel::GetAttributeDef($sClass, $x)->GetLabel(); // z.B. "Impact (residual)"
            $yAttrLabel = MetaModel::GetAttributeDef($sClass, $y)->GetLabel(); // z.B. "Likelihood (residual)"
        } catch (\Exception $e) {
            $sClass = 'ISMSRisk';
            $xAttrLabel = Dict::S('UI:DashletRiskMatrix:Impact', 'Impact');
            $yAttrLabel = Dict::S('UI:DashletRiskMatrix:Likelihood', 'Likelihood');
        }

        $oBlock->AddHtml('<table class="risk-matrix-table">');
        $oBlock->AddHtml('<thead>');

        //  Überschrift: Y-Attribut links, X-Attribut über die Spalten
        $colspan = count($aXCodes) + ($bTotals ? 1 : 0);
        $oBlock->AddHtml('<tr>'
            . '<th class="risk-matrix-axis-title">' . utils::HtmlEntities($yAttrLabel) . '</th>'
            . '<th class="risk-matrix-axis-title" colspan="' . $colspan . '">' . utils::HtmlEntities($xAttrLabel) . '</th>'
            . '</tr>');

        // Kopfzeile
        $oBlock->AddHtml('<tr><th></th>');
        foreach ($aXCodes as $vx) {
            $predX = $buildPredicate($x, $vx);
            $q = $sOQL . ' AND ' . $predX;
            $url = $makeUrl($q);
            $xLabel = $fmtCode($x, $vx);
            $oBlock->AddHtml('<th><a href="' . $url . '" style="text-decoration:none;color:inherit">' . utils::HtmlEntities($xLabel) . '</a></th>');
        }
        if ($bTotals) {
            $oBlock->AddHtml('<th>' . Dict::S('UI:DashletRiskMatrix:Total', 'Total') . '</th>');
        }
        $oBlock->AddHtml('</tr>');
        $oBlock->AddHtml('</thead><tbody>');

        // Zeilen rendern (links Y-Wertelabels)
        foreach ($aYCodes as $vy) {
            // Zeilenkopf (Y-Achse)
            $predY = $buildPredicate($y, $vy);
            $qRow = $sOQL . ' AND ' . $predY;
            $urlRow = $makeUrl($qRow);
            $yLabel = $fmtCode($y, $vy);
            $oBlock->AddHtml('<tr><th><a href="' . $urlRow . '" style="text-decoration:none;color:inherit">' . utils::HtmlEntities($yLabel) . '</a></th>');
            foreach ($aXCodes as $vx) {
                $c = $grid[$vy][$vx];
                $style = ''; // wird gleich gesetzt
                if ($colorMode === 'level') {
                    $style = $levelColor($maxLevelInCell[$vy][$vx] ?? 0);
                } elseif ($colorMode === 'severity') {
                    $style = $severityColor($vx, $vy);
                } else { // 'count'
                    $style = $countColor($c, $max);
                }
                $xLabel = $fmtCode($x, $vx);
                $title = utils::HtmlEntities($yAttrLabel . ': ' . $yLabel . ' × ' . $xAttrLabel . ': ' . $xLabel . ' • ' . $c);
                $label = (string)$c;
                if ($showPct) {
                    $pct = ($grand > 0) ? round(100 * $c / $grand) : 0;
                    $label .= '<br><small>' . $pct . '%</small>';
                }
                $cls = ($vx === '(n/a)' || $vy === '(n/a)') ? ' risk-matrix-badge is-na' : ' risk-matrix-badge';
                $content = '<span class="' . $cls . '" style="' . $style . '" title="' . $title . '">' . $label . '</span>';
                if ($bLink && $c > 0) {
                    $predX = $buildPredicate($x, $vx);
                    $predY = $buildPredicate($y, $vy);
                    $q = $sOQL . ' AND ' . $predX . ' AND ' . $predY;
                    $url = $makeUrl($q);
                    $content = '<a href="' . $url . '">' . $content . '</a>';
                }
                $oBlock->AddHtml('<td class="risk-matrix-cell">' . $content . '</td>');
            }
            if ($bTotals) $oBlock->AddHtml('<td><strong>' . $rowTotals[$vy] . '</strong></td>');
            $oBlock->AddHtml('</tr>');
        }

        // Footer (Totals)
        if ($bTotals) {
            $oBlock->AddHtml('<tr><th>' . Dict::S('UI:DashletRiskMatrix:Total', 'Total') . '</th>');
            foreach ($aXCodes as $vx) {
                $oBlock->AddHtml('<td><strong>' . $colTotals[$vx] . '</strong></td>');
            }
            $oBlock->AddHtml('<td><strong>' . $grand . '</strong></td></tr>');
        }

        $oBlock->AddHtml('</tbody></table>');

        if ($colorMode === 'severity') {
            $oBlock->AddHtml('<div style="margin-top:6px;font-size:12px;display:flex;gap:8px;flex-wrap:wrap;">'
                . '<span class="risk-matrix-badge" style="background:rgb(232,245,233);color:rgb(27,94,32);">' . Dict::S('UI:DashletRiskMatrix:Legend:Low', 'Low') . '</span>'
                . '<span class="risk-matrix-badge" style="background:rgb(240,244,195);color:rgb(62,74,0);">' . Dict::S('UI:DashletRiskMatrix:Legend:Medium', 'Medium') . '</span>'
                . '<span class="risk-matrix-badge" style="background:rgb(251,140,0);color:rgb(0,0,0);">' . Dict::S('UI:DashletRiskMatrix:Legend:High', 'High') . '</span>'
                . '<span class="risk-matrix-badge" style="background:rgb(198,40,40);color:rgb(255,255,255);">' . Dict::S('UI:DashletRiskMatrix:Legend:Extreme', 'Extreme') . '</span>'
                . '</div>');
        }

        $oBlock->AddHtml('</div>');
        $oBlock->AddHtml('</div>');

        if ($bEditMode) return $oBlock;

        return $oBlock;
    }

    public static function GetInfo(): array
    {
        return array(
            'label' => Dict::S('UI:DashletRiskMatrix:Label'),
            'icon' => 'env-' . utils::GetCurrentEnvironment() . '/br-isms/images/isms-risk.svg',
            'description' => Dict::S('UI:DashletRiskMatrix:Description'),
        );
    }

    public function GetPropertiesFields(DesignerForm $oForm): void
    {
        $oTitleField = new DesignerTextField('title', Dict::S('UI:DashletRiskMatrix:Prop-Title'), utils::HtmlEntities($this->aProperties['title']));
        $oForm->AddField($oTitleField);

        $oQueryField = new DesignerLongTextField('query', Dict::S('UI:DashletRiskMatrix:Prop-Query'), $this->aProperties['query']);
        $oQueryField->SetMandatory();
        $oForm->AddField($oQueryField);

        try {
            $sClass = $this->oModelReflection->GetQuery($this->aProperties['query'])->GetClass();
            $oXAxisField = new DesignerComboField('x_axis', Dict::S('UI:DashletRiskMatrix:Prop-X'), $this->aProperties['x_axis']);
            $oXAxisField->SetAllowedValues(static::GetEnumAttributes($sClass));
            $oXAxisField->SetMandatory();
        } catch (OQLException $e) {
            $oXAxisField = new DesignerStaticTextField('x_axis', Dict::S('UI:DashletRiskMatrix:Prop-X'));
        } finally {
            $oForm->AddField($oXAxisField);
        }

        try {
            $sClass = $this->oModelReflection->GetQuery($this->aProperties['query'])->GetClass();
            $oYAxisField = new DesignerComboField('y_axis', Dict::S('UI:DashletRiskMatrix:Prop-Y'), $this->aProperties['y_axis']);
            $oYAxisField->SetAllowedValues(static::GetEnumAttributes($sClass));
            $oYAxisField->SetMandatory();
        } catch (OQLException $e) {
            $oYAxisField = new DesignerStaticTextField('y_axis', Dict::S('UI:DashletRiskMatrix:Prop-Y'));
        } finally {
            $oForm->AddField($oYAxisField);
        }

        $oLinkField = new DesignerBooleanField('link',   Dict::S('UI:DashletRiskMatrix:Prop-Link'),   (bool)$this->aProperties['link']);
        $oForm->AddField($oLinkField);

        $oTotalsField = new DesignerBooleanField('totals', Dict::S('UI:DashletRiskMatrix:Prop-Totals'), (bool)$this->aProperties['totals']);
        $oForm->AddField($oTotalsField);

        $oColorModeField = new DesignerComboField('color_mode', Dict::S('UI:DashletRiskMatrix:Prop-ColorMode'), $this->aProperties['color_mode']);
        $oColorModeField->SetAllowedValues(array(
            'count'    => Dict::S('UI:DashletRiskMatrix:ColorMode:Count'),
            'severity' => Dict::S('UI:DashletRiskMatrix:ColorMode:Severity'),
            'level'    => Dict::S('UI:DashletRiskMatrix:ColorMode:Level'),
        ));
        $oColorModeField->SetMandatory();
        $oForm->AddField($oColorModeField);

        $oShowPctField = new DesignerBooleanField('show_percent', Dict::S('UI:DashletRiskMatrix:Prop-ShowPercent'), (bool)$this->aProperties['show_percent']);
        $oForm->AddField($oShowPctField);

        $oIncludeNAField = new DesignerBooleanField('include_na', Dict::S('UI:DashletRiskMatrix:Prop-IncludeNA'), (bool)$this->aProperties['include_na']);
        $oForm->AddField($oIncludeNAField);
    }

    protected static function GetEnumAttributes(string $sClass): array
    {
        if (isset(static::$aAttributeList[$sClass])) return static::$aAttributeList[$sClass];

        $aAttributes = array();
        foreach (MetaModel::ListAttributeDefs($sClass) as $sAttribute => $oAttributeDef) {
            if ($oAttributeDef instanceof AttributeEnum) {
                $aAttributes[$sAttribute] = $oAttributeDef->GetLabel();
            }
        }
        static::$aAttributeList[$sClass] = $aAttributes;

        return $aAttributes;
    }
}
