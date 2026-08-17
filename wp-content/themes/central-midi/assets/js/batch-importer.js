/**
 * Batch MIDI Importer - Central MIDI
 */
document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('cm-batch-app');
    if (!app) return;

    const nonce = app.dataset.nonce;
    const ajaxUrl = app.dataset.ajaxurl;

    const selectMes = document.getElementById('batch_mes');
    const inputAno = document.getElementById('batch_ano');
    const inputPrice = document.getElementById('batch_price');
    const selectClass = document.getElementById('batch_classificacao');
    const selectGenero = document.getElementById('batch_genero');
    const folderDisplay = document.getElementById('cm-folder-display');

    const btnScan = document.getElementById('cm-btn-scan-folder');
    const scanStatus = document.getElementById('cm-scan-status');

    const pasteInput = document.getElementById('cm-paste-input');
    const btnParsePaste = document.getElementById('cm-btn-parse-paste');

    const tbody = document.getElementById('cm-batch-tbody');
    const totalCountEl = document.getElementById('cm-total-count');
    const btnAddRow = document.getElementById('cm-btn-add-row');
    const btnClearTable = document.getElementById('cm-btn-clear-table');
    const btnStart = document.getElementById('cm-btn-start-batch');

    const progressWrapper = document.getElementById('cm-progress-wrapper');
    const progressLabel = document.getElementById('cm-progress-label');
    const progressPercent = document.getElementById('cm-progress-percent');
    const progressBarFill = document.getElementById('cm-progress-bar-fill');
    const progressLog = document.getElementById('cm-progress-log');
    const resultsSummary = document.getElementById('cm-results-summary');
    const summaryText = document.getElementById('cm-summary-text');

    let currentItems = [];

    // Update folder label
    function updateFolderLabel() {
        const m = selectMes.value;
        const a = inputAno.value;
        if (folderDisplay) folderDisplay.textContent = `midis/${m}/${a}/`;
    }
    selectMes.addEventListener('change', updateFolderLabel);
    inputAno.addEventListener('input', updateFolderLabel);

    // Tab Switching
    document.querySelectorAll('.cm-batch-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.cm-batch-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.cm-tab-content').forEach(c => c.classList.remove('active'));
            tab.classList.add('active');
            const target = document.getElementById('tab-' + tab.dataset.tab);
            if (target) target.classList.add('active');
        });
    });

    // Render Table
    function renderTable() {
        if (!currentItems || currentItems.length === 0) {
            tbody.innerHTML = `
                <tr class="cm-empty-row">
                    <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">
                        <i class="ri-inbox-line" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                        Nenhuma música carregada. Escaneie a pasta do servidor ou cole sua lista acima.
                    </td>
                </tr>
            `;
            totalCountEl.textContent = '0';
            btnStart.disabled = true;
            return;
        }

        totalCountEl.textContent = String(currentItems.length);
        btnStart.disabled = false;

        tbody.innerHTML = currentItems.map((item, idx) => `
            <tr data-index="${idx}">
                <td style="color: var(--text-muted); font-size: 0.85rem; text-align: center;">${idx + 1}</td>
                <td>
                    <input type="text" class="cm-table-input item-title" value="${escapeHtml(item.title || '')}" placeholder="Título da música" required />
                </td>
                <td>
                    <input type="text" class="cm-table-input item-artist" value="${escapeHtml(item.artist || '')}" placeholder="Artista / Banda" />
                </td>
                <td>
                    <input type="text" class="cm-table-input item-genero" value="${escapeHtml(item.genero || '')}" placeholder="${escapeHtml(selectGenero.value || 'Geral')}" />
                </td>
                <td>
                    <select class="cm-table-input item-class">
                        <option value="RLM" ${item.classificacao === 'RLM' ? 'selected' : ''}>#RLM</option>
                        <option value="M" ${item.classificacao === 'M' ? 'selected' : ''}>#M</option>
                        <option value="L" ${item.classificacao === 'L' ? 'selected' : ''}>#L</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="cm-table-input item-price" value="${escapeHtml(item.price || selectPriceVal())}" style="text-align: right;" />
                </td>
                <td>
                    <div class="cm-file-cell">
                        <input type="text" class="cm-table-input item-mp3" value="${escapeHtml(item.mp3_file || '')}" placeholder="arquivo.mp3" />
                        ${item.mp3_exists ? '<span class="cm-status-tag cm-status-ok" title="Arquivo presente no servidor"><i class="ri-checkbox-circle-fill"></i></span>' : ''}
                    </div>
                </td>
                <td>
                    <div class="cm-file-cell">
                        <input type="text" class="cm-table-input item-midi" value="${escapeHtml(item.midi_file || '')}" placeholder="arquivo.mid" />
                        ${item.midi_exists ? '<span class="cm-status-tag cm-status-ok" title="Arquivo presente no servidor"><i class="ri-checkbox-circle-fill"></i></span>' : ''}
                    </div>
                </td>
                <td style="text-align: center;">
                    <button type="button" class="cm-btn-remove-row" data-index="${idx}" title="Remover"><i class="ri-close-line"></i></button>
                </td>
            </tr>
        `).join('');
    }

    function selectPriceVal() {
        return inputPrice.value ? inputPrice.value.trim() : '19.90';
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Sync input edits back to array
    tbody.addEventListener('input', (e) => {
        const row = e.target.closest('tr');
        if (!row) return;
        const idx = parseInt(row.dataset.index, 10);
        if (isNaN(idx) || !currentItems[idx]) return;

        if (e.target.classList.contains('item-title')) currentItems[idx].title = e.target.value;
        if (e.target.classList.contains('item-artist')) currentItems[idx].artist = e.target.value;
        if (e.target.classList.contains('item-genero')) currentItems[idx].genero = e.target.value;
        if (e.target.classList.contains('item-class')) currentItems[idx].classificacao = e.target.value;
        if (e.target.classList.contains('item-price')) currentItems[idx].price = e.target.value;
        if (e.target.classList.contains('item-mp3')) currentItems[idx].mp3_file = e.target.value;
        if (e.target.classList.contains('item-midi')) currentItems[idx].midi_file = e.target.value;
    });

    // Remove row
    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('.cm-btn-remove-row');
        if (!btn) return;
        const idx = parseInt(btn.dataset.index, 10);
        if (!isNaN(idx)) {
            currentItems.splice(idx, 1);
            renderTable();
        }
    });

    // Add Row
    if (btnAddRow) {
        btnAddRow.addEventListener('click', () => {
            currentItems.push({
                title: '',
                artist: '',
                genero: selectGenero.value || '',
                classificacao: selectClass.value || 'RLM',
                price: selectPriceVal(),
                mp3_file: '',
                midi_file: '',
                mp3_exists: false,
                midi_exists: false,
            });
            renderTable();
        });
    }

    // Clear Table
    if (btnClearTable) {
        btnClearTable.addEventListener('click', () => {
            if (currentItems.length > 0 && !confirm('Deseja realmente limpar toda a lista carregada?')) {
                return;
            }
            currentItems = [];
            renderTable();
            if (scanStatus) scanStatus.classList.add('hidden');
        });
    }

    // 1. Scanner Action
    if (btnScan) {
        btnScan.addEventListener('click', async () => {
            const mes = selectMes.value;
            const ano = inputAno.value;

            btnScan.disabled = true;
            btnScan.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Escaneando...';
            scanStatus.classList.remove('hidden');
            scanStatus.innerHTML = `<span style="color: var(--text-muted);"><i class="ri-loader-4-line ri-spin"></i> Lendo pasta midis/${mes}/${ano}/...</span>`;

            try {
                const formData = new FormData();
                formData.append('action', 'centralmidi_scan_folder');
                formData.append('nonce', nonce);
                formData.append('mes', mes);
                formData.append('ano', ano);

                const res = await fetch(ajaxUrl, { method: 'POST', body: formData });
                const json = await res.json();

                if (json.success) {
                    const data = json.data;
                    if (!data.folder_exists) {
                        scanStatus.innerHTML = `<div class="cm-alert-warning"><i class="ri-error-warning-line"></i> A pasta <code>${data.folder_path}</code> não foi encontrada no servidor. Envie os arquivos via FTP.</div>`;
                    } else if (data.total_found === 0) {
                        scanStatus.innerHTML = `<div class="cm-alert-warning"><i class="ri-inbox-line"></i> A pasta <code>${data.folder_path}</code> existe, mas nenhum arquivo .mp3 ou .mid foi encontrado nela.</div>`;
                    } else {
                        scanStatus.innerHTML = `<div class="cm-alert-success"><i class="ri-checkbox-circle-fill"></i> Sucesso! Encontrados <strong>${data.total_found} pares de faixas</strong> na pasta <code>${data.folder_path}</code>.</div>`;
                        currentItems = data.items.map(it => ({
                            ...it,
                            price: selectPriceVal(),
                            classificacao: selectClass.value || 'RLM',
                            genero: selectGenero.value || it.genero || '',
                        }));
                        renderTable();
                    }
                } else {
                    scanStatus.innerHTML = `<div class="cm-alert-error">${json.data?.message || 'Erro ao escanear pasta.'}</div>`;
                }
            } catch (err) {
                console.error(err);
                scanStatus.innerHTML = `<div class="cm-alert-error">Erro de conexão ao escanear pasta.</div>`;
            } finally {
                btnScan.disabled = false;
                btnScan.innerHTML = '<i class="ri-search-eye-line"></i> Escanear Pasta Agora';
            }
        });
    }

    // 2. Parse Pasted Text
    if (btnParsePaste) {
        btnParsePaste.addEventListener('click', () => {
            const raw = pasteInput.value.trim();
            if (!raw) {
                alert('Por favor, cole sua lista de músicas na caixa de texto.');
                return;
            }

            const lines = raw.split('\n').map(l => l.trim()).filter(l => l.length > 0);
            const parsed = [];

            lines.forEach(line => {
                let title = '';
                let artist = '';
                let genero = selectGenero.value || '';
                let classif = selectClass.value || 'RLM';
                let price = selectPriceVal();

                if (line.includes('|')) {
                    const cols = line.split('|').map(c => c.trim());
                    title = cols[0] || '';
                    artist = cols[1] || '';
                    if (cols[2]) genero = cols[2];
                    if (cols[3]) classif = cols[3];
                    if (cols[4]) price = cols[4];
                } else if (line.includes('\t')) {
                    const cols = line.split('\t').map(c => c.trim());
                    title = cols[0] || '';
                    artist = cols[1] || '';
                    if (cols[2]) genero = cols[2];
                    if (cols[3]) classif = cols[3];
                    if (cols[4]) price = cols[4];
                } else if (line.includes(' - ')) {
                    const parts = line.split(' - ');
                    title = parts[0].trim();
                    artist = parts[1] ? parts[1].trim() : '';
                } else if (line.includes(';')) {
                    const cols = line.split(';').map(c => c.trim());
                    title = cols[0] || '';
                    artist = cols[1] || '';
                } else {
                    title = line;
                }

                const slugBase = (title + (artist ? ' - ' + artist : ''))
                    .toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');

                parsed.push({
                    title: title,
                    artist: artist,
                    genero: genero,
                    classificacao: classif,
                    price: price,
                    mp3_file: slugBase + '.mp3',
                    midi_file: slugBase + '.mid',
                    mp3_exists: false,
                    midi_exists: false,
                });
            });

            if (parsed.length > 0) {
                currentItems = parsed;
                renderTable();
                pasteInput.value = '';
                // Switch focus to table
                const tableCard = document.querySelector('.cm-batch-table-card');
                if (tableCard) tableCard.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    // 3. Start Batch Chunked Upload
    if (btnStart) {
        btnStart.addEventListener('click', async () => {
            if (!currentItems || currentItems.length === 0) return;

            const total = currentItems.length;
            if (!confirm(`Deseja iniciar o cadastro em lote de ${total} músicas para o mês ${selectMes.value}/${inputAno.value}?`)) {
                return;
            }

            btnStart.disabled = true;
            btnStart.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Processando...';
            progressWrapper.classList.remove('hidden');
            resultsSummary.classList.add('hidden');
            progressLog.innerHTML = '';

            const chunkSize = 15;
            const chunks = [];
            for (let i = 0; i < total; i += chunkSize) {
                chunks.push(currentItems.slice(i, i + chunkSize));
            }

            let totalProcessed = 0;
            const allCreated = [];
            const allErrors = [];

            for (let cIdx = 0; cIdx < chunks.length; cIdx++) {
                const chunk = chunks[cIdx];
                const chunkStart = cIdx * chunkSize + 1;
                const chunkEnd = Math.min((cIdx + 1) * chunkSize, total);

                progressLabel.textContent = `Processando lote ${cIdx + 1}/${chunks.length} (faixas ${chunkStart} a ${chunkEnd} de ${total})...`;

                try {
                    const formData = new FormData();
                    formData.append('action', 'centralmidi_process_batch_chunk');
                    formData.append('nonce', nonce);
                    formData.append('mes', selectMes.value);
                    formData.append('ano', inputAno.value);
                    formData.append('default_genero', selectGenero.value || '');
                    formData.append('default_classificacao', selectClass.value || 'RLM');
                    formData.append('default_price', selectPriceVal());

                    chunk.forEach((item, i) => {
                        formData.append(`items[${i}][title]`, item.title || '');
                        formData.append(`items[${i}][artist]`, item.artist || '');
                        formData.append(`items[${i}][genero]`, item.genero || '');
                        formData.append(`items[${i}][classificacao]`, item.classificacao || 'RLM');
                        formData.append(`items[${i}][price]`, item.price || '19.90');
                        formData.append(`items[${i}][mp3_file]`, item.mp3_file || '');
                        formData.append(`items[${i}][midi_file]`, item.midi_file || '');
                    });

                    const res = await fetch(ajaxUrl, { method: 'POST', body: formData });
                    const json = await res.json();

                    if (json.success) {
                        const data = json.data;
                        totalProcessed += data.processed || 0;
                        if (data.results) allCreated.push(...data.results);
                        if (data.errors && data.errors.length > 0) allErrors.push(...data.errors);

                        const logLine = document.createElement('div');
                        logLine.className = 'cm-log-success';
                        logLine.innerHTML = `<i class="ri-checkbox-circle-fill"></i> Lote ${cIdx + 1} concluído: ${data.processed} músicas criadas/atualizadas.`;
                        progressLog.appendChild(logLine);
                    } else {
                        const errLine = document.createElement('div');
                        errLine.className = 'cm-log-error';
                        errLine.innerHTML = `<i class="ri-error-warning-fill"></i> Erro no lote ${cIdx + 1}: ${json.data?.message || 'Falha desconhecida'}`;
                        progressLog.appendChild(errLine);
                        allErrors.push(`Lote ${cIdx + 1}: ${json.data?.message}`);
                    }
                } catch (err) {
                    console.error(err);
                    const errLine = document.createElement('div');
                    errLine.className = 'cm-log-error';
                    errLine.innerHTML = `<i class="ri-wifi-off-line"></i> Erro de conexão no lote ${cIdx + 1}.`;
                    progressLog.appendChild(errLine);
                }

                // Update Progress bar
                const percent = Math.round(((cIdx + 1) / chunks.length) * 100);
                progressBarFill.style.width = `${percent}%`;
                progressPercent.textContent = `${percent}%`;
            }

            // Finished!
            progressLabel.textContent = `Processamento de ${totalProcessed} músicas finalizado com sucesso!`;
            btnStart.disabled = false;
            btnStart.innerHTML = '<i class="ri-rocket-line"></i> Iniciar Cadastro em Lote';

            resultsSummary.classList.remove('hidden');
            summaryText.innerHTML = `Foram criados/atualizados <strong>${totalProcessed} produtos MIDI</strong> no catálogo para o mês de <strong>${selectMes.options[selectMes.selectedIndex].text} de ${inputAno.value}</strong>!`;
            resultsSummary.scrollIntoView({ behavior: 'smooth' });
        });
    }
});
