<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$outRoot = $root . '/app_store_screenshots';
$logoPath = $root . '/assets/branding/fullcare_footer_logo.png';
$font = '/System/Library/Fonts/Supplemental/Arial.ttf';
$fontBold = '/System/Library/Fonts/Supplemental/Arial Bold.ttf';

if (!is_file($font)) {
    $font = '/System/Library/Fonts/Helvetica.ttc';
    $fontBold = $font;
}

function color($img, string $hex, int $alpha = 0): int
{
    $hex = ltrim($hex, '#');
    return imagecolorallocatealpha(
        $img,
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
        $alpha
    );
}

function roundedRect($img, int $x, int $y, int $w, int $h, int $r, int $color): void
{
    imagefilledrectangle($img, $x + $r, $y, $x + $w - $r, $y + $h, $color);
    imagefilledrectangle($img, $x, $y + $r, $x + $w, $y + $h - $r, $color);
    imagefilledellipse($img, $x + $r, $y + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x + $w - $r, $y + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x + $r, $y + $h - $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x + $w - $r, $y + $h - $r, $r * 2, $r * 2, $color);
}

function strokeRoundedRect($img, int $x, int $y, int $w, int $h, int $r, int $color, int $thickness = 2): void
{
    for ($i = 0; $i < $thickness; $i++) {
        $xx = $x + $i;
        $yy = $y + $i;
        $ww = $w - ($i * 2);
        $hh = $h - ($i * 2);
        $rr = max(1, $r - $i);

        imageline($img, $xx + $rr, $yy, $xx + $ww - $rr, $yy, $color);
        imageline($img, $xx + $rr, $yy + $hh, $xx + $ww - $rr, $yy + $hh, $color);
        imageline($img, $xx, $yy + $rr, $xx, $yy + $hh - $rr, $color);
        imageline($img, $xx + $ww, $yy + $rr, $xx + $ww, $yy + $hh - $rr, $color);
        imagearc($img, $xx + $rr, $yy + $rr, $rr * 2, $rr * 2, 180, 270, $color);
        imagearc($img, $xx + $ww - $rr, $yy + $rr, $rr * 2, $rr * 2, 270, 360, $color);
        imagearc($img, $xx + $ww - $rr, $yy + $hh - $rr, $rr * 2, $rr * 2, 0, 90, $color);
        imagearc($img, $xx + $rr, $yy + $hh - $rr, $rr * 2, $rr * 2, 90, 180, $color);
    }
}

function label($img, string $text, int $size, int $x, int $y, int $color, string $font): void
{
    imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
}

function wrappedText($img, string $text, int $size, int $x, int $y, int $maxWidth, int $lineHeight, int $color, string $font): int
{
    $words = preg_split('/\s+/', trim($text)) ?: [];
    $line = '';

    foreach ($words as $word) {
        $candidate = trim($line . ' ' . $word);
        $box = imagettfbbox($size, 0, $font, $candidate);
        $width = $box[2] - $box[0];
        if ($width > $maxWidth && $line !== '') {
            label($img, $line, $size, $x, $y, $color, $font);
            $y += $lineHeight;
            $line = $word;
        } else {
            $line = $candidate;
        }
    }

    if ($line !== '') {
        label($img, $line, $size, $x, $y, $color, $font);
        $y += $lineHeight;
    }

    return $y;
}

function canvas(int $w, int $h, string $bg)
{
    $img = imagecreatetruecolor($w, $h);
    imagesavealpha($img, true);
    imagefilledrectangle($img, 0, 0, $w, $h, color($img, $bg));
    return $img;
}

function pasteLogo($img, string $logoPath, int $x, int $y, int $size): void
{
    if (!is_file($logoPath)) {
        return;
    }

    $logo = imagecreatefrompng($logoPath);
    imagealphablending($img, true);
    imagesavealpha($img, true);
    imagecopyresampled($img, $logo, $x, $y, 0, 0, $size, $size, imagesx($logo), imagesy($logo));
    imagedestroy($logo);
}

function saveImage($img, string $path): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    imagepng($img, $path, 9);
    imagedestroy($img);
}

function uiScale(int $w): float
{
    return $w / 1320;
}

function appHeader($img, int $w, string $title, string $subtitle, string $font, string $bold, string $logoPath): int
{
    $s = uiScale($w);
    $blue = color($img, '#116fb0');
    $ink = color($img, '#203246');
    $muted = color($img, '#667085');
    $white = color($img, '#ffffff');

    $h = (int)(220 * $s);
    imagefilledrectangle($img, 0, 0, $w, $h, $blue);
    pasteLogo($img, $logoPath, (int)(52 * $s), (int)(46 * $s), (int)(92 * $s));
    label($img, $title, (int)(36 * $s), (int)(170 * $s), (int)(92 * $s), $white, $bold);
    label($img, $subtitle, (int)(23 * $s), (int)(170 * $s), (int)(136 * $s), color($img, '#e8f5f9'), $font);

    roundedRect($img, (int)($w - 312 * $s), (int)(68 * $s), (int)(238 * $s), (int)(64 * $s), (int)(24 * $s), color($img, '#ffffff', 112));
    label($img, 'Ambiente seguro', (int)(21 * $s), (int)($w - 274 * $s), (int)(109 * $s), $white, $bold);

    return $h;
}

function card($img, int $x, int $y, int $w, int $h, float $s): void
{
    roundedRect($img, $x, $y, $w, $h, (int)(28 * $s), color($img, '#ffffff'));
    strokeRoundedRect($img, $x, $y, $w, $h, (int)(28 * $s), color($img, '#d8e3f0'), (int)max(1, 2 * $s));
}

function drawLoginScreen($img, int $w, int $h, string $font, string $bold, string $logoPath): void
{
    $s = uiScale($w);
    $blue = color($img, '#116fb0');
    $ink = color($img, '#203246');
    $muted = color($img, '#667085');
    $white = color($img, '#ffffff');
    $panelY = (int)(330 * $s);
    $panelX = (int)(86 * $s);
    $panelW = $w - ($panelX * 2);

    imagefilledrectangle($img, 0, 0, $w, (int)(520 * $s), $blue);
    pasteLogo($img, $logoPath, (int)(($w - 190 * $s) / 2), (int)(114 * $s), (int)(190 * $s));
    card($img, $panelX, $panelY, $panelW, (int)(900 * $s), $s);

    label($img, 'FullCare Audit', (int)(46 * $s), (int)(148 * $s), (int)(470 * $s), $ink, $bold);
    wrappedText($img, 'Acesso seguro para gestao de auditoria e controles operacionais.', (int)(25 * $s), (int)(148 * $s), (int)(530 * $s), $panelW - (int)(124 * $s), (int)(38 * $s), $muted, $font);

    $fieldX = (int)(148 * $s);
    $fieldW = $w - (int)(296 * $s);
    foreach ([['Usuario autorizado', 670], ['Senha', 808]] as $field) {
        roundedRect($img, $fieldX, (int)($field[1] * $s), $fieldW, (int)(90 * $s), (int)(18 * $s), color($img, '#f2f6fc'));
        label($img, $field[0], (int)(24 * $s), $fieldX + (int)(34 * $s), (int)(($field[1] + 57) * $s), color($img, '#4f6175'), $font);
    }

    roundedRect($img, $fieldX, (int)(980 * $s), $fieldW, (int)(96 * $s), (int)(22 * $s), $blue);
    label($img, 'Entrar', (int)(29 * $s), (int)($w / 2 - 48 * $s), (int)(1040 * $s), $white, $bold);
    label($img, 'Politica de Privacidade', (int)(22 * $s), (int)($w / 2 - 132 * $s), (int)(1150 * $s), $blue, $font);

    label($img, 'Acesso restrito a usuarios cadastrados', (int)(27 * $s), (int)(104 * $s), $h - (int)(154 * $s), $ink, $bold);
    wrappedText($img, 'O aplicativo protege informacoes operacionais e assistenciais com login individual e sessao autenticada.', (int)(22 * $s), (int)(104 * $s), $h - (int)(106 * $s), $w - (int)(208 * $s), (int)(34 * $s), $muted, $font);
}

function drawHubScreen($img, int $w, int $h, string $font, string $bold, string $logoPath): void
{
    $s = uiScale($w);
    $top = appHeader($img, $w, 'FullCare Audit', 'Hub operacional', $font, $bold, $logoPath);
    $ink = color($img, '#203246');
    $muted = color($img, '#667085');
    $blue = color($img, '#116fb0');
    $purple = color($img, '#5e2363');

    $x = (int)(64 * $s);
    $y = $top + (int)(54 * $s);
    card($img, $x, $y, $w - (int)(128 * $s), (int)(260 * $s), $s);
    label($img, 'Painel do auditor', (int)(34 * $s), $x + (int)(40 * $s), $y + (int)(74 * $s), $ink, $bold);
    wrappedText($img, 'Acompanhe internacoes, registros assistenciais e atividades de conformidade em um fluxo unico.', (int)(24 * $s), $x + (int)(40 * $s), $y + (int)(124 * $s), $w - (int)(208 * $s), (int)(36 * $s), $muted, $font);

    $cards = [
        ['Internacoes', 'Fila operacional e detalhes do atendimento', '#116fb0'],
        ['TUSS', 'Registro de itens e quantidades liberadas', '#25b8d6'],
        ['Prorrogacoes', 'Acompanhamento de diarias e periodos', '#5e2363'],
        ['Altas', 'Lancamentos e tipos de alta hospitalar', '#4c7c2d'],
        ['Evolucao', 'Historico e anotacoes do caso', '#bd5b00'],
        ['Home Care', 'Fila de continuidade assistencial', '#335c81'],
    ];

    $gridY = $y + (int)(330 * $s);
    $gap = (int)(26 * $s);
    $cardW = (int)(($w - 128 * $s - $gap) / 2);
    $cardH = (int)(220 * $s);
    foreach ($cards as $i => $item) {
        $col = $i % 2;
        $row = intdiv($i, 2);
        $cx = $x + $col * ($cardW + $gap);
        $cy = $gridY + $row * ($cardH + $gap);
        card($img, $cx, $cy, $cardW, $cardH, $s);
        imagefilledellipse($img, $cx + (int)(58 * $s), $cy + (int)(72 * $s), (int)(54 * $s), (int)(54 * $s), color($img, $item[2]));
        label($img, $item[0], (int)(27 * $s), $cx + (int)(100 * $s), $cy + (int)(70 * $s), $ink, $bold);
        wrappedText($img, $item[1], (int)(21 * $s), $cx + (int)(100 * $s), $cy + (int)(110 * $s), $cardW - (int)(132 * $s), (int)(31 * $s), $muted, $font);
    }

    roundedRect($img, $x, $h - (int)(240 * $s), $w - (int)(128 * $s), (int)(144 * $s), (int)(26 * $s), color($img, '#e8f5f9'));
    label($img, 'Controles em tempo real', (int)(29 * $s), $x + (int)(36 * $s), $h - (int)(176 * $s), $ink, $bold);
    label($img, 'Auditoria, evidencias e indicadores com acesso por perfil.', (int)(22 * $s), $x + (int)(36 * $s), $h - (int)(132 * $s), $muted, $font);
    imagefilledellipse($img, $w - (int)(160 * $s), $h - (int)(168 * $s), (int)(70 * $s), (int)(70 * $s), $purple);
}

function drawAdmissionsScreen($img, int $w, int $h, string $font, string $bold, string $logoPath): void
{
    $s = uiScale($w);
    $top = appHeader($img, $w, 'Internacoes', 'Acompanhamento operacional', $font, $bold, $logoPath);
    $ink = color($img, '#203246');
    $muted = color($img, '#667085');
    $blue = color($img, '#116fb0');

    $x = (int)(64 * $s);
    $searchY = $top + (int)(54 * $s);
    roundedRect($img, $x, $searchY, $w - (int)(128 * $s), (int)(92 * $s), (int)(24 * $s), color($img, '#ffffff'));
    strokeRoundedRect($img, $x, $searchY, $w - (int)(128 * $s), (int)(92 * $s), (int)(24 * $s), color($img, '#d8e3f0'), (int)max(1, 2 * $s));
    label($img, 'Pesquisar beneficiario, prestador ou convenio', (int)(23 * $s), $x + (int)(36 * $s), $searchY + (int)(58 * $s), $muted, $font);
    label($img, 'Total de registros: 50', (int)(24 * $s), $x, $searchY + (int)(152 * $s), $blue, $bold);

    $rows = [
        ['Paciente Demo 01', 'Prestador: Hospital Demonstracao', 'Convenio: Operadora Exemplo', 'Status: em analise'],
        ['Paciente Demo 02', 'Prestador: Rede Clinica Sul', 'Convenio: Plano Exemplo', 'Status: prorrogacao'],
        ['Paciente Demo 03', 'Prestador: Centro Medico Norte', 'Convenio: Saude Exemplo', 'Status: alta prevista'],
        ['Paciente Demo 04', 'Prestador: Hospital Geral Demo', 'Convenio: Operadora Exemplo', 'Status: acompanhamento'],
    ];

    $rowY = $searchY + (int)(210 * $s);
    $rowH = (int)(248 * $s);
    foreach ($rows as $i => $row) {
        card($img, $x, $rowY, $w - (int)(128 * $s), $rowH, $s);
        imagefilledellipse($img, $x + (int)(58 * $s), $rowY + (int)(70 * $s), (int)(50 * $s), (int)(50 * $s), color($img, $i % 2 === 0 ? '#25b8d6' : '#5e2363'));
        label($img, $row[0], (int)(27 * $s), $x + (int)(104 * $s), $rowY + (int)(62 * $s), $ink, $bold);
        label($img, $row[1], (int)(22 * $s), $x + (int)(104 * $s), $rowY + (int)(106 * $s), $muted, $font);
        label($img, $row[2], (int)(22 * $s), $x + (int)(104 * $s), $rowY + (int)(146 * $s), $muted, $font);
        label($img, $row[3], (int)(22 * $s), $x + (int)(104 * $s), $rowY + (int)(186 * $s), $blue, $bold);
        label($img, '>', (int)(36 * $s), $w - (int)(118 * $s), $rowY + (int)(134 * $s), color($img, '#4f6175'), $bold);
        $rowY += $rowH + (int)(28 * $s);
    }
}

function drawRecordsScreen($img, int $w, int $h, string $font, string $bold, string $logoPath): void
{
    $s = uiScale($w);
    $top = appHeader($img, $w, 'Registros', 'TUSS, prorrogacao e alta', $font, $bold, $logoPath);
    $ink = color($img, '#203246');
    $muted = color($img, '#667085');
    $blue = color($img, '#116fb0');
    $green = color($img, '#4c7c2d');
    $purple = color($img, '#5e2363');

    $x = (int)(64 * $s);
    $y = $top + (int)(54 * $s);
    card($img, $x, $y, $w - (int)(128 * $s), (int)(300 * $s), $s);
    label($img, 'Paciente Demo 01', (int)(34 * $s), $x + (int)(40 * $s), $y + (int)(76 * $s), $ink, $bold);
    label($img, 'Hospital Demonstracao - Operadora Exemplo', (int)(23 * $s), $x + (int)(40 * $s), $y + (int)(122 * $s), $muted, $font);
    label($img, 'Senha: 123456', (int)(23 * $s), $x + (int)(40 * $s), $y + (int)(168 * $s), $blue, $bold);
    roundedRect($img, $x + (int)(40 * $s), $y + (int)(208 * $s), (int)(260 * $s), (int)(58 * $s), (int)(20 * $s), color($img, '#e8f5f9'));
    label($img, 'Em acompanhamento', (int)(20 * $s), $x + (int)(68 * $s), $y + (int)(246 * $s), $blue, $bold);

    $blocks = [
        ['TUSS', 'Codigo 10101012', 'Qtd solicitada 2 / liberada 2', $blue],
        ['Prorrogacao', 'Apartamento - 3 diarias', 'Periodo 15/06 a 18/06', $purple],
        ['Alta', 'Tipo: melhorado', 'Registro preparado para conclusao', $green],
        ['Evolucao', 'Relato assistencial salvo', 'Historico disponivel no caso', color($img, '#bd5b00')],
    ];

    $by = $y + (int)(370 * $s);
    foreach ($blocks as $item) {
        card($img, $x, $by, $w - (int)(128 * $s), (int)(210 * $s), $s);
        imagefilledellipse($img, $x + (int)(64 * $s), $by + (int)(82 * $s), (int)(60 * $s), (int)(60 * $s), $item[3]);
        label($img, $item[0], (int)(29 * $s), $x + (int)(120 * $s), $by + (int)(70 * $s), $ink, $bold);
        label($img, $item[1], (int)(23 * $s), $x + (int)(120 * $s), $by + (int)(112 * $s), $muted, $font);
        label($img, $item[2], (int)(23 * $s), $x + (int)(120 * $s), $by + (int)(152 * $s), $muted, $font);
        $by += (int)(238 * $s);
    }
}

function createSet(string $dir, int $w, int $h, string $font, string $bold, string $logoPath): void
{
    $screens = [
        '01-acesso-restrito.png' => 'drawLoginScreen',
        '02-operacao-e-auditoria.png' => 'drawHubScreen',
        '03-registros.png' => 'drawAdmissionsScreen',
        '04-conformidade.png' => 'drawRecordsScreen',
    ];

    foreach ($screens as $file => $fn) {
        $img = canvas($w, $h, '#f2f6fc');
        $fn($img, $w, $h, $font, $bold, $logoPath);
        saveImage($img, $dir . '/' . $file);
    }
}

createSet($outRoot . '/iphone-6-9', 1320, 2868, $font, $fontBold, $logoPath);
createSet($outRoot . '/iphone-6-5', 1242, 2688, $font, $fontBold, $logoPath);
createSet($outRoot . '/ipad-13', 2048, 2732, $font, $fontBold, $logoPath);

copy($outRoot . '/ipad-13/01-acesso-restrito.png', $outRoot . '/ipad-13/01-ipad-acesso-restrito.png');
copy($outRoot . '/ipad-13/02-operacao-e-auditoria.png', $outRoot . '/ipad-13/02-ipad-operacao-e-auditoria.png');
copy($outRoot . '/ipad-13/03-registros.png', $outRoot . '/ipad-13/03-ipad-registros.png');
copy($outRoot . '/ipad-13/04-conformidade.png', $outRoot . '/ipad-13/04-ipad-conformidade.png');
@unlink($outRoot . '/ipad-13/01-acesso-restrito.png');
@unlink($outRoot . '/ipad-13/02-operacao-e-auditoria.png');
@unlink($outRoot . '/ipad-13/03-registros.png');
@unlink($outRoot . '/ipad-13/04-conformidade.png');

echo "App Store screenshots created in {$outRoot}\n";
