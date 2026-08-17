(function ($) {
    'use strict';

    var cfg = window.CentralMidiMidis || {};

    function showNotice(message, type) {
        var $notice = $('#centralmidi-midis-notice');
        $notice
            .removeClass('notice-success notice-error')
            .addClass(type === 'error' ? 'notice-error' : 'notice-success')
            .show()
            .html('<p>' + $('<span></span>').text(message).html() + '</p>');
        clearTimeout(showNotice._t);
        showNotice._t = setTimeout(function () {
            $notice.fadeOut(300);
        }, 4000);
    }

    function formatArquivo(cell) {
        var url = cell.getValue();
        if (!url) {
            return '<span class="cm-arquivo-vazio">' + (cfg.textos ? cfg.textos.semArquivo : '') + '</span>';
        }
        var nome = url.split('/').pop() || url;
        return '<a href="' + $('<span></span>').text(url).html() + '" target="_blank" rel="noopener" title="' + $('<span></span>').text(url).html() + '">' + $('<span></span>').text(nome).html() + '</a>';
    }

    function formatTitulo(cell) {
        var data = cell.getRow().getData();
        var titulo = cell.getValue();
        var out = '<strong>' + $('<span></span>').text(titulo).html() + '</strong>';
        var links = [];
        if (data.edit_url) {
            links.push('<a href="' + $('<span></span>').text(data.edit_url).html() + '">Editar</a>');
        }
        if (data.view_url) {
            links.push('<a href="' + $('<span></span>').text(data.view_url).html() + '" target="_blank" rel="noopener">Ver</a>');
        }
        if (links.length) {
            out += '<div class="cm-row-actions">' + links.join(' | ') + '</div>';
        }
        return out;
    }

    function formatMes(cell) {
        var mes = cell.getValue();
        var meses = cfg.meses || {};
        return meses[mes] || (mes ? mes : '');
    }

    function formatClassificacao(cell) {
        var v = cell.getValue();
        return v ? '#' + v : '';
    }

    function saveCell(cell, callback) {
        var field = cell.getField();
        var row = cell.getRow().getData();
        var value = cell.getValue() == null ? '' : cell.getValue();

        var payloadField = field;
        var payloadValue = value;

        if (field === 'artista') {
            var a = (cfg.artistas || []).filter(function (x) { return x.nome === value; })[0];
            if (!a) {
                cell.setValueOriginal();
                return;
            }
            payloadField = 'artista_id';
            payloadValue = a.id;
        } else if (field === 'genero') {
            var g = (cfg.generos || []).filter(function (x) { return x.nome === value; })[0];
            if (!g) {
                cell.setValueOriginal();
                return;
            }
            payloadField = 'genero_id';
            payloadValue = g.id;
        }

        $.post(cfg.ajaxUrl, {
            action: 'centralmidi_midis_save',
            nonce: cfg.nonce,
            product_id: row.product_id,
            field: payloadField,
            value: payloadValue
        }, function (resp) {
            if (resp && resp.success) {
                cell.setValue(resp.data.value);
                if (typeof callback === 'function') {
                    callback();
                }
            } else {
                cell.setValueOriginal();
                showNotice((resp && resp.data && resp.data.message) || cfg.textos.erro, 'error');
            }
        }).fail(function () {
            cell.setValueOriginal();
            showNotice(cfg.textos.erro, 'error');
        });
    }

    var artistasSelect = [];
    var artistasFiltro = [{ label: '— Todos —', value: '' }];
    (cfg.artistas || []).forEach(function (a) {
        artistasSelect.push({ label: a.nome, value: a.nome });
        artistasFiltro.push({ label: a.nome, value: a.nome });
    });

    var generosSelect = [];
    var generosFiltro = [{ label: '— Todos —', value: '' }];
    (cfg.generos || []).forEach(function (g) {
        generosSelect.push({ label: g.nome, value: g.nome });
        generosFiltro.push({ label: g.nome, value: g.nome });
    });

    var classificacoesFiltro = [{ label: '— Todas —', value: '' }];
    Object.keys(cfg.classificacoes || {}).forEach(function (code) {
        classificacoesFiltro.push({ label: '#' + code, value: code });
    });

    var table = new Tabulator('#centralmidi-midis-table', {
        layout: 'fitColumns',
        ajaxURL: cfg.ajaxUrl,
        ajaxParams: {
            action: 'centralmidi_midis_table',
            nonce: cfg.nonce
        },
        ajaxFiltering: true,
        ajaxSorting: true,
        paginationMode: 'remote',
        paginationSize: 20,
        paginationSizeSelector: [20, 50, 100],
        selectableRows: true,
        selectableRowsHighlight: true,
        placeholder: 'Nenhum MIDI encontrado.',
        ajaxResponse: function (url, params, response) {
            if (response && response.success) {
                var d = response.data;
                var total = d.total || 0;
                $('#centralmidi-count').text(total + ' MIDI(s)');
                return d;
            }
            return [];
        },
        columns: [
            {
                formatter: 'rowSelection',
                titleFormatter: 'rowSelection',
                hozAlign: 'center',
                headerSort: false,
                width: 40,
                resizable: false
            },
            {
                title: 'Produto',
                field: 'titulo',
                minWidth: 220,
                frozen: true,
                formatter: formatTitulo,
                headerFilter: true,
                headerFilterFunc: 'like'
            },
            {
                title: 'Artista',
                field: 'artista',
                editor: 'select',
                editorParams: { values: artistasSelect },
                headerFilter: 'select',
                headerFilterParams: { values: artistasFiltro }
            },
            {
                title: 'Gênero',
                field: 'genero',
                editor: 'select',
                editorParams: { values: generosSelect },
                headerFilter: 'select',
                headerFilterParams: { values: generosFiltro }
            },
            {
                title: 'Mês',
                field: 'mes',
                hozAlign: 'center',
                sorter: 'number',
                formatter: formatMes,
                editor: 'number',
                editorParams: { min: 1, max: 12 },
                headerFilter: 'number'
            },
            {
                title: 'Ano',
                field: 'ano',
                hozAlign: 'center',
                sorter: 'number',
                editor: 'number',
                headerFilter: 'number'
            },
            {
                title: 'Class',
                field: 'classificacao',
                hozAlign: 'center',
                sorter: 'string',
                formatter: formatClassificacao,
                editor: 'select',
                editorParams: { values: ['M', 'L', 'RLM'] },
                headerFilter: 'select',
                headerFilterParams: { values: classificacoesFiltro }
            },
            {
                title: 'Arquivo',
                field: 'arquivo',
                minWidth: 220,
                formatter: formatArquivo,
                editor: 'input'
            }
        ],
        cellEdited: function (cell) {
            saveCell(cell);
        },
        rowSelectionChanged: function (data) {
            var total = data.length;
            $('#centralmidi-count').text(total + ' selecionado(s)');
        }
    });

    $('#centralmidi-select-all').on('click', function () {
        table.selectRow();
    });

    $('#centralmidi-select-clear').on('click', function () {
        table.deselectRow();
    });

    $('#centralmidi-export-csv').on('click', function () {
        table.download('csv', 'centralmidi-midis.csv');
    });

    $('#centralmidi-bulk-apply').on('click', function () {
        var op = $('#centralmidi-bulk-op').val();
        var ids = table.getSelectedRows().map(function (r) {
            return r.getData().product_id;
        });

        if (!op) {
            showNotice(cfg.textos.acao, 'error');
            return;
        }
        if (!ids.length) {
            showNotice(cfg.textos.selecione, 'error');
            return;
        }
        if (op === 'delete' && !window.confirm(cfg.textos.confirmar)) {
            return;
        }

        $.post(cfg.ajaxUrl, {
            action: 'centralmidi_midis_bulk',
            nonce: cfg.nonce,
            op: op,
            ids: ids
        }, function (resp) {
            if (resp && resp.success) {
                table.deselectRow();
                table.replaceData();
                showNotice(resp.data.count + ' ' + cfg.textos.atualizado, 'success');
            } else {
                showNotice((resp && resp.data && resp.data.message) || cfg.textos.erro, 'error');
            }
        }).fail(function () {
            showNotice(cfg.textos.erro, 'error');
        });
    });
})(jQuery);
