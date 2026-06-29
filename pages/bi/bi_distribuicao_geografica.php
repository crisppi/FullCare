<?php
include_once("check_logado.php");
require_once("templates/header.php");

if (!isset($conn) || !($conn instanceof PDO)) {
    die("Conexao invalida.");
}

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function brStateCode($value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $ascii = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : $value;
    $key = strtoupper(preg_replace('/[^A-Z]/', '', $ascii !== false ? $ascii : $value));
    $states = [
        'AC' => 'AC', 'ACRE' => 'AC',
        'AL' => 'AL', 'ALAGOAS' => 'AL',
        'AP' => 'AP', 'AMAPA' => 'AP',
        'AM' => 'AM', 'AMAZONAS' => 'AM',
        'BA' => 'BA', 'BAHIA' => 'BA',
        'CE' => 'CE', 'CEARA' => 'CE',
        'DF' => 'DF', 'DISTRITOFEDERAL' => 'DF',
        'ES' => 'ES', 'ESPIRITOSANTO' => 'ES',
        'GO' => 'GO', 'GOIAS' => 'GO',
        'MA' => 'MA', 'MARANHAO' => 'MA',
        'MT' => 'MT', 'MATOGROSSO' => 'MT',
        'MS' => 'MS', 'MATOGROSSODOSUL' => 'MS',
        'MG' => 'MG', 'MINASGERAIS' => 'MG',
        'PA' => 'PA', 'PARA' => 'PA',
        'PB' => 'PB', 'PARAIBA' => 'PB',
        'PR' => 'PR', 'PARANA' => 'PR',
        'PE' => 'PE', 'PERNAMBUCO' => 'PE',
        'PI' => 'PI', 'PIAUI' => 'PI',
        'RJ' => 'RJ', 'RIODEJANEIRO' => 'RJ',
        'RN' => 'RN', 'RIOGRANDEDONORTE' => 'RN',
        'RS' => 'RS', 'RIOGRANDEDOSUL' => 'RS',
        'RO' => 'RO', 'RONDONIA' => 'RO',
        'RR' => 'RR', 'RORAIMA' => 'RR',
        'SC' => 'SC', 'SANTACATARINA' => 'SC',
        'SP' => 'SP', 'SAOPAULO' => 'SP',
        'SE' => 'SE', 'SERGIPE' => 'SE',
        'TO' => 'TO', 'TOCANTINS' => 'TO',
    ];

    return $states[$key] ?? '';
}

function parseCoordinate($value): ?float
{
    $value = trim(str_replace(',', '.', (string)$value));
    if ($value === '' || !is_numeric($value)) {
        return null;
    }
    return (float)$value;
}

$internado = trim((string)(filter_input(INPUT_GET, 'internado') ?? ''));
$hospitalId = filter_input(INPUT_GET, 'hospital_id', FILTER_VALIDATE_INT) ?: null;
$mesInput = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT);
$anoInput = filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT);
$mes = $mesInput ? (int)$mesInput : null;
$ano = $anoInput ? (int)$anoInput : null;

$hospitais = $conn->query("SELECT id_hospital, nome_hosp FROM tb_hospital ORDER BY nome_hosp")
    ->fetchAll(PDO::FETCH_ASSOC);
$anos = $conn->query("SELECT DISTINCT YEAR(data_intern_int) AS ano FROM tb_internacao WHERE data_intern_int IS NOT NULL AND data_intern_int <> '0000-00-00' ORDER BY ano DESC")
    ->fetchAll(PDO::FETCH_COLUMN);

$where = "i.data_intern_int IS NOT NULL";
$params = [];
if ($ano) {
    $where .= " AND YEAR(i.data_intern_int) = :ano";
    $params[':ano'] = $ano;
}
if ($mes) {
    $where .= " AND MONTH(i.data_intern_int) = :mes";
    $params[':mes'] = $mes;
}
if ($internado === 's' || $internado === 'n') {
    $where .= " AND i.internado_int = :internado";
    $params[':internado'] = $internado;
}
if ($hospitalId) {
    $where .= " AND i.fk_hospital_int = :hospital_id";
    $params[':hospital_id'] = $hospitalId;
}

$sql = "
    SELECT
        h.id_hospital,
        COALESCE(h.nome_hosp, 'Sem informações') AS label,
        h.estado_hosp,
        h.latitude_hosp,
        h.longitude_hosp,
        COUNT(DISTINCT i.id_internacao) AS internacoes,
        SUM(GREATEST(1, DATEDIFF(COALESCE(al.data_alta_alt, CURDATE()), i.data_intern_int) + 1)) AS total_diarias,
        SUM(COALESCE(ca.valor_final, 0)) AS custo_total,
        SUM(CASE WHEN ut.fk_internacao_uti IS NOT NULL THEN 1 ELSE 0 END) AS internacoes_uti,
        SUM(
            CASE WHEN al.data_alta_alt IS NOT NULL AND EXISTS (
                SELECT 1
                FROM tb_internacao i2
                WHERE i2.fk_paciente_int = i.fk_paciente_int
                  AND i2.data_intern_int > al.data_alta_alt
                  AND i2.data_intern_int <= DATE_ADD(al.data_alta_alt, INTERVAL 30 DAY)
            ) THEN 1 ELSE 0 END
        ) AS reinternacoes
    FROM tb_internacao i
    LEFT JOIN tb_hospital h ON h.id_hospital = i.fk_hospital_int
    LEFT JOIN (
        SELECT fk_id_int_alt, MAX(data_alta_alt) AS data_alta_alt
        FROM tb_alta
        GROUP BY fk_id_int_alt
    ) al ON al.fk_id_int_alt = i.id_internacao
    LEFT JOIN (
        SELECT fk_int_capeante, SUM(COALESCE(valor_final_capeante, valor_apresentado_capeante, 0)) AS valor_final
        FROM tb_capeante
        GROUP BY fk_int_capeante
    ) ca ON ca.fk_int_capeante = i.id_internacao
    LEFT JOIN (SELECT DISTINCT fk_internacao_uti FROM tb_uti) ut ON ut.fk_internacao_uti = i.id_internacao
    WHERE {$where}
    GROUP BY h.id_hospital, h.nome_hosp, h.estado_hosp, h.latitude_hosp, h.longitude_hosp
    ORDER BY custo_total DESC
    LIMIT 30
";
$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

foreach ($rows as &$row) {
    $internacoesRow = (int)($row['internacoes'] ?? 0);
    $diariasRow = (float)($row['total_diarias'] ?? 0);
    $internacoesUtiRow = (int)($row['internacoes_uti'] ?? 0);
    $custoTotalRow = (float)($row['custo_total'] ?? 0);
    $row['mp'] = $internacoesRow > 0 ? $diariasRow / $internacoesRow : 0;
    $row['pct_uti'] = $internacoesRow > 0 ? ($internacoesUtiRow / $internacoesRow) * 100 : 0;
    $row['custo_diaria'] = $diariasRow > 0 ? $custoTotalRow / $diariasRow : 0;
}
unset($row);

$stateCentroids = [
    'AC' => [-9.02, -70.81], 'AL' => [-9.57, -36.78], 'AP' => [1.41, -51.77],
    'AM' => [-3.47, -65.1], 'BA' => [-12.96, -41.7], 'CE' => [-5.2, -39.53],
    'DF' => [-15.78, -47.93], 'ES' => [-19.19, -40.34], 'GO' => [-15.98, -49.86],
    'MA' => [-5.42, -45.44], 'MT' => [-12.64, -55.42], 'MS' => [-20.51, -54.54],
    'MG' => [-18.1, -44.38], 'PA' => [-3.79, -52.48], 'PB' => [-7.28, -36.72],
    'PR' => [-24.89, -51.55], 'PE' => [-8.38, -37.86], 'PI' => [-7.72, -42.73],
    'RJ' => [-22.25, -42.66], 'RN' => [-5.81, -36.59], 'RS' => [-30.17, -53.5],
    'RO' => [-10.83, -63.34], 'RR' => [1.99, -61.33], 'SC' => [-27.45, -50.95],
    'SP' => [-22.19, -48.79], 'SE' => [-10.57, -37.45], 'TO' => [-10.25, -48.25],
];
$mapData = array_values(array_filter(array_map(static function ($row) use ($stateCentroids): ?array {
    $internacoes = (int)($row['internacoes'] ?? 0);
    if ($internacoes <= 0) {
        return null;
    }

    $lat = parseCoordinate($row['latitude_hosp'] ?? null);
    $lng = parseCoordinate($row['longitude_hosp'] ?? null);
    $source = 'Coordenada do hospital';
    $uf = brStateCode($row['estado_hosp'] ?? '');

    if (
        $lat === null || $lng === null ||
        $lat < -35 || $lat > 7 ||
        $lng < -75 || $lng > -30
    ) {
        if ($uf === '' || !isset($stateCentroids[$uf])) {
            return null;
        }
        [$lat, $lng] = $stateCentroids[$uf];
        $source = 'Centro da UF';
    }

    return [
        'lat' => round($lat, 6),
        'lng' => round($lng, 6),
        'hospital' => (string)($row['label'] ?? 'Sem informações'),
        'uf' => $uf,
        'source' => $source,
        'internacoes' => $internacoes,
        'mp' => round((float)($row['mp'] ?? 0), 2),
        'custo' => round((float)($row['custo_total'] ?? 0), 2),
    ];
}, $rows)));
?>

<link rel="stylesheet" href="<?= $BASE_URL ?>css/bi.css?v=20260628-select-arrow">
<script src="<?= $BASE_URL ?>js/bi.js?v=20260614-select-neutral"></script>
<script>document.addEventListener('DOMContentLoaded', () => document.body.classList.add('bi-theme'));</script>
<style>
    .hospital-geo-page .hospital-geo-map {
        height: min(72vh, 820px);
        min-height: 620px;
    }

    .hospital-map-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }

    .hospital-map-head h3 {
        margin: 0;
    }

    .hospital-map-modes {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px;
        border-radius: 10px;
        background: rgba(15, 23, 42, .1);
        border: 1px solid rgba(255, 255, 255, .12);
    }

    .hospital-map-mode {
        min-height: 30px;
        padding: 4px 10px;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: inherit;
        font-size: .74rem;
        font-weight: 700;
        cursor: pointer;
    }

    .hospital-map-mode.is-active {
        background: rgba(51, 204, 191, .24);
        color: #eaffff;
        box-shadow: inset 0 0 0 1px rgba(51, 204, 191, .36);
    }

    .hospital-geo-map {
        position: relative;
        width: 100%;
        height: min(74vh, 760px);
        min-height: 620px;
        margin: 0;
        overflow: hidden;
        border-radius: 12px;
        background: #b8d1ec;
        border: 1px solid rgba(255, 255, 255, .12);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .06);
        cursor: grab;
        touch-action: none;
    }

    .hospital-geo-map.is-dragging {
        cursor: grabbing;
    }

    .hospital-geo-map__tile,
    .hospital-geo-map__bubble,
    .hospital-geo-map__label,
    .hospital-geo-map__empty,
    .hospital-geo-map__attribution,
    .hospital-geo-map__controls {
        position: absolute;
    }

    .hospital-geo-map__tile {
        user-select: none;
        pointer-events: none;
    }

    .hospital-geo-map__bubble {
        transform: translate(-50%, -50%);
        border-radius: 999px;
        background: rgba(37, 99, 235, .74);
        border: 3px solid rgba(15, 43, 130, .98);
        box-shadow: 0 0 0 4px rgba(255, 255, 255, .92), 0 10px 24px rgba(15, 23, 42, .38), inset 0 1px 0 rgba(255, 255, 255, .5);
        cursor: pointer;
    }

    .hospital-geo-map__bubble:hover,
    .hospital-geo-map__bubble:focus {
        background: rgba(225, 29, 72, .82);
        border-color: rgba(126, 7, 43, .98);
        outline: 3px solid rgba(255, 255, 255, .95);
        z-index: 8;
    }

    .hospital-geo-map__label {
        transform: translate(0, -50%);
        z-index: 7;
        max-width: 210px;
        padding: 5px 8px;
        border-radius: 8px;
        background: rgba(255, 255, 255, .94);
        border: 1px solid rgba(15, 43, 130, .32);
        color: #12213a;
        font-size: .68rem;
        font-weight: 800;
        line-height: 1.16;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .2);
        pointer-events: none;
        white-space: normal;
        opacity: 0;
        visibility: hidden;
        transition: opacity .12s ease, visibility .12s ease;
    }

    .hospital-geo-map__label span {
        display: block;
        margin-top: 2px;
        color: #1d4ed8;
        font-size: .64rem;
        font-weight: 900;
    }

    .hospital-geo-map__label.is-fixed,
    .hospital-geo-map__bubble:hover + .hospital-geo-map__label,
    .hospital-geo-map__bubble:focus + .hospital-geo-map__label {
        opacity: 1;
        visibility: visible;
    }

    .hospital-geo-map__empty {
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #24445b;
        font-size: .82rem;
        font-weight: 700;
        background: rgba(255, 255, 255, .68);
        z-index: 5;
    }

    .hospital-geo-map__attribution {
        right: 8px;
        bottom: 6px;
        z-index: 6;
        padding: 2px 6px;
        border-radius: 6px;
        background: rgba(255, 255, 255, .82);
        color: #24445b;
        font-size: .62rem;
    }

    .hospital-geo-map__attribution a {
        color: #1d5d9b;
        text-decoration: none;
    }

    .hospital-geo-map__controls {
        top: 10px;
        right: 10px;
        z-index: 10;
        display: grid;
        gap: 6px;
    }

    .hospital-geo-map__control {
        width: 32px;
        height: 32px;
        border: 0;
        border-radius: 8px;
        background: rgba(255, 255, 255, .9);
        color: #18364d;
        font-size: 1rem;
        font-weight: 800;
        line-height: 1;
        box-shadow: 0 6px 16px rgba(15, 23, 42, .18);
        cursor: pointer;
    }

    .hospital-geo-map__control:hover,
    .hospital-geo-map__control:focus {
        background: #ffffff;
        outline: 2px solid rgba(35, 102, 147, .24);
    }

    @media (max-width: 640px) {
        .hospital-map-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .hospital-geo-map {
            width: 100%;
            height: 520px;
            min-height: 520px;
        }
    }
</style>

<div class="bi-wrapper bi-theme bi-ie-page hospital-geo-page">
    <div class="bi-header">
        <div>
            <h1 class="bi-title">Distribuição geográfica</h1>
            <div style="color: var(--bi-muted); font-size: 0.95rem;">Mapa de hospitais por internações, MP ou custo.</div>
        </div>
        <div class="bi-header-actions">
            <a class="bi-nav-icon" href="<?= $BASE_URL ?>bi/navegacao" title="Navegação BI">
                <i class="bi bi-grid-3x3-gap"></i>
            </a>
        </div>
    </div>

    <form method="get">
        <div class="bi-panel bi-filters bi-filters-wrap bi-filters-compact">
            <div class="bi-filter">
                <label>Internados</label>
                <select name="internado">
                    <option value="" <?= $internado === '' ? 'selected' : '' ?>>Todos</option>
                    <option value="s" <?= $internado === 's' ? 'selected' : '' ?>>Sim</option>
                    <option value="n" <?= $internado === 'n' ? 'selected' : '' ?>>Não</option>
                </select>
            </div>
            <div class="bi-filter">
                <label>Hospitais</label>
                <select name="hospital_id">
                    <option value="">Todos</option>
                    <?php foreach ($hospitais as $h): ?>
                        <option value="<?= (int)$h['id_hospital'] ?>" <?= $hospitalId == $h['id_hospital'] ? 'selected' : '' ?>>
                            <?= e($h['nome_hosp']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="bi-filter">
                <label>Mês</label>
                <select name="mes">
                    <option value="">Todos</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $mes == $m ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="bi-filter">
                <label>Ano</label>
                <select name="ano">
                    <option value="">Todos</option>
                    <?php foreach ($anos as $anoOpt): ?>
                        <option value="<?= (int)$anoOpt ?>" <?= $ano == $anoOpt ? 'selected' : '' ?>>
                            <?= (int)$anoOpt ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="bi-actions">
                <button class="bi-btn" type="submit">Aplicar</button>
                <a class="bi-btn bi-btn-secondary" href="<?= $BASE_URL ?>bi/distribuicao-geografica">Limpar</a>
            </div>
        </div>
    </form>

    <div class="bi-panel">
        <div class="hospital-map-head">
            <h3>Mapa por internações, MP ou custo</h3>
            <div class="hospital-map-modes" aria-label="Métrica do mapa">
                <button type="button" class="hospital-map-mode is-active" data-map-metric="internacoes">Internações</button>
                <button type="button" class="hospital-map-mode" data-map-metric="mp">MP</button>
                <button type="button" class="hospital-map-mode" data-map-metric="custo">Custo</button>
            </div>
        </div>
        <div id="hospitalGeoMap" class="hospital-geo-map" aria-label="Mapa de internações por hospital"></div>
    </div>
</div>

<script>
const mapaHospitais = <?= json_encode($mapData, JSON_UNESCAPED_UNICODE) ?>;
const hospitalMapInitialCenter = { lat: -14.2, lng: -53.2 };
const hospitalMapMinZoom = 4;
const hospitalMapMaxZoom = 17;
let hospitalMapZoom = hospitalMapMinZoom;
let hospitalMapCenter = { ...hospitalMapInitialCenter };
const hospitalTileSize = 256;
let hospitalMapMetric = 'internacoes';
let hospitalMapDrag = null;
const hospitalMapMetricMeta = {
    internacoes: { label: 'Internações', field: 'internacoes', money: false, decimals: 0 },
    mp: { label: 'MP', field: 'mp', money: false, decimals: 2 },
    custo: { label: 'Custo', field: 'custo', money: true, decimals: 2 }
};

function mercatorX(lng, zoom) {
    return ((Number(lng) + 180) / 360) * Math.pow(2, zoom) * hospitalTileSize;
}

function mercatorY(lat, zoom) {
    const rad = Number(lat) * Math.PI / 180;
    return (1 - Math.log(Math.tan(rad) + 1 / Math.cos(rad)) / Math.PI) / 2 * Math.pow(2, zoom) * hospitalTileSize;
}

function lngFromMercatorX(x, zoom) {
    return (x / (Math.pow(2, zoom) * hospitalTileSize)) * 360 - 180;
}

function latFromMercatorY(y, zoom) {
    const n = Math.PI - 2 * Math.PI * y / (Math.pow(2, zoom) * hospitalTileSize);
    return (180 / Math.PI) * Math.atan(0.5 * (Math.exp(n) - Math.exp(-n)));
}

function clampHospitalMapCenter(center) {
    return {
        lat: Math.max(-38, Math.min(10, Number(center.lat) || hospitalMapInitialCenter.lat)),
        lng: Math.max(-82, Math.min(-28, Number(center.lng) || hospitalMapInitialCenter.lng))
    };
}

function hospitalMapValue(point, metric) {
    const meta = hospitalMapMetricMeta[metric] || hospitalMapMetricMeta.internacoes;
    return Number(point[meta.field] || 0);
}

function hospitalMapRadius(point, metric) {
    const values = mapaHospitais.map((item) => hospitalMapValue(item, metric)).filter((value) => value > 0);
    const max = values.length ? Math.max(...values) : 0;
    if (!max) return 9;
    return 7 + Math.sqrt(hospitalMapValue(point, metric) / max) * 18;
}

function hospitalMapMetricLabel(point, metric) {
    const meta = hospitalMapMetricMeta[metric] || hospitalMapMetricMeta.internacoes;
    const value = hospitalMapValue(point, metric);
    if (meta.money) {
        return meta.label + ': ' + (window.biMoneyTick ? window.biMoneyTick(value) : value.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
    }
    return meta.label + ': ' + value.toLocaleString('pt-BR', {
        maximumFractionDigits: meta.decimals
    });
}

function shortHospitalMapName(name) {
    const text = String(name || 'Sem informações').trim();
    return text.length > 32 ? text.slice(0, 29) + '...' : text;
}

function hospitalMapPointsCenter(points) {
    if (!points.length) return null;

    let latTotal = 0;
    let lngTotal = 0;
    let weightTotal = 0;
    points.forEach((point) => {
        const weight = Math.max(1, hospitalMapValue(point, hospitalMapMetric));
        latTotal += Number(point.lat) * weight;
        lngTotal += Number(point.lng) * weight;
        weightTotal += weight;
    });

    return {
        lat: latTotal / weightTotal,
        lng: lngTotal / weightTotal
    };
}

function visibleHospitalMapPoints() {
    const map = document.getElementById('hospitalGeoMap');
    if (!map) return [];

    const width = map.clientWidth || 800;
    const height = map.clientHeight || 430;
    const centerPx = mercatorX(hospitalMapCenter.lng, hospitalMapZoom);
    const centerPy = mercatorY(hospitalMapCenter.lat, hospitalMapZoom);
    const originX = centerPx - width / 2;
    const originY = centerPy - height / 2;

    return mapaHospitais.filter((point) => {
        const x = mercatorX(point.lng, hospitalMapZoom) - originX;
        const y = mercatorY(point.lat, hospitalMapZoom) - originY;
        return x >= -50 && x <= width + 50 && y >= -50 && y <= height + 50;
    });
}

function focusHospitalMapPoints() {
    const visiblePoints = visibleHospitalMapPoints();
    const center = hospitalMapPointsCenter(visiblePoints.length ? visiblePoints : mapaHospitais);
    if (center) {
        hospitalMapCenter = clampHospitalMapCenter(center);
    }
}

function setHospitalMapZoom(nextZoom, focusPoints = false) {
    if (focusPoints) {
        focusHospitalMapPoints();
    }
    hospitalMapZoom = Math.max(hospitalMapMinZoom, Math.min(hospitalMapMaxZoom, nextZoom));
    renderHospitalGeoMap();
}

function resetHospitalGeoMap() {
    hospitalMapZoom = hospitalMapMinZoom;
    hospitalMapCenter = { ...hospitalMapInitialCenter };
    renderHospitalGeoMap();
}

function initHospitalGeoMapInteractions(map) {
    if (map.dataset.interactionsReady === '1') return;
    map.dataset.interactionsReady = '1';

    map.addEventListener('click', (event) => {
        const actionButton = event.target.closest('[data-map-zoom-action]');
        if (!actionButton) return;

        const action = actionButton.dataset.mapZoomAction;
        if (action === 'in') setHospitalMapZoom(hospitalMapZoom + 1, true);
        if (action === 'out') setHospitalMapZoom(hospitalMapZoom - 1);
        if (action === 'reset') resetHospitalGeoMap();
    });

    map.addEventListener('wheel', (event) => {
        event.preventDefault();
        setHospitalMapZoom(hospitalMapZoom + (event.deltaY < 0 ? 1 : -1));
    }, { passive: false });

    map.addEventListener('pointerdown', (event) => {
        if (event.target.closest('button, a')) return;
        hospitalMapDrag = {
            pointerId: event.pointerId,
            x: event.clientX,
            y: event.clientY,
            centerX: mercatorX(hospitalMapCenter.lng, hospitalMapZoom),
            centerY: mercatorY(hospitalMapCenter.lat, hospitalMapZoom)
        };
        map.classList.add('is-dragging');
        map.setPointerCapture(event.pointerId);
    });

    map.addEventListener('pointermove', (event) => {
        if (!hospitalMapDrag || hospitalMapDrag.pointerId !== event.pointerId) return;
        const dx = event.clientX - hospitalMapDrag.x;
        const dy = event.clientY - hospitalMapDrag.y;
        hospitalMapCenter = clampHospitalMapCenter({
            lng: lngFromMercatorX(hospitalMapDrag.centerX - dx, hospitalMapZoom),
            lat: latFromMercatorY(hospitalMapDrag.centerY - dy, hospitalMapZoom)
        });
        renderHospitalGeoMap();
    });

    map.addEventListener('pointerup', () => {
        hospitalMapDrag = null;
        map.classList.remove('is-dragging');
    });

    map.addEventListener('pointercancel', () => {
        hospitalMapDrag = null;
        map.classList.remove('is-dragging');
    });
}

function renderHospitalGeoMap() {
    const map = document.getElementById('hospitalGeoMap');
    if (!map) return;
    initHospitalGeoMapInteractions(map);

    map.innerHTML = '';
    const width = map.clientWidth || 800;
    const height = map.clientHeight || 430;
    const maxTiles = Math.pow(2, hospitalMapZoom);
    const centerPx = mercatorX(hospitalMapCenter.lng, hospitalMapZoom);
    const centerPy = mercatorY(hospitalMapCenter.lat, hospitalMapZoom);
    const originX = centerPx - width / 2;
    const originY = centerPy - height / 2;

    const minTileX = Math.floor(originX / hospitalTileSize);
    const maxTileX = Math.floor((originX + width) / hospitalTileSize);
    const minTileY = Math.max(0, Math.floor(originY / hospitalTileSize));
    const maxTileY = Math.min(maxTiles - 1, Math.floor((originY + height) / hospitalTileSize));

    for (let tileX = minTileX; tileX <= maxTileX; tileX += 1) {
        for (let tileY = minTileY; tileY <= maxTileY; tileY += 1) {
            if (tileX < 0 || tileX >= maxTiles) continue;
            const tile = document.createElement('img');
            tile.className = 'hospital-geo-map__tile';
            tile.src = 'https://tile.openstreetmap.org/' + hospitalMapZoom + '/' + tileX + '/' + tileY + '.png';
            tile.alt = '';
            tile.style.left = (tileX * hospitalTileSize - originX) + 'px';
            tile.style.top = (tileY * hospitalTileSize - originY) + 'px';
            tile.style.width = (hospitalTileSize + 1) + 'px';
            tile.style.height = (hospitalTileSize + 1) + 'px';
            map.appendChild(tile);
        }
    }

    if (!mapaHospitais.length) {
        const empty = document.createElement('div');
        empty.className = 'hospital-geo-map__empty';
        empty.textContent = 'Sem hospitais com coordenada ou UF para o período.';
        map.appendChild(empty);
    }

    mapaHospitais.forEach((point) => {
        const x = mercatorX(point.lng, hospitalMapZoom) - originX;
        const y = mercatorY(point.lat, hospitalMapZoom) - originY;
        if (x < -50 || x > width + 50 || y < -50 || y > height + 50) return;
        const radius = hospitalMapRadius(point, hospitalMapMetric);
        const bubble = document.createElement('button');
        bubble.type = 'button';
        bubble.className = 'hospital-geo-map__bubble';
        bubble.style.left = x + 'px';
        bubble.style.top = y + 'px';
        bubble.style.width = (radius * 2) + 'px';
        bubble.style.height = (radius * 2) + 'px';
        bubble.title = [
            point.hospital + (point.uf ? ' / ' + point.uf : ''),
            hospitalMapMetricLabel(point, hospitalMapMetric),
            'Internações: ' + Number(point.internacoes || 0).toLocaleString('pt-BR'),
            'MP: ' + Number(point.mp || 0).toLocaleString('pt-BR', { maximumFractionDigits: 2 }),
            'Custo: ' + (window.biMoneyTick ? window.biMoneyTick(point.custo || 0) : point.custo),
            point.source || ''
        ].filter(Boolean).join('\n');
        bubble.setAttribute('aria-label', bubble.title.replace(/\n/g, '. '));
        map.appendChild(bubble);

        const label = document.createElement('div');
        label.className = 'hospital-geo-map__label';
        if (hospitalMapZoom >= 14) {
            label.classList.add('is-fixed');
        }
        label.style.left = (x + radius + 10) + 'px';
        label.style.top = y + 'px';
        label.textContent = shortHospitalMapName(point.hospital);
        const labelValue = document.createElement('span');
        labelValue.textContent = hospitalMapMetricLabel(point, hospitalMapMetric);
        label.appendChild(labelValue);
        map.appendChild(label);
    });

    const attr = document.createElement('div');
    attr.className = 'hospital-geo-map__attribution';
    attr.innerHTML = '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>';
    map.appendChild(attr);

    const controls = document.createElement('div');
    controls.className = 'hospital-geo-map__controls';
    controls.innerHTML = [
        '<button type="button" class="hospital-geo-map__control" data-map-zoom-action="in" title="Aproximar">+</button>',
        '<button type="button" class="hospital-geo-map__control" data-map-zoom-action="out" title="Afastar">-</button>',
        '<button type="button" class="hospital-geo-map__control" data-map-zoom-action="reset" title="Brasil inteiro">⌂</button>'
    ].join('');
    map.appendChild(controls);
}

document.querySelectorAll('[data-map-metric]').forEach((button) => {
    button.addEventListener('click', () => {
        hospitalMapMetric = button.dataset.mapMetric || 'internacoes';
        document.querySelectorAll('[data-map-metric]').forEach((item) => {
            item.classList.toggle('is-active', item === button);
        });
        renderHospitalGeoMap();
    });
});

renderHospitalGeoMap();
window.addEventListener('resize', renderHospitalGeoMap);
</script>

<?php require_once("templates/footer.php"); ?>
