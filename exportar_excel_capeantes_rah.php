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

function rahExportParam(string $name, $default = '')
{
    $value = filter_input(INPUT_GET, $name, FILTER_UNSAFE_RAW);
    return $value !== null ? $value : $default;
}

function rahExportSqlValue($value): string
{
    return addslashes(trim((string)$value));
}

function rahExportDateBr($value): string
{
    if (!$value || $value === '0000-00-00') {
        return '';
    }
    $ts = strtotime((string)$value);
    return $ts ? date('d/m/Y', $ts) : '';
}

function rahExportYesNo($value): string
{
    $value = strtolower((string)$value);
    if ($value === 's') {
        return 'Sim';
    }
    if ($value === 'n') {
        return 'Não';
    }
    return '';
}

$context = rahExportParam('rah_context', 'auditar');
$exportScope = rahExportParam('export_scope', 'filtered') === 'current_page' ? 'current_page' : 'filtered';
$paginaAtual = max(1, (int)rahExportParam('pag', 1));
$limite = max(1, min(5000, (int)rahExportParam('limite', 10)));

$id_hosp = rahExportParam('id_hosp', '');
$pesquisa_nome = rahExportParam('pesquisa_nome', '');
$pesquisa_pac = rahExportParam('pesquisa_pac', '');
$pesquisa_matricula = rahExportParam('pesquisa_matricula', '');
$senha_fin = rahExportParam('senha_fin', '');
$encerrado_cap = rahExportParam('encerrado_cap', '');
$conta_parada = rahExportParam('conta_parada', '');
$senha_int = rahExportParam('senha_int', '');
$lote = rahExportParam('lote', '');
$idcapeante = rahExportParam('idcapeante', '');
$data_intern_int = rahExportParam('data_intern_int', '');
$data_intern_int_max = rahExportParam('data_intern_int_max', '');
$ordenar = rahExportParam('ordenar', 'id_capeante_desc');

if ($context === 'finalizadas' && $encerrado_cap === '') {
    $encerrado_cap = 's';
}
if ($context === 'senhas' && $senha_fin === '') {
    $senha_fin = 's';
}
if ($context === 'auditar' && $encerrado_cap === '') {
    $encerrado_cap = 'n';
}
if ($data_intern_int !== '' && $data_intern_int_max === '') {
    $data_intern_int_max = date('Y-m-d');
}

$cargoSessao = $_SESSION['cargo'] ?? '';
$nivelSessao = $_SESSION['nivel'] ?? null;
$userId = $_SESSION['id_usuario'] ?? null;
$isDiretor = (stripos((string)$cargoSessao, 'diretor') !== false) || ((string)$nivelSessao === '1');

$condicoes = [
    trim($id_hosp) !== '' ? 'ho.id_hospital = ' . (int)$id_hosp : null,
    trim($pesquisa_nome) !== '' ? 'ho.nome_hosp LIKE "%' . rahExportSqlValue($pesquisa_nome) . '%"' : null,
    trim($pesquisa_pac) !== '' ? 'pa.nome_pac LIKE "%' . rahExportSqlValue($pesquisa_pac) . '%"' : null,
    trim($pesquisa_matricula) !== '' ? 'pa.matricula_pac LIKE "%' . rahExportSqlValue($pesquisa_matricula) . '%"' : null,
    trim($lote) !== '' ? 'ca.lote_cap = "' . rahExportSqlValue($lote) . '"' : null,
    trim($idcapeante) !== '' ? 'ca.id_capeante LIKE "%' . rahExportSqlValue($idcapeante) . '%"' : null,
    trim($senha_fin) !== '' ? 'senha_finalizada = "' . rahExportSqlValue($senha_fin) . '"' : null,
    ($conta_parada === 's' || $conta_parada === 'n') ? 'ca.conta_parada_cap = "' . rahExportSqlValue($conta_parada) . '"' : null,
    ($encerrado_cap === 's' || $encerrado_cap === 'n') ? 'ca.encerrado_cap = "' . rahExportSqlValue($encerrado_cap) . '"' : null,
    trim($senha_int) !== '' ? 'senha_int LIKE "%' . rahExportSqlValue($senha_int) . '%"' : null,
    trim($data_intern_int) !== '' ? 'data_intern_int BETWEEN "' . rahExportSqlValue($data_intern_int) . '" AND "' . rahExportSqlValue($data_intern_int_max) . '"' : null,
    (!$isDiretor && trim((string)$userId) !== '') ? 'ho.fk_usuario_hosp = "' . rahExportSqlValue($userId) . '"' : null,
];
$where = implode(' AND ', array_filter($condicoes));

$mapOrder = [
    'id_internacao' => 'ac.id_internacao',
    'id_internacao_desc' => 'ac.id_internacao DESC',
    'id_capeante_desc' => 'ca.id_capeante DESC',
    'id_capeante' => 'ca.id_capeante',
    'senha_int' => 'ac.senha_int',
    'senha_int_desc' => 'ac.senha_int DESC',
    'nome_pac' => 'pa.nome_pac',
    'nome_pac_desc' => 'pa.nome_pac DESC',
    'nome_hosp' => 'ho.nome_hosp',
    'nome_hosp_desc' => 'ho.nome_hosp DESC',
    'data_intern_int' => 'ac.data_intern_int',
    'data_intern_int_desc' => 'ac.data_intern_int DESC',
];
$order = $mapOrder[$ordenar] ?? 'ca.id_capeante DESC';

$limitExport = null;
if ($exportScope === 'current_page') {
    $offset = ($paginaAtual - 1) * $limite;
    $limitExport = $offset . ',' . $limite;
}

$internacaoDao = new internacaoDAO($conn, $BASE_URL);
$dados = $internacaoDao->selectAllInternacaoCapList($where, $order, $limitExport);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Capeantes');
$sheet->setShowGridlines(false);

$headers = [
    'Reg Int',
    'Capeante',
    'Hospital',
    'Paciente',
    'Senha',
    'Data internação',
    'Data fechamento',
    'Data digitação',
    'Lote',
    'Senha finalizada',
    'Encerrado',
    'Conta parada',
    'Parcial',
    'Valor apresentado',
    'Valor final',
    'Evento adverso',
];

$logoPath = __DIR__ . '/img/LogoFullCare.png';
if (file_exists($logoPath)) {
    $logo = new Drawing();
    $logo->setName('Logo');
    $logo->setDescription('Logo FullCare');
    $logo->setPath($logoPath);
    $logo->setHeight(32);
    $logo->setCoordinates('A2');
    $logo->setWorksheet($sheet);
}

$titleByContext = [
    'auditar' => 'Contas para Auditar',
    'finalizadas' => 'Contas Finalizadas',
    'senhas' => 'Senhas Finalizadas',
];
$title = $titleByContext[$context] ?? 'Capeantes';
$lastCol = Coordinate::stringFromColumnIndex(count($headers));
$sheet->getRowDimension(1)->setRowHeight(28);
$sheet->getRowDimension(2)->setRowHeight(18);
$sheet->setCellValue('D1', $title . ' - Exportação');
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
    $sheet->setCellValue('F' . $row, rahExportDateBr($item['data_intern_int'] ?? ''));
    $sheet->setCellValue('G' . $row, rahExportDateBr($item['data_fech_capeante'] ?? ''));
    $sheet->setCellValue('H' . $row, rahExportDateBr($item['data_digit_capeante'] ?? ''));
    $sheet->setCellValue('I' . $row, $item['lote_cap'] ?? '');
    $sheet->setCellValue('J' . $row, rahExportYesNo($item['senha_finalizada'] ?? ''));
    $sheet->setCellValue('K' . $row, rahExportYesNo($item['encerrado_cap'] ?? ''));
    $sheet->setCellValue('L' . $row, rahExportYesNo($item['conta_parada_cap'] ?? ''));
    $sheet->setCellValue('M' . $row, $item['parcial_num'] ?? '');
    $sheet->setCellValue('N' . $row, (float)($item['valor_apresentado_capeante'] ?? 0));
    $sheet->setCellValue('O' . $row, (float)($item['valor_final_capeante'] ?? 0));
    $sheet->setCellValue('P' . $row, ((int)($item['alerta_evento_adverso_cap'] ?? 0) === 1) ? 'Sim' : 'Não');
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
    $sheet->getStyle('N' . ($headerRow + 1) . ':O' . $lastRow)->getNumberFormat()->setFormatCode('"R$" #,##0.00');
}

foreach (range(1, count($headers)) as $colIndex) {
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$fileName = 'capeantes_' . preg_replace('/[^a-z0-9_-]+/i', '_', $context) . '_' . date('Ymd_His') . '.xlsx';

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
