<?php
require_once 'vendor/autoload.php';
require_once 'Connection.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Color, Fill, Font};
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/* ─── Query ─── */
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 200;

$rows = [];
if (!empty($conn)) {
    $result = $conn->query(
        "SELECT created_at, soil1_moisture, soil2_wet,
                temperature, humidity, water_level_cm, pump_status
         FROM sensor_readings
         ORDER BY created_at DESC
         LIMIT $limit"
    );
    if ($result && $result->num_rows > 0) {
        while ($r = $result->fetch_assoc()) $rows[] = $r;
    }
    $conn->close();
}

/* ─── Spreadsheet setup ─── */
$ss    = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('sensor_readings');

/* ═══ STYLES ═══ */
$DARK_GREEN  = '1F4D2A';
$MID_GREEN   = '3A7D44';
$LIGHT_GREEN = 'E8F5E9';
$ALT_ROW     = 'F5FAF5';
$WHITE       = 'FFFFFF';
$TEXT_DARK   = '1A1A1A';
$TEXT_GRAY   = '555555';

$headerStyle = [
    'font'      => ['bold' => true, 'color' => ['rgb' => $WHITE], 'name' => 'Arial', 'size' => 10],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $DARK_GREEN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                     'color'        => ['rgb' => '388E3C']]],
];

$dataFont = ['name' => 'Arial', 'size' => 9, 'color' => ['rgb' => $TEXT_DARK]];
$centerAlign = ['horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER];

$thinBorder = ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                'color'        => ['rgb' => 'CCCCCC']]];

/* ═══ HEADER ROW ═══ */
$headers = [
    'A1' => 'Timestamp',
    'B1' => 'Soil 1 Moisture (%)',
    'C1' => 'Soil 2 Status',
    'D1' => 'Temperature (°C)',
    'E1' => 'Humidity (%)',
    'F1' => 'Water Level (cm)',
    'G1' => 'Pump Status',
];

foreach ($headers as $cell => $label) {
    $sheet->setCellValue($cell, $label);
}

$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(28);

/* ═══ DATA ROWS ═══ */
$r = 2;
foreach ($rows as $data) {

    /* Timestamp — write as real Excel date so it sorts & filters properly */
    $dtObj = new DateTime($data['created_at']);
    $excelDate = ExcelDate::PHPToExcel($dtObj);
    $sheet->setCellValue("A$r", $excelDate);
    $sheet->getStyle("A$r")->getNumberFormat()
          ->setFormatCode('YYYY-MM-DD HH:MM:SS');

    $sheet->setCellValue("B$r", (float)$data['soil1_moisture']);
    $sheet->setCellValue("C$r", (int)$data['soil2_wet'] === 1 ? 'DRY' : 'WET');
    $sheet->setCellValue("D$r", (float)$data['temperature']);
    $sheet->setCellValue("E$r", (float)$data['humidity']);
    $sheet->setCellValue("F$r", (float)$data['water_level_cm']);
    $sheet->setCellValue("G$r", (int)$data['pump_status'] === 1 ? 'ON' : 'OFF');

    /* Alternating row fill */
    $fillColor = ($r % 2 === 0) ? $ALT_ROW : $WHITE;
    $rowStyle = [
        'font'      => $dataFont,
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fillColor]],
        'alignment' => $centerAlign,
        'borders'   => $thinBorder,
    ];
    $sheet->getStyle("A$r:G$r")->applyFromArray($rowStyle);

    /* Conditional coloring — Soil 2 */
    $s2Color = ((int)$data['soil2_wet'] === 1) ? 'E65100' : '2E7D32'; // orange=dry, green=wet
    $sheet->getStyle("C$r")->getFont()->getColor()->setARGB('FF' . $s2Color);
    $sheet->getStyle("C$r")->getFont()->setBold(true);

    /* Conditional coloring — Pump */
    $pumpColor = ((int)$data['pump_status'] === 1) ? '1565C0' : '757575';
    $sheet->getStyle("G$r")->getFont()->getColor()->setARGB('FF' . $pumpColor);
    $sheet->getStyle("G$r")->getFont()->setBold(true);

    $sheet->getRowDimension($r)->setRowHeight(18);
    $r++;
}

/* ═══ COLUMN WIDTHS ═══ */
$widths = ['A' => 22, 'B' => 20, 'C' => 15, 'D' => 18, 'E' => 15, 'F' => 20, 'G' => 14];
foreach ($widths as $col => $w) {
    $sheet->getColumnDimension($col)->setWidth($w);
}

/* ═══ FREEZE + AUTOFILTER ═══ */
$sheet->freezePane('A2');
$lastRow = $r - 1;
$sheet->setAutoFilter("A1:G$lastRow");

/* ═══ TITLE ROW above header ═══ */
$sheet->insertNewRowBefore(1, 1);
$sheet->mergeCells('A1:G1');
$exportTime = (new DateTime())->format('Y-m-d H:i:s');
$sheet->setCellValue('A1', "I still don't know Shiggy · Sensor Readings Export · Generated: $exportTime");
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'color' => ['rgb' => $WHITE], 'name' => 'Arial', 'size' => 11],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $MID_GREEN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(24);

/* ═══ OUTPUT ═══ */
$filename = 'irrisense_readings_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($ss);
$writer->save('php://output');
exit;