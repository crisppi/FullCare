<?php

/** Períodos [início, fim): a data da troca pertence à acomodação seguinte. */
final class ProrrogacaoTimeline
{
    public static function date($value): ?string
    {
        $value = trim((string)$value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T].*)?$/D', $value)) return null;
        $day = substr($value, 0, 10);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $day, new DateTimeZone('UTC'));
        return $date && $date->format('Y-m-d') === $day ? $day : null;
    }

    public static function days(string $start, string $end): int
    {
        return (int)(new DateTimeImmutable($start, new DateTimeZone('UTC')))
            ->diff(new DateTimeImmutable($end, new DateTimeZone('UTC')))->format('%r%a');
    }

    public static function row(array $row): array
    {
        return [
            'id' => (int)($row['id_prorrogacao'] ?? $row['id'] ?? 0),
            'ini' => self::date($row['prorrog1_ini_pror'] ?? $row['ini'] ?? null),
            'fim' => self::date($row['prorrog1_fim_pror'] ?? $row['fim'] ?? null),
            'acomod' => trim((string)($row['acomod1_pror'] ?? $row['acomod'] ?? '')),
        ];
    }

    public static function assertRows(array $rows, string $admission, ?string $discharge): void
    {
        $periods = [];
        foreach ($rows as $index => $raw) {
            $row = self::row($raw);
            $label = 'Prorrogação ' . ($index + 1) . ': ';
            if (!$row['ini'] || !$row['fim'] || $row['acomod'] === '') {
                throw new DomainException($label . 'informe acomodação e datas válidas de início e fim.');
            }
            if ($row['fim'] <= $row['ini']) throw new DomainException($label . 'o fim deve ser posterior ao início.');
            if ($row['ini'] < $admission) throw new DomainException($label . 'o início não pode ser anterior à internação.');
            if ($discharge && $row['fim'] > $discharge) throw new DomainException($label . 'o período não pode ultrapassar a alta.');
            $periods[] = $row;
        }
        usort($periods, static fn($a, $b) => strcmp($a['ini'], $b['ini']));
        $end = null;
        foreach ($periods as $row) {
            if ($end !== null && $row['ini'] < $end) throw new DomainException('Existem prorrogações com períodos sobrepostos. Ajuste a sequência antes de salvar.');
            $end = $row['fim'];
        }
    }

    public static function coverage(array $rows, string $admission, ?string $discharge): array
    {
        $periods = array_map([self::class, 'row'], $rows);
        $periods = array_values(array_filter($periods, static fn($r) => $r['ini'] && $r['fim'] && $r['fim'] > $r['ini']));
        usort($periods, static fn($a, $b) => strcmp($a['ini'], $b['ini']));
        $end = $discharge ?: ($periods ? max(array_column($periods, 'fim')) : $admission);
        $cursor = $admission;
        $gaps = [];
        foreach ($periods as $row) {
            $start = max($row['ini'], $admission);
            $finish = min($row['fim'], $end);
            if ($finish <= $cursor) continue;
            if ($start > $cursor) $gaps[] = ['ini' => $cursor, 'fim' => min($start, $end)];
            $cursor = max($cursor, $finish);
            if ($cursor >= $end) break;
        }
        if ($cursor < $end) $gaps[] = ['ini' => $cursor, 'fim' => $end];
        $missing = array_sum(array_map(static fn($g) => self::days($g['ini'], $g['fim']), $gaps));
        return [
            'gaps' => $gaps, 'missingDays' => $missing,
            'complete' => !$gaps && ($discharge !== null || count($periods) > 0),
            'next' => $gaps[0] ?? ($discharge ? null : ['ini' => max($cursor, $admission), 'fim' => null]),
        ];
    }

    public static function context(PDO $conn, int $id, bool $lock = false): array
    {
        $stmt = $conn->prepare('SELECT data_intern_int FROM tb_internacao WHERE id_internacao = ?' . ($lock ? ' FOR UPDATE' : ''));
        $stmt->execute([$id]);
        $admission = self::date($stmt->fetchColumn());
        if (!$admission) throw new DomainException('Internação não encontrada ou com data inválida.');
        $stmt = $conn->prepare('SELECT MAX(data_alta_alt) FROM tb_alta WHERE fk_id_int_alt = ?');
        $stmt->execute([$id]);
        $discharge = self::date($stmt->fetchColumn());
        $stmt = $conn->prepare('SELECT * FROM tb_prorrogacao WHERE fk_internacao_pror = ? ORDER BY prorrog1_ini_pror, id_prorrogacao' . ($lock ? ' FOR UPDATE' : ''));
        $stmt->execute([$id]);
        return ['admission' => $admission, 'discharge' => $discharge, 'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }
}
