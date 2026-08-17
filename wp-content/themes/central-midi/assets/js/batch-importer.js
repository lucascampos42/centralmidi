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
    const inputArtist = document.getElementById('batch_artist');
    const inputGenero = document.getElementById('batch_genero');
    const folderDisplay = document.getElementById('cm-folder-display');

    // 1. Direct Multi-MP3 Upload
    const dropzone = document.getElementById('cm-mp3-dropzone');
    const fileInput = document.getElementById('cm-mp3-upload-input');
    const btnSelectFiles = document.getElementById('cm-btn-select-files');
    const uploadCountEl = document.getElementById('cm-upload-count');

    // 2. Folder Scanner
    const btnScan = document.getElementById('cm-btn-scan-folder');
    const scanStatus = document.getElementById('cm-scan-status');

    // 3. Paste
    const pasteInput = document.getElementById('cm-paste-input');
    const btnParsePaste = document.getElementById('cm-btn-parse-paste');

    // Table & Actions
    const tbody = document.getElementById('cm-batch-tbody');
    const totalCountEl = document.getElementById('cm-total-count');
    const btnAddRow = document.getElementById('cm-btn-add-row');
    const btnClearTable = document.getElementById('cm-btn-clear-table');
    const btnStart = document.getElementById('cm-btn-start-batch');

    // Progress
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
        const m = String(selectMes.value).padStart(2, '0');
        const a = inputAno.value;
        if (folderDisplay) folderDisplay.textContent = `midis/${a}${m}/`;
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

    function getDefaultArtist() {
        return inputArtist && inputArtist.value.trim() ? inputArtist.value.trim() : 'Padrão';
    }

    function getDefaultGenero() {
        return inputGenero && inputGenero.value.trim() ? inputGenero.value.trim() : 'Padrão';
    }

    function getDefaultPrice() {
        return inputPrice && inputPrice.value.trim() ? inputPrice.value.trim() : '';
    }

    function getDefaultClass() {
        return selectClass ? selectClass.value : '';
    }

    /**
     * Mirror of PHP's sanitize_file_name():
     * 1. Transliterate accented/special chars (ã→a, ç→c, etc.)
     * 2. Replace spaces with hyphens
     * 3. Remove any remaining non-alphanumeric chars except dot, hyphen, underscore
     * 4. Collapse consecutive hyphens, trim edge hyphens
     * This ensures the filename shown in the table matches exactly what PHP saves on disk.
     */
    function sanitizeFilename(name) {
        const ext  = name.match(/\.[^/.]+$/)?.[0] || '';
        let base   = name.slice(0, name.length - ext.length);

        // 1. Transliterate accented chars via Unicode normalization (NFD decomposes ã → a + combining ~)
        base = base.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

        // 2. Replace '&' with 'and', spaces with hyphens
        base = base.replace(/&/g, 'and').replace(/\s+/g, '-');

        // 3. Remove remaining invalid chars
        base = base.replace(/[^a-zA-Z0-9.\-_]/g, '');

        // 4. Collapse consecutive hyphens and trim edges
        base = base.replace(/-{2,}/g, '-').replace(/^-+|-+$/g, '');

        return base + ext;
    }

    function cleanFilenameToTitle(filename) {
        const base = filename.replace(/\.[^/.]+$/, '');
        let clean = base.replace(/[_]+/g, ' ').replace(/\s+/g, ' ').trim();
        return clean;
    }

    // Process selected File list
    function handleSelectedFiles(files) {
        if (!files || files.length === 0) return;

        const newItems = [];
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const filename     = file.name;                        // original: "The Realm Awakens.mp3"
            const safeFilename = sanitizeFilename(filename);       // disk name: "The-Realm-Awakens.mp3"
            const base         = safeFilename.replace(/\.[^/.]+$/, '');
            let title = cleanFilenameToTitle(filename);
            let artist = getDefaultArtist();

            // Detect if filename has "Artist - Title"
            if (title.includes(' - ')) {
                const parts = title.split(' - ');
                artist = parts[0].trim();
                title = parts[1].trim();
            }

            newItems.push({
                title: title,
                artist: artist,
                genero: getDefaultGenero(),
                classificacao: getDefaultClass(),
                price: getDefaultPrice(),
                mp3_file: safeFilename,   // "The-Realm-Awakens.mp3" — exact disk name
                mp3_exists: true,
                _file: file,
            });
        }

        currentItems = currentItems.concat(newItems);
        renderTable();

        if (uploadCountEl) {
            uploadCountEl.classList.remove('hidden');
            uploadCountEl.innerHTML = `<div class="cm-alert-success"><i class="ri-checkbox-circle-fill"></i> ${files.length} arquivos MP3 carregados com sucesso!</div>`;
        }

        const tableCard = document.querySelector('.cm-batch-table-card');
        if (tableCard) tableCard.scrollIntoView({ behavior: 'smooth' });
    }

    // Dropzone Events
    if (dropzone) {
        if (btnSelectFiles && fileInput) {
            btnSelectFiles.addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.click();
            });
            dropzone.addEventListener('click', () => {
                fileInput.click();
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                handleSelectedFiles(e.target.files);
                fileInput.value = '';
            });
        }

        ['dragenter', 'dragover'].forEach(evt => {
            dropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('drag-over');
            });
        });

        ['dragleave', 'drop'].forEach(evt => {
            dropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('drag-over');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            if (e.dataTransfer && e.dataTransfer.files) {
                handleSelectedFiles(e.dataTransfer.files);
            }
        });
    }

    // Render Table
    function renderTable() {
        if (!currentItems || currentItems.length === 0) {
            tbody.innerHTML = `
                <tr class="cm-empty-row">
                    <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                        <i class="ri-inbox-line" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                        Nenhuma música carregada. Faça upload de MP3s, escaneie a pasta ou cole uma lista.
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
                    <input type="text" class="cm-table-input item-artist" value="${escapeHtml(item.artist || 'Padrão')}" placeholder="Padrão" />
                </td>
                <td>
                    <input type="text" class="cm-table-input item-genero" value="${escapeHtml(item.genero || 'Padrão')}" placeholder="Padrão" />
                </td>
                <td>
                    <select class="cm-table-input item-class">
                        <option value="" ${!item.classificacao ? 'selected' : ''}>—</option>
                        <option value="RLM" ${item.classificacao === 'RLM' ? 'selected' : ''}>#RLM</option>
                        <option value="M" ${item.classificacao === 'M' ? 'selected' : ''}>#M</option>
                        <option value="L" ${item.classificacao === 'L' ? 'selected' : ''}>#L</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="cm-table-input item-price" value="${escapeHtml(item.price || '')}" placeholder="Sem Preço" style="text-align: right;" />
                </td>
                <td>
                    <div class="cm-file-cell">
                        <input type="text" class="cm-table-input item-mp3" value="${escapeHtml(item.mp3_file || '')}" placeholder="arquivo.mp3" />
                        ${item.mp3_exists ? '<span class="cm-status-tag cm-status-ok" title="Arquivo pronto"><i class="ri-checkbox-circle-fill"></i></span>' : ''}
                    </div>
                </td>
                <td style="text-align: center;">
                    <button type="button" class="cm-btn-remove-row" data-index="${idx}" title="Remover"><i class="ri-close-line"></i></button>
                </td>
            </tr>
        `).join('');
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
                artist: getDefaultArtist(),
                genero: getDefaultGenero(),
                classificacao: getDefaultClass(),
                price: getDefaultPrice(),
                mp3_file: '',
                mp3_exists: false,
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
            if (uploadCountEl) uploadCountEl.classList.add('hidden');
        });
    }

    // 2. Scanner Action
    if (btnScan) {
        btnScan.addEventListener('click', async () => {
            const mes = selectMes.value;
            const ano = inputAno.value;

            btnScan.disabled = true;
            btnScan.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Escaneando...';
            scanStatus.classList.remove('hidden');
            const mesPad = String(mes).padStart(2, '0');
            scanStatus.innerHTML = `<span style="color: var(--text-muted);"><i class="ri-loader-4-line ri-spin"></i> Lendo pasta midis/${ano}${mesPad}/...</span>`;

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
                            artist: it.artist || getDefaultArtist(),
                            genero: it.genero || getDefaultGenero(),
                            classificacao: getDefaultClass(),
                            price: getDefaultPrice(),
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

    // 3. Parse Pasted Text
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
                let title = line;
                let artist = getDefaultArtist();

                if (line.includes(' - ')) {
                    const parts = line.split(' - ');
                    artist = parts[0].trim();
                    title = parts[1].trim();
                }

                const slugBase = (title + (artist ? ' - ' + artist : ''))
                    .toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');

                parsed.push({
                    title: title,
                    artist: artist,
                    genero: getDefaultGenero(),
                    classificacao: getDefaultClass(),
                    price: getDefaultPrice(),
                    mp3_file: slugBase + '.mp3',
                    mp3_exists: false,
                });
            });

            if (parsed.length > 0) {
                currentItems = currentItems.concat(parsed);
                renderTable();
                pasteInput.value = '';
                const tableCard = document.querySelector('.cm-batch-table-card');
                if (tableCard) tableCard.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    // 4. Start Batch Chunked Upload
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
                    formData.append('default_genero', getDefaultGenero());
                    formData.append('default_artist', getDefaultArtist());
                    formData.append('default_classificacao', getDefaultClass());
                    formData.append('default_price', getDefaultPrice());
                    if (document.getElementById('batch_publicar') && document.getElementById('batch_publicar').checked) {
                        formData.append('publicar', '1');
                    }

                    chunk.forEach((item, i) => {
                        formData.append(`items[${i}][title]`, item.title || '');
                        formData.append(`items[${i}][artist]`, item.artist || getDefaultArtist());
                        formData.append(`items[${i}][genero]`, item.genero || getDefaultGenero());
                        formData.append(`items[${i}][classificacao]`, item.classificacao || '');
                        formData.append(`items[${i}][price]`, item.price || '');
                        formData.append(`items[${i}][mp3_file]`, item.mp3_file || '');

                        if (item._file) {
                            formData.append(`file_${i}`, item._file, item.mp3_file || item._file.name);
                        }
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
                        logLine.innerHTML = `<i class="ri-checkbox-circle-fill"></i> Lote ${cIdx + 1} concluído: ${data.processed} músicas cadastradas.`;
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
