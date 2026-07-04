<?php
ob_start();

require_once("globals.php");
require_once("db.php");
require_once("models/internacao.php");
require_once("dao/internacaoDao.php");
require_once("vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

function getExportParam(string $name, $default = '')
{
    $value = filter_input(INPUT_GET, $name, FILTER_UNSAFE_RAW);
    return $value !== null ? $value : $default;
}

function exportSqlValue($value): string
{
    return addslashes(trim((string)$value));
}

function exportDateBR($value): string
{
    if (!$value || $value === '0000-00-00') {
        return '';
    }
    $ts = strtotime((string)$value);
    return $ts ? date('d/m/Y', $ts) : '';
}

function exportYesNo($value): string
{
    return strtolower((string)$value) === 's' ? 'Sim' : 'Não';
}

$pesquisa_nome = getExportParam('pesquisa_nome', '');
$pesquisa_pac = getExportParam('pesquisa_pac', '');
$senha_int = getExportParam('senha_int', '');
$lote = getExportParam('lote', '');
$med_check = getExportParam('med_check', '');
$enf_check = getExportParam('enf_check', '');
$adm_check = getExportParam('adm_check', '');
$data_intern_int = getExportParam('data_intern_int', '');
$data_intern_int_max = getExportParam('data_intern_int_max', '');
$ordenar = getExportParam('ordenar', 'id_internacao DESC');
$limite = max(1, min(5000, (int)getExportParam('limite', 10)));
$paginaAtual = max(1, (int)getExportParam('pag', 1));
$exportScope = getExportParam('export_scope', 'filtered') === 'current_page' ? 'current_page' : 'filtered';

if ($data_intern_int !== '' && $data_intern_int_max === '') {
    $data_intern_int_max = date('Y-m-d');
}

$auditor = null;
if (isset($_SESSION['nivel']) && ((int)$_SESSION['nivel'] === 3 || (int)$_SESSION['nivel'] === 1)) {
    $auditor = $_SESSION['id_usuario'] ?? null;
}

$condicoes = [
    trim($pesquisa_nome) !== '' ? 'ho.nome_hosp LIKE "%' . exportSqlValue($pesquisa_nome) . '%"' : null,
    trim($pesquisa_pac) !== '' ? 'pa.nome_pac LIKE "%' . exportSqlValue($pesquisa_pac) . '%"' : null,
    trim($senha_int) !== '' ? 'senha_int LIKE "%' . exportSqlValue($senha_int) . '%"' : null,
    'senha_finalizada = "s"',
    trim($med_check) !== '' ? 'med_check = "' . exportSqlValue($med_check) . '"' : null,
    trim($enf_check) !== '' ? 'enfer_check = "' . exportSqlValue($enf_check) . '"' : null,
    trim($adm_check) !== '' ? 'adm_check = "' . exportSqlValue($adm_check) . '"' : null,
    trim($data_intern_int) !== '' ? 'data_intern_int BETWEEN "' . exportSqlValue($data_intern_int) . '" AND "' . exportSqlValue($data_intern_int_max) . '"' : null,
    $auditor ? 'hos.fk_usuario_hosp = "' . exportSqlValue($auditor) . '"' : null,
    trim($lote) !== '' ? 'ca.lote_cap = "' . exportSqlValue($lote) . '"' : null,
];
$where = implode(' AND ', array_filter($condicoes));

$orderOptions = [
    'id_internacao' => 'id_internacao DESC',
    'nome_pac' => 'nome_pac ASC',
    'nome_hosp' => 'nome_hosp ASC',
    'data_intern_int' => 'data_intern_int DESC',
];
$order = $orderOptions[$ordenar] ?? $ordenar;
if (!preg_match('/^[a-zA-Z0-9_\\.]+(\\s+(ASC|DESC))?(\\s*,\\s*[a-zA-Z0-9_\\.]+(\\s+(ASC|DESC))?)*$/i', (string)$order)) {
    $order = 'id_internacao DESC';
}

$limitExport = null;
if ($exportScope === 'current_page') {
    $offset = ($paginaAtual - 1) * $limite;
    $limitExport = $offset . ',' . $limite;
}

$internacaoDao = new internacaoDao($conn, $BASE_URL);
$dados = $internacaoDao->selectAllInternacaoCapList($where, $order, $limitExport);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Senhas Finalizadas');
$sheet->setShowGridlines(false);

$headers = [
    'Reg',
    'Conta No.',
    'Hospital',
    'Paciente',
    'Senha',
    'Data internação',
    'Lote',
    'Médico',
    'Enfermagem',
    'Administrativo',
    'Data fechamento',
    'Data digitação',
    'Valor apresentado',
    'Valor final',
];

$logoPath = __DIR__ . '/img/LogoConexAud.png';
if (file_exists($logoPath)) {
    $logo = new Drawing();
    $logo->setName('Logo');
    $logo->setDescription('Logo FullCare');
    $logo->setPath($logoPath);
    $logo->setHeight(32);
    $logo->setCoordinates('A2');
    $logo->setWorksheet($sheet);
}

$lastCol = Coordinate::stringFromColumnIndex(count($headers));
$sheet->getRowDimension(1)->setRowHeight(28);
$sheet->getRowDimension(2)->setRowHeight(18);
$sheet->setCellValue('D1', 'Capeantes com senha finalizada');
$sheet->mergeCells('D1:' . $lastCol . '1');
$sheet->setCellValue('D2', 'Data da extração: ' . date('d/m/Y H:i'));
$sheet->mergeCells('D2:' . $lastCol . '2');
$sheet->getStyle('D1')->getFont()->setBold(true)->setSize(13);

$headerRow = 6;
$sheet->fromArray($headers, null, 'A' . $headerRow);

$row = $headerRow + 1;
foreach ($dados as $item) {
    $sheet->setCellValue('A' . $row, $item['id_internacao'] ?? '');
    $sheet->setCellValue('B' . $row, $item['id_capeante'] ?? '');
    $sheet->setCellValue('C' . $row, $item['nome_hosp'] ?? '');
    $sheet->setCellValue('D' . $row, $item['nome_pac'] ?? '');
    $sheet->setCellValue('E' . $row, $item['senha_int'] ?? '');
    $sheet->setCellValue('F' . $row, exportDateBR($item['data_intern_int'] ?? ''));
    $sheet->setCellValue('G' . $row, $item['lote_cap'] ?? '');
    $sheet->setCellValue('H' . $row, exportYesNo($item['med_check'] ?? ''));
    $sheet->setCellValue('I' . $row, exportYesNo($item['enfer_check'] ?? ''));
    $sheet->setCellValue('J' . $row, exportYesNo($item['adm_check'] ?? ''));
    $sheet->setCellValue('K' . $row, exportDateBR($item['data_fech_capeante'] ?? ''));
    $sheet->setCellValue('L' . $row, exportDateBR($item['data_digit_capeante'] ?? ''));
    $sheet->setCellValue('M' . $row, (float)($item['valor_apresentado_capeante'] ?? 0));
    $sheet->setCellValue('N' . $row, (float)($item['valor_final_capeante'] ?? 0));
    $row++;
}

$headerStyle = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E5EEF7'],
    ],
    'font' => ['bold' => true, 'color' => ['rgb' => '24384F']],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CBD5E1'],
        ],
    ],
];
$borderStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'E2E8F0'],
        ],
    ],
];

$lastRow = max($headerRow, $row - 1);
$sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray($headerStyle);
$sheet->getStyle('A' . $headerRow . ':' . $lastCol . $lastRow)->applyFromArray($borderStyle);
$sheet->getStyle('A' . $headerRow . ':' . $lastCol . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
if ($row > $headerRow + 1) {
    $sheet->getStyle('M' . ($headerRow + 1) . ':N' . $lastRow)->getNumberFormat()->setFormatCode('"R$" #,##0.00');
}

foreach (range(1, count($headers)) as $colIndex) {
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$fileName = 'senhas_finalizadas_' . date('Ymd_His') . '.xlsx';

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
