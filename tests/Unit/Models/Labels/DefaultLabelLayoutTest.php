<?php

namespace Tests\Unit\Models\Labels;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Labels\DefaultLabel;
use App\Models\Labels\FieldOption;
use Illuminate\Support\Collection;
use TCPDF;
use Tests\TestCase;

class DefaultLabelLayoutTest extends TestCase
{
    /**
     * Writes one label and returns every text cell with its top and bottom
     * (inches), plus where the 1D barcode strip starts.
     *
     * @return array{cells: list<array{text: string, top: float, bottom: float, style: string}>, barcodeTop: ?float}
     */
    private function render(DefaultLabel $label, Collection $record): array
    {
        $pdf = new class('L', 'in', [$label->getLabelWidth(), $label->getLabelHeight()]) extends TCPDF
        {
            public array $cells = [];

            public ?float $barcodeTop = null;

            public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M')
            {
                if (trim((string) $txt) !== '') {
                    $this->cells[] = ['text' => $txt, 'top' => $this->GetY(), 'bottom' => $this->GetY() + $h, 'style' => $this->FontStyle];
                }

                return parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link, $stretch, $ignore_min_height, $calign, $valign);
            }

            public function write1DBarcode($code, $type, $x = '', $y = '', $w = '', $h = '', $xres = '', $style = [], $align = '')
            {
                $this->barcodeTop = (float) $y;

                return parent::write1DBarcode($code, $type, $x, $y, $w, $h, $xres, $style, $align);
            }
        };
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $label->write($pdf, $record);

        return ['cells' => $pdf->cells, 'barcodeTop' => $pdf->barcodeTop];
    }

    private function record(array $fields): Collection
    {
        return collect([
            'tag' => 'NY-001-LP-W11',
            'title' => null,
            'barcode1d' => (object) ['type' => 'C128', 'content' => 'NY-001-LP-W11'],
            'fields' => collect($fields),
        ]);
    }

    public function test_the_asset_tag_is_printed_first_and_in_bold(): void
    {
        $this->settings->set(['labels_width' => 2.625, 'labels_height' => 1, 'labels_fontsize' => 9]);

        $result = $this->render(new DefaultLabel, $this->record([
            ['label' => 'N:', 'value' => 'Reception laptop', 'dataSource' => 'name'],
        ]));

        $this->assertSame('NY-001-LP-W11', $result['cells'][0]['text']);
        $this->assertStringContainsStringIgnoringCase('b', $result['cells'][0]['style']);
        $this->assertSame('N: Reception laptop', $result['cells'][1]['text']);
        $this->assertGreaterThanOrEqual($result['cells'][0]['bottom'], $result['cells'][1]['top']);
    }

    public function test_text_never_runs_into_the_barcode(): void
    {
        $this->settings->set(['labels_width' => 2.625, 'labels_height' => 1, 'labels_fontsize' => 9]);

        $result = $this->render(new DefaultLabel, $this->record(array_map(
            fn (int $i) => ['label' => 'F'.$i.':', 'value' => 'value '.$i, 'dataSource' => 'field'.$i],
            range(1, 4),
        )));

        $this->assertNotNull($result['barcodeTop']);
        foreach ($result['cells'] as $cell) {
            $this->assertLessThanOrEqual($result['barcodeTop'] + 0.001, $cell['bottom'], $cell['text'].' overlaps the barcode');
        }
    }

    public function test_the_tag_is_not_repeated_when_it_is_also_a_field(): void
    {
        $this->settings->set(['labels_width' => 2.625, 'labels_height' => 1, 'labels_fontsize' => 9]);

        $result = $this->render(new DefaultLabel, $this->record([
            ['label' => '', 'value' => 'NY-001-LP-W11', 'dataSource' => 'asset_tag'],
        ]));

        $this->assertSame(1, collect($result['cells'])->where('text', 'NY-001-LP-W11')->count());
    }

    public function test_company_field_falls_back_to_the_site_name(): void
    {
        $this->settings->set(['site_name' => 'Acme IT']);
        $option = FieldOption::fromString('Company=company.name');

        $this->assertSame('Acme IT', $option->getValue(Asset::factory()->create(['company_id' => null])));

        $company = Company::factory()->create(['name' => 'Branch Office']);
        $this->assertSame('Branch Office', $option->getValue(Asset::factory()->create(['company_id' => $company->id])));
    }
}
