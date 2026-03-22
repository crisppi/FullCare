<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>FullCare Mobile Web</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="mobile-shell">
        <section class="screen auth-screen" id="auth-screen">
            <div class="auth-card">
                <img class="auth-logo" src="../img/logo_fullcare_branco_large.png" alt="FullCare">
                <p class="eyebrow">FullCare Mobile Web</p>
                <h1>Internação, TUSS, prorrogação e evolução no navegador.</h1>
                <p class="auth-copy">Use o mesmo acesso do sistema para entrar e operar pelo celular.</p>

                <form id="login-form" class="stack-form">
                    <label>
                        E-mail
                        <input name="email" type="email" autocomplete="username" required>
                    </label>
                    <label>
                        Senha
                        <input name="password" type="password" autocomplete="current-password" required>
                    </label>
                    <button class="primary-button" type="submit">Entrar</button>
                </form>
            </div>
        </section>

        <section class="screen app-screen" id="app-screen" hidden>
            <header class="topbar">
                <div>
                    <img class="topbar-logo" src="../img/LogoFullCare.png" alt="FullCare">
                    <p class="eyebrow dark" id="user-role">Carregando</p>
                    <strong id="user-name">-</strong>
                </div>
                <button class="ghost-button compact" id="logout-button" type="button">Sair</button>
            </header>

            <section class="feedback" id="feedback" hidden></section>

            <section class="view" id="admissions-view">
                <div class="section-header">
                    <div>
                        <p class="section-tag">Internações</p>
                        <h2>Pacientes internados</h2>
                    </div>
                    <button class="ghost-button compact" id="refresh-button" type="button">Atualizar</button>
                </div>

                <div class="panel">
                    <label>
                        Buscar paciente ou hospital
                        <input id="search-input" type="search" placeholder="Digite para filtrar">
                    </label>
                    <p class="summary-line" id="admissions-total">Total de internados: 0</p>
                </div>

                <div class="admission-list" id="admissions-list"></div>
            </section>

            <section class="view" id="detail-view" hidden>
                <div class="section-header">
                    <button class="back-button" id="back-to-list" type="button">Voltar</button>
                    <div>
                        <p class="section-tag">Detalhe</p>
                        <h2>Internação</h2>
                    </div>
                </div>

                <article class="panel detail-card" id="detail-card"></article>

                <div class="section-header tight">
                    <div>
                        <p class="section-tag">Ações</p>
                        <h3>Ações da internação</h3>
                    </div>
                </div>

                <div class="action-grid">
                    <button class="action-tile action-prorro" id="open-extension-modal" type="button">
                        <strong>Prorrogação</strong>
                        <span>Lançar nova</span>
                    </button>
                    <button class="action-tile action-tuss" id="open-tuss-modal" type="button">
                        <strong>TUSS</strong>
                        <span>Cadastrar item</span>
                    </button>
                    <button class="action-tile action-evolution" id="open-evolutions-view" type="button">
                        <strong>Evolução</strong>
                        <span>Ver histórico</span>
                    </button>
                    <button class="action-tile action-discharge" id="open-discharge-modal" type="button">
                        <strong>Alta</strong>
                        <span>Registrar saída</span>
                    </button>
                </div>

                <article class="panel" id="latest-extension-panel" hidden>
                    <div class="panel-header">
                        <div>
                            <p class="section-tag">Resumo</p>
                            <h3>Última prorrogação</h3>
                        </div>
                    </div>
                    <div id="latest-extension-content"></div>
                </article>

                <article class="panel">
                    <div class="panel-header">
                        <div>
                            <p class="section-tag">Procedimentos</p>
                            <h3>TUSS</h3>
                        </div>
                    </div>
                    <div class="inline-count" id="tuss-count">0 itens</div>
                    <div class="stack-list" id="tuss-list"></div>
                </article>

                <article class="panel">
                    <div class="panel-header">
                        <div>
                            <p class="section-tag">Prorrogações</p>
                            <h3>Histórico</h3>
                        </div>
                    </div>
                    <div class="inline-count" id="extensions-count">0 registros</div>
                    <div class="stack-list" id="extensions-list"></div>
                </article>
            </section>

            <section class="view" id="evolutions-view" hidden>
                <div class="section-header">
                    <button class="back-button" id="back-to-detail" type="button">Voltar</button>
                    <div>
                        <p class="section-tag">Evoluções</p>
                        <h2>Histórico clínico</h2>
                    </div>
                </div>

                <article class="panel">
                    <div class="panel-header">
                        <div>
                            <p class="section-tag">Paciente</p>
                            <h3 id="evolutions-patient-name">-</h3>
                        </div>
                        <button class="primary-button compact-inline" id="open-evolution-modal" type="button">Nova evolução</button>
                    </div>
                    <div class="stack-list" id="evolutions-list"></div>
                </article>
            </section>
        </section>
    </main>

    <div class="modal-shell" id="modal-shell" hidden>
        <div class="modal-backdrop" data-close-modal></div>
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <p class="section-tag">Operação</p>
                    <h3 id="modal-title">Modal</h3>
                </div>
                <button class="ghost-button compact" id="close-modal" type="button">Fechar</button>
            </div>
            <form class="stack-form" id="modal-form"></form>
        </div>
    </div>

    <template id="empty-card-template">
        <article class="panel empty-panel">
            <p class="empty-state">Nenhum registro encontrado.</p>
        </article>
    </template>

    <script src="assets/app.js"></script>
</body>
</html>
