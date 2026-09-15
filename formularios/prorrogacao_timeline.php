<?php
require_once __DIR__ . '/../app/ProrrogacaoTimeline.php';
$timelineContext = ['admission' => null, 'discharge' => null, 'rows' => []];
$timelineId = !empty($timelineEditingAll) ? (int)($intern['id_internacao'] ?? 0) : (int)($id_internacao ?? 0);
if ($timelineId > 0) {
    $timelineContext = ProrrogacaoTimeline::context($conn, $timelineId);
}
$timelineEditingIds = array_map('intval', array_column($prorrogEditRows ?? [], 'id_prorrogacao'));
$timelineHistory = !empty($timelineEditingAll) ? [] : array_values(array_filter($timelineContext['rows'], static function ($row) use ($timelineEditingIds) {
    return !in_array((int)$row['id_prorrogacao'], $timelineEditingIds, true);
}));
$timelineConfig = [
    'admission' => $timelineContext['admission'], 'discharge' => $timelineContext['discharge'],
    'history' => array_map([ProrrogacaoTimeline::class, 'row'], $timelineHistory),
];
?>
<script id="prorrog-timeline-config" type="application/json"><?= json_encode($timelineConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= $BASE_URL ?>js/prorrogacao_timeline.js?v=<?= filemtime(__DIR__ . '/../js/prorrogacao_timeline.js') ?>"></script>
