/**
 * Central MIDI — Migração: conduz a importação em lotes via AJAX.
 *
 * Três etapas:
 *   1. análise    (somente leitura) — mostra o que será encontrado
 *   2. prepare   — cria artistas/gêneros e monta o mapa term_id -> id
 *   3. batch     — repete até o servidor sinalizar `done`
 */
(function ($) {
    'use strict';

    var cfg = window.CentralMidiMigration || {};
    var $run = $('#cm-mig-run');
    var $reset = $('#cm-mig-reset');
    var $analyze = $('#cm-mig-analyze');
    var $bar = $('#cm-mig-bar');
    var $count = $('#cm-mig-count');
    var running = false;
    var acumulado = {};

    function post(action, data) {
        return $.post(cfg.ajaxUrl, $.extend({
            action: action,
            nonce: cfg.nonce
        }, data || {}));
    }

    function escapeHtml(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }

    /**
     * Renderiza o relatório de conflitos agrupado por tipo.
     * @param {Object} log {tipos: [...], total, truncado}
     */
    function renderConflitos(log) {
        var box = $('#cm-mig-conflitos');
        if (!log || !log.tipos || !log.tipos.length) {
            box.prop('hidden', true).empty();
            return;
        }

        var html = '<h3>Conflitos encontrados (' + log.total + ')</h3>';
        html += '<p>Os dados não foram descartados: em todos os casos o primeiro valor foi mantido e o restante está listado aqui para conferência.</p>';
        html += '<table class="widefat striped cm-mig-table"><thead><tr><th>Tipo</th><th>Ocorrências</th><th>Como foi resolvido</th></tr></thead><tbody>';

        $.each(log.tipos, function (_, t) {
            html += '<tr><td><strong>' + escapeHtml(t.rotulo || t.tipo) + '</strong><br><code>' + escapeHtml(t.tipo) + '</code></td>';
            html += '<td>' + t.n + '</td><td>' + escapeHtml(t.ajuda || '—') + '</td></tr>';
        });
        html += '</tbody></table>';

        $.each(log.tipos, function (_, t) {
            if (!t.amostra || !t.amostra.length) {
                return;
            }
            html += '<h4>' + escapeHtml(t.rotulo || t.tipo) + ' — até ' + t.amostra.length + ' exemplos</h4>';
            html += '<table class="widefat striped cm-mig-table"><thead><tr><th>Produto</th><th>Detalhe</th><th>Editar</th></tr></thead><tbody>';
            $.each(t.amostra, function (__, a) {
                var link = a.produto ? 'post.php?post=' + a.produto + '&action=edit' : '#';
                html += '<tr><td>' + a.produto + '</td><td><code>' + escapeHtml(a.detalhe) + '</code></td>';
                html += '<td><a href="' + link + '" target="_blank" rel="noopener">abrir</a></td></tr>';
            });
            html += '</tbody></table>';
        });

        if (log.truncado) {
            html += '<p><em>As amostras estão limitadas; os contadores acima refletem o total real.</em></p>';
        }
        box.html(html).prop('hidden', false);
    }

    function linha(label, valor, destaque) {
        return '<tr><th scope="row">' + escapeHtml(label) + '</th><td' +
            (destaque ? ' class="cm-mig-destaque"' : '') + '>' + escapeHtml(valor) + '</td></tr>';
    }

    function soma(obj) {
        var t = 0;
        $.each(obj, function (_, v) { t += (v || 0); });
        return t;
    }

    /* ---------------------------------------------------------------- */
    /* 1. Análise                                                         */
    /* ---------------------------------------------------------------- */

    $analyze.on('click', function () {
        var $btn = $(this).prop('disabled', true);
        $('#cm-mig-analyze-status').text(cfg.i18n.analisando);
        $('#cm-mig-analysis').prop('hidden', true);

        post('centralmidi_migration_analyze')
            .done(function (r) {
                if (!r || !r.success) { return; }
                var d = r.data;
                var html = '<table class="widefat striped cm-mig-table"><tbody>';

                html += linha('Produtos publicados', d.produtos, true);
                html += linha('Artistas (categorias filhas)', d.artistas);
                html += linha('…com foto (thumbnail_id)', d.artistas_foto);
                html += linha('Gêneros', d.generos);
                html += linha('Produtos com campo `rlm`', d.rlm_com);
                html += linha('Produtos sem campo `rlm`', d.rlm_sem + ' (serão gravados com classificação vazia)');
                html += linha('Valores de `rlm` não reconhecidos', d.rlm_sem_mapear
                    ? d.rlm_sem_mapear + ' — serão deixados vazios e listados no relatório' : 'nenhum', !!d.rlm_sem_mapear);
                html += linha('Produtos com demo (`url_demo`)', d.com_demo);
                html += linha('Já presentes na tabela do plugin', d.ja_migrados);
                html += '</tbody></table>';

                html += '<h3>Conversão de <code>rlm</code></h3>';
                html += '<table class="widefat striped cm-mig-table"><thead><tr>' +
                    '<th>Valor no site</th><th>Produtos</th><th>Vira</th></tr></thead><tbody>';
                $.each(d.rlm, function (_, r) {
                    html += '<tr' + (r.ok ? '' : ' class="cm-mig-alerta"') + '>' +
                        '<td>' + escapeHtml(r.valor) + '</td>' +
                        '<td>' + r.n + '</td>' +
                        '<td><code>' + escapeHtml(r.vira) + '</code></td></tr>';
                });
                html += '</tbody></table>';

                if (d.multi_artista || d.multi_mes || d.demo_duplo) {
                    html += '<h3>Conflitos previstos</h3><ul class="cm-mig-alerta">';
                    if (d.multi_artista) html += '<li>' + d.multi_artista + ' produto(s) com mais de um artista — será mantido o primeiro.</li>';
                    if (d.multi_mes) html += '<li>' + d.multi_mes + ' produto(s) com mais de um mês — será mantido o primeiro.</li>';
                    if (d.demo_duplo) html += '<li>' + d.demo_duplo + ' produto(s) com mais de um <code>url_demo</code> — será mantido o primeiro.</li>';
                    html += '</ul>';
                }

                $('#cm-mig-analysis').html(html).prop('hidden', false);
                $run.prop('disabled', false);
                $reset.prop('disabled', false);
                acumulado.total = d.produtos;
                if (d.conflitos) { cfg.conflitos = d.conflitos; renderConflitos(cfg.conflitos); }
            })
            .fail(function (xhr) {
                $('#cm-mig-analyze-status').text(cfg.i18n.erro + ' ' + (xhr.status || ''));
            })
            .always(function () { $btn.prop('disabled', false); });
    });

    /* ---------------------------------------------------------------- */
    /* 2 + 3. Preparar e migrar                                           */
    /* ---------------------------------------------------------------- */

    $run.on('click', function () {
        if (running) { return; }
        if (!window.confirm(cfg.i18n.confirmExec)) { return; }

        running = true;
        acumulado = { processados: 0, linhas: 0, artista: 0, genero: 0, mes: 0, classificacao: 0, demo: 0, sem_artista: 0 };
        $run.prop('disabled', true);
        $('#cm-mig-report').prop('hidden', true).empty();
        $('.cm-mig-progress').prop('hidden', false);
        $('#cm-mig-run-status').text(cfg.i18n.preparando);
        $bar.val(0);

        post('centralmidi_migration_prepare')
            .done(function (r) {
                if (!r || !r.success) { return; }
                acumulado.total = r.data.produtos;
                $('#cm-mig-count').text('0 / ' + acumulado.total);
                $('#cm-mig-run-status').text(cfg.i18n.migrando);
                proximoLote();
            })
            .fail(function (xhr) {
                running = false;
                $run.prop('disabled', false);
                $('#cm-mig-run-status').text(cfg.i18n.erro + ' ' + (xhr.status || ''));
            });
    });

    function proximoLote() {
        post('centralmidi_migration_batch', { batch_size: cfg.batchSize })
            .done(function (r) {
                if (!r || !r.success) {
                    running = false;
                    $run.prop('disabled', false);
                    $('#cm-mig-run-status').text(cfg.i18n.erro + ' ' + ((r && r.data && r.data.message) || ''));
                    return;
                }

                var d = r.data;
                acumulado.processados = d.processed;
                $.each(d.stats || {}, function (k, v) { acumulado[k] = (acumulado[k] || 0) + v; });

                var pct = acumulado.total ? Math.round((d.processed / acumulado.total) * 100) : 0;
                $bar.val(pct);
                $('#cm-mig-count').text(d.processed + ' / ' + acumulado.total + '  (' + pct + '%)');

                if (d.done) {
                    if (d.conflitos) { cfg.conflitos = d.conflitos; }
                    finalizar();
                } else {
                    proximoLote();
                }
            })
            .fail(function (xhr) {
                running = false;
                $run.prop('disabled', false);
                $('#cm-mig-run-status').text(
                    cfg.i18n.erro + ' ' + (xhr.status || '') + ' — clique em “Executar migração” para retomar de onde parou.'
                );
            });
    }

    function finalizar() {
        running = false;
        $run.prop('disabled', false);
        $('#cm-mig-run-status').text(cfg.i18n.concluido);

        var html = '<h3>Resumo</h3><table class="widefat striped cm-mig-table"><tbody>';
        html += linha('Produtos processados', acumulado.processados, true);
        html += linha('Linhas em wp_centralmidi_midis', acumulado.linhas);
        html += linha('Com artista vinculado', acumulado.artista);
        html += linha('…sem artista', acumulado.sem_artista);
        html += linha('Com gênero vinculado', acumulado.genero);
        html += linha('Com mês/ano', acumulado.mes);
        html += linha('Com classificação (M/L/RLM)', acumulado.classificacao);
        html += linha('Sem classificação (vazio)', (acumulado.processados || 0) - (acumulado.classificacao || 0));
        html += linha('Com demo (_centralmidi_demo_audio)', acumulado.demo);
        html += '</tbody></table>';

        $('#cm-mig-report').html(html).prop('hidden', false);
        renderConflitos(cfg.conflitos);
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    }

    /* ---------------------------------------------------------------- */
    /* Reset                                                              */
    /* ---------------------------------------------------------------- */

    $reset.on('click', function () {
        if (running) { return; }
        if (!window.confirm(cfg.i18n.confirmReset)) { return; }
        post('centralmidi_migration_reset')
            .done(function (r) {
                if (r && r.success) { window.location.reload(); }
            });
    });

    /* ---------------------------------------------------------------- */
    /* Conflitos já registrados em execuções anteriores                  */
    /* ---------------------------------------------------------------- */

    $(function () {
        renderConflitos(cfg.conflitos);
    });
}(jQuery));
