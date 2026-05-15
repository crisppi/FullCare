<?php

$out = __DIR__ . '/../img/producao_preview.png';

$w = 1640;
$h = 820;
$img = imagecreatetruecolor($w, $h);
imagesavealpha($img, true);
imagealphablending($img, false);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);
imagealphablending($img, true);

$fontDir = __DIR__ . '/../diversos/CoolAdmin-master/fonts/poppins';
$fontRegular = $fontDir . '/poppins-v5-latin-regular.ttf';
$fontSemi = $fontDir . '/poppins-v5-latin-600.ttf';
$fontBold = $fontDir . '/poppins-v5-latin-700.ttf';
foreach ([$fontRegular, $fontSemi, $fontBold] as $font) {
    if (!is_file($font)) {
        $fontRegular = $fontSemi = $fontBold = null;
        break;
    }
}

function rgba($im, int $r, int $g, int $b, int $a = 0): int
{
    return imagecolorallocatealpha($im, $r, $g, $b, $a);
}

function roundedRect($im, int $x, int $y, int $w, int $h, int $r, int $color): void
{
    imagefilledrectangle($im, $x + $r, $y, $x + $w - $r, $y + $h, $color);
    imagefilledrectangle($im, $x, $y + $r, $x + $w, $y + $h - $r, $color);
    imagefilledellipse($im, $x + $r, $y + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($im, $x + $w - $r, $y + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($im, $x + $r, $y + $h - $r, $r * 2, $r * 2, $color);
    imagefilledellipse($im, $x + $w - $r, $y + $h - $r, $r * 2, $r * 2, $color);
}

function strokeRoundedRect($im, int $x, int $y, int $w, int $h, int $r, int $color, int $thickness = 1): void
{
    imagesetthickness($im, $thickness);
    imageline($im, $x + $r, $y, $x + $w - $r, $y, $color);
    imageline($im, $x + $r, $y + $h, $x + $w - $r, $y + $h, $color);
    imageline($im, $x, $y + $r, $x, $y + $h - $r, $color);
    imageline($im, $x + $w, $y + $r, $x + $w, $y + $h - $r, $color);
    imagearc($im, $x + $r, $y + $r, $r * 2, $r * 2, 180, 270, $color);
    imagearc($im, $x + $w - $r, $y + $r, $r * 2, $r * 2, 270, 360, $color);
    imagearc($im, $x + $w - $r, $y + $h - $r, $r * 2, $r * 2, 0, 90, $color);
    imagearc($im, $x + $r, $y + $h - $r, $r * 2, $r * 2, 90, 180, $color);
    imagesetthickness($im, 1);
}

function textOrBar($im, ?string $font, int $size, int $x, int $y, string $text, int $color, int $barW = 80): void
{
    if ($font) {
        imagettftext($im, $size, 0, $x, $y, $color, $font, $text);
        return;
    }
    roundedRect($im, $x, $y - 12, $barW, 8, 4, $color);
}

function linePath($im, array $points, int $color, int $thickness = 8): void
{
    imagesetthickness($im, $thickness);
    for ($i = 1, $n = count($points); $i < $n; $i++) {
        imageline($im, $points[$i - 1][0], $points[$i - 1][1], $points[$i][0], $points[$i][1], $color);
    }
    imagesetthickness($im, 1);
}

function makeShadow(int $w, int $h, int $x, int $y, int $rw, int $rh, int $r, int $alpha)
{
    $shadow = imagecreatetruecolor($w, $h);
    imagesavealpha($shadow, true);
    imagealphablending($shadow, false);
    imagefill($shadow, 0, 0, imagecolorallocatealpha($shadow, 0, 0, 0, 127));
    imagealphablending($shadow, true);
    roundedRect($shadow, $x, $y, $rw, $rh, $r, imagecolorallocatealpha($shadow, 36, 20, 49, $alpha));
    for ($i = 0; $i < 12; $i++) {
        imagefilter($shadow, IMG_FILTER_GAUSSIAN_BLUR);
    }
    return $shadow;
}

// Notebook body and screen.
imagecopy($img, makeShadow($w, $h, 205, 105, 1230, 520, 52, 72), 0, 0, 0, 0, $w, $h);
roundedRect($img, 208, 96, 1224, 572, 50, rgba($img, 19, 24, 39));
roundedRect($img, 236, 124, 1168, 516, 28, rgba($img, 248, 252, 255));
roundedRect($img, 236, 124, 1168, 74, 28, rgba($img, 66, 24, 73));
imagefilledrectangle($img, 236, 175, 1404, 204, rgba($img, 92, 42, 112));

foreach ([[288, 162, 121, 199, 255], [326, 162, 255, 198, 108], [364, 162, 111, 223, 194]] as $dot) {
    imagefilledellipse($img, $dot[0], $dot[1], 20, 20, rgba($img, $dot[2], $dot[3], $dot[4]));
}
textOrBar($img, $fontBold, 24, 420, 171, 'Dashboard Produção', rgba($img, 255, 255, 255), 260);
textOrBar($img, $fontSemi, 14, 1192, 171, 'FULLCARE BI', rgba($img, 239, 228, 243), 150);

// Filters row.
roundedRect($img, 280, 248, 1080, 88, 24, rgba($img, 255, 255, 255));
strokeRoundedRect($img, 280, 248, 1080, 88, 24, rgba($img, 220, 229, 238), 2);
foreach ([[316, 274, 152], [498, 274, 184], [712, 274, 176], [918, 274, 204]] as $pill) {
    roundedRect($img, $pill[0], $pill[1], $pill[2], 34, 15, rgba($img, 238, 243, 248));
}
roundedRect($img, 1188, 266, 128, 50, 15, rgba($img, 66, 24, 73));
textOrBar($img, $fontSemi, 13, 350, 296, 'Internado', rgba($img, 81, 95, 115), 72);
textOrBar($img, $fontSemi, 13, 538, 296, 'Hospitais', rgba($img, 81, 95, 115), 82);
textOrBar($img, $fontSemi, 13, 756, 296, 'Periodo', rgba($img, 81, 95, 115), 70);
textOrBar($img, $fontSemi, 13, 980, 296, 'Todos', rgba($img, 81, 95, 115), 56);
textOrBar($img, $fontBold, 13, 1228, 297, 'Aplicar', rgba($img, 255, 255, 255), 58);

// Chart cards.
roundedRect($img, 280, 380, 500, 184, 28, rgba($img, 255, 255, 255));
strokeRoundedRect($img, 280, 380, 500, 184, 28, rgba($img, 220, 229, 238), 2);
textOrBar($img, $fontBold, 20, 320, 426, 'Internações', rgba($img, 37, 48, 68), 160);
textOrBar($img, $fontRegular, 12, 322, 450, 'Evolução mensal', rgba($img, 104, 117, 137), 120);
imagefilledrectangle($img, 320, 486, 720, 540, rgba($img, 121, 199, 255, 112));
linePath($img, [[324, 534], [374, 506], [434, 518], [500, 490], [590, 502], [682, 458], [726, 476]], rgba($img, 121, 199, 255), 9);
linePath($img, [[334, 500], [360, 482], [386, 494], [418, 466]], rgba($img, 111, 223, 194), 7);

roundedRect($img, 860, 380, 500, 184, 28, rgba($img, 255, 255, 255));
strokeRoundedRect($img, 860, 380, 500, 184, 28, rgba($img, 220, 229, 238), 2);
textOrBar($img, $fontBold, 20, 900, 426, 'Valor final', rgba($img, 37, 48, 68), 140);
textOrBar($img, $fontRegular, 12, 902, 450, 'Produção acumulada', rgba($img, 104, 117, 137), 140);
linePath($img, [[904, 498], [982, 476], [1060, 484], [1130, 462], [1220, 470], [1300, 452]], rgba($img, 255, 198, 108), 7);
foreach ([[914, 508, 36, 38], [980, 486, 36, 60], [1046, 470, 36, 76], [1112, 500, 36, 46], [1178, 462, 36, 84], [1244, 482, 36, 64]] as $bar) {
    roundedRect($img, $bar[0], $bar[1], $bar[2], $bar[3], 8, rgba($img, 141, 208, 255));
}

// Bottom screen shine and notebook base.
imagefilledrectangle($img, 236, 612, 1404, 640, rgba($img, 237, 244, 251));
roundedRect($img, 208, 668, 1224, 22, 11, rgba($img, 207, 213, 223, 24));
$base = [
    208, 668,
    1432, 668,
    1372, 706,
    268, 706,
];
imagefilledpolygon($img, $base, 4, rgba($img, 192, 199, 211, 26));
roundedRect($img, 260, 704, 1120, 18, 9, rgba($img, 137, 147, 161, 86));
roundedRect($img, 716, 672, 208, 18, 9, rgba($img, 171, 180, 191, 44));

imagepng($img, $out, 9);
imagedestroy($img);

echo $out . PHP_EOL;
