<?php
/**
 * Template Name: Importador em Lote de MIDIs
 * Template Post Type: page
 */

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    auth_redirect();
    exit;
}

get_header();

$current_month = (int) date('n');
$current_year  = (int) date('Y');
$generos       = class_exists('CentralMidi_DB') ? CentralMidi_DB::get_generos() : array();
$artistas      = class_exists('CentralMidi_DB') ? CentralMidi_DB::get_artistas() : array();

$meses = array(
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
);

$batch_nonce = wp_create_nonce('centralmidi_batch_nonce');
?>

<div class="cm-hero-banner">
    <div class="cm-container">
        <span class="cm-badge"><i class="ri-upload-cloud-2-line"></i> Ferramenta Administrativa</span>
        <h1 class="cm-hero-title">Cadastro em Lote de MIDIs</h1>
        <p class="cm-hero-subtitle">
            Cadastre 50, 100 ou mais de 200 músicas de uma só vez escaneando as pastas no servidor ou colando sua lista.
        </p>
    </div>
</div>

<div class="cm-container cm-batch-page" id="cm-batch-app" data-nonce="<?php echo esc_attr($batch_nonce); ?>" data-ajaxurl="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">

    <!-- 1. Configurações Globais do Lote -->
    <div class="cm-box cm-box--pad cm-batch-config-card">
        <h3 class="cm-box-title"><i class="ri-settings-3-line cm-box-title-icon"></i> Configurações Gerais do Lote</h3>
        <p class="cm-box-text">Defina o mês de lançamento e os padrões que serão aplicados a todas as músicas do lote.</p>

        <div class="cm-batch-config-grid">
            <div class="cm-form-group">
                <label for="batch_mes"><i class="ri-calendar-event-line"></i> Mês de Lançamento</label>
                <select id="batch_mes" class="cm-input">
                    <?php foreach ($meses as $num => $nome) : ?>
                        <option value="<?php echo $num; ?>" <?php selected($current_month, $num); ?>>
                            <?php echo esc_html($nome); ?> (<?php echo $num; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cm-form-group">
                <label for="batch_ano"><i class="ri-calendar-line"></i> Ano de Lançamento</label>
                <input type="number" id="batch_ano" class="cm-input" value="<?php echo $current_year; ?>" min="2000" max="2099" />
            </div>

            <div class="cm-form-group">
                <label for="batch_price"><i class="ri-money-dollar-circle-line"></i> Preço Padrão</label>
                <input type="text" id="batch_price" class="cm-input" value="" placeholder="Sem Preço" />
            </div>

            <div class="cm-form-group">
                <label for="batch_classificacao"><i class="ri-award-line"></i> Classificação Padrão</label>
                <select id="batch_classificacao" class="cm-input">
                    <option value="" selected>— Nenhuma (Sem Marcação) —</option>
                    <option value="RLM">#RLM (Repertório Letra Melodia)</option>
                    <option value="M">#M (Melodia)</option>
                    <option value="L">#L (Letra)</option>
                </select>
            </div>

            <div class="cm-form-group">
                <label for="batch_artist"><i class="ri-user-star-line"></i> Artista Padrão</label>
                <input type="text" id="batch_artist" class="cm-input" value="Padrão" />
            </div>

            <div class="cm-form-group">
                <label for="batch_genero"><i class="ri-music-2-line"></i> Gênero Padrão</label>
                <input type="text" id="batch_genero" class="cm-input" value="Padrão" />
            </div>

            <div class="cm-form-group cm-batch-config-publish">
                <label for="batch_publicar"><i class="ri-eye-line"></i> Publicação</label>
                <label class="cm-checkbox">
                    <input type="checkbox" id="batch_publicar" value="1" />
                    <span><?php esc_html_e('Publicar imediatamente no site (se desmarcado, os MIDIs entram como "Em breve")', 'central-midi'); ?></span>
                </label>
            </div>
        </div>
    </div>

    <!-- 2. Modos de Entrada (Abas) -->
    <div class="cm-box cm-box--pad cm-batch-entry-card">
        <div class="cm-batch-tabs">
            <button type="button" class="cm-batch-tab active" data-tab="upload">
                <i class="ri-upload-cloud-line"></i> 1. Upload Direto de MP3s pelo Navegador
            </button>
            <button type="button" class="cm-batch-tab" data-tab="scanner">
                <i class="ri-folder-music-line"></i> 2. Escanear Pasta FTP no Servidor
            </button>
            <button type="button" class="cm-batch-tab" data-tab="paste">
                <i class="ri-file-list-3-line"></i> 3. Colar Lista de Músicas
            </button>
        </div>

        <!-- Conteúdo Aba 1: Upload Direto de MP3s -->
        <div class="cm-tab-content active" id="tab-upload">
            <div class="cm-dropzone" id="cm-mp3-dropzone">
                <input type="file" id="cm-mp3-upload-input" multiple accept=".mp3,audio/mpeg,audio/mp3" style="display: none;" />
                <i class="ri-file-music-line cm-dropzone-icon"></i>
                <h3>Arraste seus arquivos MP3 aqui ou clique para selecionar</h3>
                <p>Selecione dezenas ou centenas de arquivos MP3 de uma só vez do seu computador. O sistema extrairá o nome de cada música automaticamente para o lote.</p>
                <button type="button" id="cm-btn-select-files" class="cm-btn cm-btn-primary">
                    <i class="ri-folder-upload-line"></i> Selecionar Arquivos MP3 do Computador
                </button>
                <div id="cm-upload-count" class="cm-upload-count hidden"></div>
            </div>
        </div>

        <!-- Conteúdo Aba 2: Scanner de Pasta FTP -->
        <div class="cm-tab-content" id="tab-scanner">
            <div class="cm-scanner-box">
                <div class="cm-scanner-info">
                    <?php $default_folder = $current_year . str_pad($current_month, 2, '0', STR_PAD_LEFT); ?>
                    <h4><i class="ri-folder-info-line"></i> Pasta no Servidor: <code id="cm-folder-display">midis/<?php echo esc_html($default_folder); ?>/</code></h4>
                    <p>Suba seus arquivos <code>.mp3</code> e <code>.mid</code> via FTP para esta pasta. O sistema identificará e pareará automaticamente todos os arquivos.</p>
                </div>
                <button type="button" id="cm-btn-scan-folder" class="cm-btn cm-btn-primary">
                    <i class="ri-search-eye-line"></i> Escanear Pasta Agora
                </button>
            </div>
            <div id="cm-scan-status" class="cm-scan-status hidden"></div>
        </div>

        <!-- Conteúdo Aba 3: Colar Lista -->
        <div class="cm-tab-content" id="tab-paste">
            <div class="cm-paste-box">
                <label for="cm-paste-input">Cole sua lista de faixas (1 por linha):</label>
                <textarea id="cm-paste-input" class="cm-textarea" rows="6" placeholder="Exemplo:&#10;Dormi na Praça - Bruno e Marrone&#10;Infiel - Marilia Mendonca&#10;Evidências - Chitãozinho & Xororó"></textarea>
                <div class="cm-paste-actions">
                    <button type="button" id="cm-btn-parse-paste" class="cm-btn cm-btn-primary">
                        <i class="ri-play-list-add-line"></i> Carregar Músicas na Tabela
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Grade de Pré-Visualização e Ajustes -->
    <div class="cm-box cm-box--pad cm-batch-table-card">
        <div class="cm-batch-table-header">
            <div>
                <h3 class="cm-box-title"><i class="ri-list-check-2 cm-box-title-icon"></i> Pré-Visualização do Lote (<span id="cm-total-count">0</span> faixas)</h3>
                <p class="cm-box-text">Revise os dados antes de iniciar o cadastro. Você pode editar qualquer campo diretamente na tabela.</p>
            </div>
            <div class="cm-batch-table-actions">
                <button type="button" id="cm-btn-add-row" class="cm-btn cm-btn-outline cm-btn-sm">
                    <i class="ri-add-line"></i> Nova Linha
                </button>
                <button type="button" id="cm-btn-clear-table" class="cm-btn cm-btn-outline cm-btn-sm">
                    <i class="ri-delete-bin-line"></i> Limpar Tudo
                </button>
            </div>
        </div>

        <div class="cm-table-responsive">
            <table class="cm-batch-table" id="cm-batch-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Título da Música</th>
                        <th>Artista / Banda</th>
                        <th>Gênero</th>
                        <th style="width: 100px;">Classif.</th>
                        <th style="width: 100px;">Preço</th>
                        <th>Arquivo MP3</th>
                        <th>Arquivo MIDI</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody id="cm-batch-tbody">
                    <tr class="cm-empty-row">
                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="ri-inbox-line" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                            Nenhuma música carregada. Escaneie a pasta do servidor ou cole sua lista acima.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 4. Painel de Execução & Barra de Progresso -->
    <div class="cm-box cm-box--pad cm-batch-execute-card">
        <div class="cm-execute-header">
            <div>
                <h3>Pronto para Cadastrar?</h3>
                <p class="cm-box-text">O processamento será executado em lotes contínuos com tratamento antiduplicação e auto-criação de artistas.</p>
            </div>
            <button type="button" id="cm-btn-start-batch" class="cm-btn cm-btn-primary cm-btn-lg" disabled>
                <i class="ri-rocket-line"></i> Iniciar Cadastro em Lote
            </button>
        </div>

        <!-- Barra de Progresso -->
        <div id="cm-progress-wrapper" class="cm-progress-wrapper hidden">
            <div class="cm-progress-info">
                <span id="cm-progress-label">Iniciando processamento...</span>
                <span id="cm-progress-percent">0%</span>
            </div>
            <div class="cm-progress-track">
                <div id="cm-progress-bar-fill" class="cm-progress-bar-fill"></div>
            </div>
            <div id="cm-progress-log" class="cm-progress-log"></div>
        </div>

        <!-- Relatório Final -->
        <div id="cm-results-summary" class="cm-results-summary hidden">
            <div class="cm-success-badge"><i class="ri-checkbox-circle-fill"></i> Lote Concluído com Sucesso!</div>
            <p id="cm-summary-text"></p>
            <div class="cm-summary-actions">
                <a href="<?php echo esc_url(home_url('/midis/')); ?>" class="cm-btn cm-btn-primary" target="_blank">
                    <i class="ri-search-line"></i> Ver no Catálogo de MIDIs
                </a>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="cm-btn cm-btn-outline" target="_blank">
                    <i class="ri-home-4-line"></i> Ver na Página Inicial
                </a>
            </div>
        </div>
    </div>

</div>

<?php get_footer(); ?>
