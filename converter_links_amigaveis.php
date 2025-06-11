<?php

// Lista de módulos utilizados
$modulos = [
    'antecedente', 'hospital', 'seguradora', 'estipulante',
    'hospitalUser', 'usuario', 'visita', 'patologia',
    'mensagem', 'internacao_censo', 'internacao',
    'censo', 'capeante_audit'
];

// Caminho base do projeto
$base = __DIR__;

// Função para converter os links
function converterLinks($arquivo, $modulos) {
    $conteudo = file_get_contents($arquivo);
    $modificado = false;

    foreach ($modulos as $modulo) {
        // Listagem
        $conteudo = preg_replace_callback('/href=["\']list_' . $modulo . '\.php["\']/', function () use ($modulo) {
            return 'href="/' . normalizar($modulo) . '"';
        }, $conteudo, -1, $count1);

        // Cadastro
        $conteudo = preg_replace_callback('/href=["\']cad_' . $modulo . '\.php["\']/', function () use ($modulo) {
            return 'href="/' . normalizar($modulo) . '/novo"';
        }, $conteudo, -1, $count2);

        // Edição
        $conteudo = preg_replace_callback('/href=["\']edit_' . $modulo . '\.php\?id=([0-9]+)["\']/', function ($m) use ($modulo) {
            return 'href="/' . normalizar($modulo) . '/editar/' . $m[1] . '"';
        }, $conteudo, -1, $count3);

        // Visualização
        $conteudo = preg_replace_callback('/href=["\']show_' . $modulo . '\.php\?id=([0-9]+)["\']/', function ($m) use ($modulo) {
            return 'href="/' . normalizar($modulo) . '/visualizar/' . $m[1] . '"';
        }, $conteudo, -1, $count4);

        // Exclusão
        $conteudo = preg_replace_callback('/href=["\']del_' . $modulo . '\.php\?id=([0-9]+)["\']/', function ($m) use ($modulo) {
            return 'href="/' . normalizar($modulo) . '/excluir/' . $m[1] . '"';
        }, $conteudo, -1, $count5);

        if ($count1 + $count2 + $count3 + $count4 + $count5 > 0) {
            $modificado = true;
            echo "✔️ Corrigido $modulo em: $arquivo\n";
        }
    }

    if ($modificado) {
        file_put_contents($arquivo, $conteudo);
    }
}

// Nome amigável na URL (ex: hospitalUser → hospital-usuarios)
function normalizar($modulo) {
    return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $modulo));
}

// Percorrer todos os arquivos do projeto
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($base)
);

foreach ($iterator as $arquivo) {
    if ($arquivo->isFile() && pathinfo($arquivo, PATHINFO_EXTENSION) === 'php') {
        converterLinks($arquivo->getPathname(), $modulos);
    }
}

echo "\n✅ Substituição concluída.\n";