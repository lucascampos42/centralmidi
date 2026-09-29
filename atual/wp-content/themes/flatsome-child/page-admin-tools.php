<?php
/**
 * Template Name: Ferramentas Admin Vue
 */
if (!is_user_logged_in() || !current_user_can('manage_options')) {
  wp_redirect(wp_login_url(get_permalink()));
  exit;
}
get_header();
$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('pa_admin_nonce');
?>

<main id="main" class="admin-tools-page">
  <div id="app" v-cloak>
    <div class="shop-container">
      <!-- Toast Notification -->
      <transition name="toast">
        <div v-if="toast.show" :class="['admin-toast', toast.type]">
          {{ toast.message }}
        </div>
      </transition>

      <div class="admin-header">
        <div class="admin-header-top">
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-number">{{ stats.total }}</div>
              <div class="stat-label">Total Produtos</div>
            </div>
            <div class="stat-card">
              <div class="stat-number">{{ stats.withDemo }}</div>
              <div class="stat-label">Com Demo</div>
            </div>
          </div>

          <div class="actions-row">
            <button @click="exportCSV" class="button" :disabled="isExporting">
              {{ isExporting ? '⏳ Exportando...' : '📥 Exportar CSV' }}
            </button>
            <button @click="checkDuplicates" class="button" :disabled="isCheckingDupes">
              {{ isCheckingDupes ? '⏳ Verificando...' : '🔍 Verificar Duplicidade' }}
            </button>
          </div>

          <div class="prod-filters-row">
            <select v-model="filters.filtro_artista" class="filtro-select">
              <option value="">Artista: Todos</option>
              <option v-for="t in taxonomies.product_cat" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
            <select v-model="filters.filtro_genero" class="filtro-select">
              <option value="">Gênero: Todos</option>
              <option v-for="t in taxonomies.genero_musical" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
            <select v-model="filters.filtro_mes" class="filtro-select">
              <option value="">Mês: Todos</option>
              <option v-for="t in taxonomies.mes_de_lancamento" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
            <select v-model="filters.filtro_tipo" class="filtro-select">
              <option value="">Tipo: Todos</option>
              <option v-for="t in taxonomies.tipo" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
            <select v-model="filters.filtro_demo" class="filtro-select">
              <option value="all">Demo: Todos</option>
              <option value="has">Demo: Com demo</option>
              <option value="none">Demo: Sem demo</option>
            </select>
            <button @click="clearFilters" class="button button-sm" title="Limpar Filtros">✕</button>
          </div>
        </div>

        <div class="prod-search-row">
          <div class="search-group" style="width: 25%;">
            <span class="search-icon">🔗</span>
            <input type="text" v-model="filters.filtro_demo_search" class="search-input" placeholder="Buscar URL demo..." @keyup.enter="loadProducts(1)">
          </div>
          
          <div class="search-group grow">
            <span class="search-icon">🎵</span>
            <input type="text" v-model="filters.search" class="search-input" placeholder="Música..." @keyup.enter="loadProducts(1)">
          </div>

          <div class="search-group" style="width: 160px;">
            <span class="search-icon">#</span>
            <input type="number" v-model="filters.search_id" class="search-input" placeholder="ID" @keyup.enter="loadProducts(1)">
          </div>

          <button @click="loadProducts(1)" class="button primary load-btn" :class="{loading: isLoading}">
            <span>📦 Carregar</span>
            <span class="total-badge">{{ totalProducts }}</span>
          </button>
        </div>
      </div>

      <!-- ... -->

      <div v-if="products.length" class="results-table-wrap" style="margin-top:8px; position: relative;">
        <!-- Overlay de Carregamento da Tabela -->
        <div v-if="isLoading" class="table-loading-overlay">
          <div class="spinner"></div>
          <span>Carregando dados...</span>
        </div>
        
        <table class="results-table" :class="{ 'is-loading': isLoading }">
          <thead>
            <tr>
              <th class="col-id">ID</th>
              <th class="col-artista">Artista</th>
              <th class="col-musica">Música</th>
              <th class="col-rlm">Classificação</th>
              <th class="col-tipo">Tipo</th>
              <th class="col-preco">Preço</th>
              <th class="col-genero">Gênero</th>
              <th class="col-mes">Mês</th>
              <th class="col-demo">Demo</th>
              <th class="col-actions">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in products" :key="p.id">
              <td class="col-id" style="font-size: 11px;">{{ p.id }}</td>
              <td>
                <div class="artista-picker">
                  <input type="text" class="prod-input" v-model="p.artista_busca"
                         @input="buscarArtista(p)" @keydown.escape="p.artista_opcoes = []"
                         placeholder="artista" :disabled="p.isSaving">
                  <ul v-if="p.artista_opcoes && p.artista_opcoes.length" class="artista-opcoes">
                    <li v-for="o in p.artista_opcoes" :key="o.id" @click="escolherArtista(p, o)">
                      {{ o.name }} <em v-if="o.count">{{ o.count }}</em>
                    </li>
                  </ul>
                  <button v-if="p.artista_id" class="artista-limpar" title="Remover artista"
                          @click="limparArtista(p)" :disabled="p.isSaving">&times;</button>
                </div>
              </td>
              <td class="col-musica">
                <a href="#" @click.prevent="openEditModal(p, 'post_title')" :class="{ 'is-disabled': p.isSaving }">{{ p.title }}</a>
                <span v-if="p.isSaving" class="save-spinner"></span>
              </td>
              <td>
                <select v-model="p.rlm" class="prod-select" @change="saveProduct(p, 'rlm')" :disabled="p.isSaving">
                  <option value="">— Nenhum —</option>
                  <option value="M">Somente Melodia</option>
                  <option value="L">Somente Letra sincronizada</option>
                  <option value="RLM">Melodia e Letra sincronizada</option>
                </select>
              </td>
              <td>
                <select v-model="p.tipo_ids[0]" class="prod-select" @change="saveProduct(p, 'tipo')" :disabled="p.isSaving">
                  <option value="">— Nenhum —</option>
                  <option v-for="t in taxonomies.tipo" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
              </td>
              <td>
                <input type="number" step="0.01" v-model="p.preco" class="prod-input" style="width: 80px;" @change="saveProduct(p, '_regular_price')" :disabled="p.isSaving">
              </td>
              <td>
                <select v-model="p.genero_ids[0]" class="prod-select" @change="saveProduct(p, 'genero_musical')" :disabled="p.isSaving">
                  <option value="">— Nenhum —</option>
                  <option v-for="t in taxonomies.genero_musical" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
              </td>
              <td>
                <select v-model="p.mes_ids[0]" class="prod-select" @change="saveProduct(p, 'mes_de_lancamento')" :disabled="p.isSaving">
                  <option value="">— Nenhum —</option>
                  <option v-for="t in taxonomies.mes_de_lancamento" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
              </td>
              <td>
                <a v-if="p.url_demo" href="#" class="demo-link" @click.prevent="openEditModal(p, 'url_demo')" :class="{ 'is-disabled': p.isSaving }">{{ p.url_demo }}</a>
                <span v-else style="color: #6b7280; cursor: pointer; font-size: 11px;" @click="openEditModal(p, 'url_demo')" :class="{ 'is-disabled': p.isSaving }">Sem demo</span>
              </td>
              <td>
                <div style="display: flex; gap: 6px; align-items: center;">
                  <button v-if="p.url_demo" class="btn-play" :class="{playing: currentPlayingId === p.id}" @click="togglePlay(p)">
                    {{ currentPlayingId === p.id ? '⏸' : '▶' }}
                  </button>
                  <a :href="p.edit_url" target="_blank" class="button button-sm" title="Editar no painel do WordPress">✏️ WP</a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="products.length" class="prod-pagination" style="margin-top:8px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <button @click="loadProducts(1)" class="button" :disabled="currentPage <= 1">««</button>
        <button @click="loadProducts(currentPage - 1)" class="button" :disabled="currentPage <= 1">« Anterior</button>
        <span>Página {{ currentPage }} de {{ totalPages }}</span>
        <button @click="loadProducts(currentPage + 1)" class="button" :disabled="currentPage >= totalPages">Próxima »</button>
        <button @click="loadProducts(totalPages)" class="button" :disabled="currentPage >= totalPages">»»</button>
        
        <label style="margin-left:auto">Por página:
          <select v-model="limit" @change="loadProducts(1)" id="prod-limit">
            <option v-for="n in [5, 10, 15, 20, 50, 100]" :key="n" :value="n">{{ n }}</option>
          </select>
        </label>
      </div>

      <!-- Modal de Edição -->
      <div v-if="editingProduct" class="modal-overlay" @click.self="closeEditModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ editModalTitle }}</h3>
            <button @click="closeEditModal" class="modal-close">&times;</button>
          </div>
          <div class="modal-body">
            <label>{{ editModalLabel }}</label>
            <textarea v-model="editValue" class="prod-input" rows="3" ref="modalInput" style="min-height: 100px; font-size: 14px;"></textarea>
          </div>
          <div class="modal-footer">
            <button @click="closeEditModal" class="button">Cancelar</button>
            <button @click="saveEditModal" class="button primary" :disabled="isSavingModal">
              {{ isSavingModal ? 'Salvando...' : 'Salvar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<style>
[v-cloak] { display: none; }
/* ... restante do style continua igual ... */
body { background-color: #f9fafb; color: #111827; }
.admin-tools-page { padding: 20px 10px; max-width: 98vw; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }

.admin-header { display: flex; flex-direction: column; gap: 4px; margin-bottom: 4px; }
.admin-header-top { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.admin-header .stats-grid { display: flex; gap: 4px; }
.admin-header .stat-card { min-width: 80px; padding: 4px 8px; border-radius: 4px; text-align: center; background: #f9fafb; border: 1px solid #e5e7eb; }
.admin-header .stat-number { font-size: 16px; font-weight: 700; color: #69A24C; line-height: 1.1; }
.admin-header .stat-label { font-size: 8px; color: #6b7280; text-transform: uppercase; margin-top: 1px; white-space: nowrap; }
.admin-header .actions-row { display: flex; gap: 4px; }
.admin-header .admin-controls-flex { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-top: 4px; }
.admin-header .prod-filters-row { display: flex; gap: 4px; align-items: center; flex-shrink: 0; }
.admin-header .prod-search-row { display: flex; gap: 8px; align-items: center; margin-top: 2px; }

.search-group { display: flex; align-items: center; background: #fff; border-bottom: 2px solid #e5e7eb; height: 32px; transition: all 0.2s; }
.search-group:focus-within { border-color: #69A24C; background: #fff; }
.search-group.grow { flex: 1; }
.search-group.small { width: 80px; }
.search-icon { padding: 0 8px; font-size: 14px; opacity: 0.6; display: flex; align-items: center; }
.search-input { border: none !important; background: transparent !important; padding: 0 !important; font-size: 13px !important; width: 100%; height: 100%; box-shadow: none !important; color: #111827 !important; line-height: 32px !important; display: block !important; margin-bottom: 0 !important; }
.search-input::placeholder { color: #9ca3af; }
.search-input:focus { outline: none !important; }

.load-btn { height: 28px; padding: 0 12px; gap: 8px; }
.total-badge { background: rgba(255,255,255,0.2); padding: 1px 6px; border-radius: 10px; font-size: 10px; font-weight: 600; }

.admin-header .filtro-select { padding: 2px 6px; font-size: 11px; min-width: 0; width: auto; max-width: 120px; border: 1px solid #d1d5db; border-radius: 3px; background: #fff; color: #111827; height: 26px; }
.admin-header .filtro-select:focus { outline: none; border-color: #69A24C; box-shadow: 0 0 0 2px rgba(105, 162, 76, 0.2); }
.v-divider { width: 1px; height: 20px; background: #e5e7eb; margin: 0 4px; }

.button { display: inline-flex; align-items: center; justify-content: center; height: 26px; padding: 0 10px; font-size: 11px; font-weight: 500; border-radius: 4px; border: 1px solid #d1d5db; background: #fff; color: #374151; cursor: pointer; line-height: 1; transition: all 0.15s; }
.button:hover { background: #f3f4f6; }
.button.primary { background: #69A24C; color: #fff; border-color: #69A24C; }
.button.primary:hover { background: #5a8a41; }
.button-sm { height: 22px; padding: 2px 8px; font-size: 10px; }
.button:disabled { opacity: 0.5; pointer-events: none; }
.result-message { padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; display:none; font-weight: 500; font-size: 13px; }
.result-message.success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.result-message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.result-message.info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }

.results-table-wrap { overflow-x: auto; background: #ffffff; border-top: 1px solid #e5e7eb; }
.results-table { width: 100%; border-collapse: collapse; }
.results-table th, .results-table td { padding: 4px 4px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 12px; white-space: nowrap; color: #374151; }
.results-table th { background: #f9fafb; color: #111827; position: sticky; top: 0; font-weight: 600; text-transform: uppercase; font-size: 11px; z-index: 10; border-bottom: 2px solid #e5e7eb; padding: 6px 4px; }
.results-table tr:hover { background-color: #f9fafb; }
.results-table a { color: #2563eb; text-decoration: none; font-weight: 500; }
.results-table a:hover { text-decoration: underline; }
.button.loading { position: relative; pointer-events: none; opacity: 0.7; }
.product-tools-bar { display: flex; gap: 8px; align-items: center; margin-bottom: 10px; flex-wrap: wrap; }

.prod-select { width: 100%; min-width: 90px; padding: 4px 6px; border: 1px solid #d1d5db; border-radius: 4px; background: #ffffff; color: #111827; font-size: 11px; }
.prod-input { width: 100%; min-width: 60px; padding: 2px 6px; border: 1px solid #d1d5db; border-radius: 4px; background: #ffffff; color: #111827; font-size: 11px; transition: all 0.2s; height: 24px; }
.prod-input:focus, .prod-select:focus { outline: none; border-color: #69A24C; box-shadow: 0 0 0 2px rgba(105, 162, 76, 0.2); }

.artista-picker { position: relative; }
.artista-picker .prod-input { padding-right: 20px; }
.artista-limpar { position: absolute; right: 3px; top: 4px; width: 16px; height: 16px; line-height: 1; border: 0; padding: 0; background: #e5e7eb; color: #6b7280; border-radius: 50%; cursor: pointer; font-size: 12px; }
.artista-limpar:hover { background: #d1d5db; color: #374151; }
.artista-opcoes { position: absolute; z-index: 60; left: 0; right: 0; top: 100%; margin: 0; padding: 0; list-style: none; background: #ffffff; border: 1px solid #d1d5db; border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,.15); max-height: 220px; overflow-y: auto; }
.artista-opcoes li { padding: 4px 6px; font-size: 11px; cursor: pointer; white-space: nowrap; color: #111827; }
.artista-opcoes li:hover { background: #f3f4f6; }
.artista-opcoes em { color: #9ca3af; font-style: normal; }
#prod-search { padding: 2px 8px; border: 1px solid #d1d5db; border-radius: 4px; background: #ffffff; color: #111827; width: 220px; font-size: 11px; height: 26px; }
#prod-search-id { padding: 2px 8px; border: 1px solid #d1d5db; border-radius: 4px; background: #ffffff; color: #111827; width: 60px; font-size: 11px; height: 26px; }
#prod-search:focus, #prod-search-id:focus { outline: none; border-color: #69A24C; box-shadow: 0 0 0 2px rgba(105, 162, 76, 0.2); }
#prod-limit { padding: 2px 6px; border: 1px solid #d1d5db; border-radius: 4px; background: #ffffff; color: #111827; font-size: 11px; font-weight: 500; height: 26px; }

.col-id { width: 40px; }
.col-artista { width: 150px; }
.results-table td.col-musica, .results-table th.col-musica { max-width: 300px; width: 300px; white-space: normal; word-break: break-word; overflow-wrap: break-word; }
.col-rlm { width: 120px; }
.col-tipo { width: 110px; }
.col-preco { width: 90px; }
.col-genero { width: 120px; }
.col-mes { width: 50px; }
.col-demo { min-width: 220px; }
.col-actions { width: 120px; }
.demo-link { color: #2563eb; font-size: 12px; text-decoration: underline; word-break: break-all; white-space: normal; line-height: 1.4; display: block; }
.btn-play { background: #2563eb; color: #fff; border: none; border-radius: 4px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 10px; cursor: pointer; transition: all 0.15s; }
.btn-play:hover { background: #1d4ed8; transform: scale(1.05); }
.btn-play.playing { background: #dc2626; }
.btn-play.playing:hover { background: #b91c1c; }
.btn-play.loading { opacity: 0.5; pointer-events: none; }
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(4px); z-index: 1000; display: flex; align-items: center; justify-content: center; }
.modal-content { background: #ffffff; border-radius: 12px; padding: 32px; min-width: 900px; max-width: 95vw; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e5e7eb; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 16px; }
.modal-header h3 { color: #111827; margin: 0; font-size: 18px; font-weight: 600; }
.modal-close { background: none; border: none; color: #9ca3af; font-size: 28px; cursor: pointer; padding: 0; line-height: 1; transition: color 0.2s; }
.modal-close:hover { color: #111827; }
.modal-body { margin-bottom: 24px; }
.modal-body label { display: block; color: #4b5563; margin-bottom: 8px; font-size: 14px; font-weight: 500; }
.modal-footer { display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid #e5e7eb; padding-top: 20px; }
#modal-field-input { width: 100%; padding: 12px 16px; font-size: 15px; box-sizing: border-box; resize: vertical; min-height: 80px; font-family: inherit; line-height: 1.5; border: 1px solid #d1d5db; border-radius: 8px; color: #111827; background: #f9fafb; transition: all 0.2s; }
#modal-field-input:focus { outline: none; border-color: #69A24C; background: #ffffff; box-shadow: 0 0 0 3px rgba(105, 162, 76, 0.15); }
#prod-info { color: #4b5563; margin-left: 12px; font-weight: 500; font-size: 14px; }
#prod-page-info { color: #4b5563; font-weight: 500; font-size: 14px; }
label { color: #4b5563; font-weight: 500; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }

/* Toast Notification */
.admin-toast { position: fixed; top: 35px; right: 20px; padding: 12px 24px; border-radius: 8px; color: #fff; font-weight: 600; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-size: 14px; }
.admin-toast.success { background: #10b981; }
.admin-toast.error { background: #ef4444; }
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter, .toast-leave-to { opacity: 0; transform: translateY(-20px); }

/* Table Loading Overlay */
.table-loading-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.7); z-index: 50; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; font-weight: 600; color: #374151; }
.spinner { width: 32px; height: 32px; border: 4px solid #f3f3f3; border-top: 4px solid #69A24C; border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* Save Spinner in Table */
.save-spinner { display: inline-block; width: 12px; height: 12px; border: 2px solid #e5e7eb; border-top: 2px solid #69A24C; border-radius: 50%; animation: spin 1s linear infinite; margin-left: 6px; vertical-align: middle; }
.is-disabled { opacity: 0.5; pointer-events: none; }
.results-table.is-loading { opacity: 0.5; pointer-events: none; }
</style>

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script>
const ajaxUrl = '<?php echo $ajax_url; ?>';
const nonce = '<?php echo $nonce; ?>';

new Vue({
  el: '#app',
  data: {
    stats: { total: 0, withDemo: 0 },
    products: [],
    toast: { show: false, message: '', type: 'success' },
    taxonomies: { product_cat: [], genero_musical: [], mes_de_lancamento: [], tipo: [] },
    filters: {
      filtro_artista: '',
      filtro_genero: '',
      filtro_mes: '',
      filtro_tipo: '',
      filtro_demo: 'all',
      filtro_demo_search: '',
      search: '',
      search_id: ''
    },
    limit: 10,
    currentPage: 1,
    totalPages: 1,
    totalProducts: 0,
    isLoading: false,
    isExporting: false,
    isCheckingDupes: false,
    editingProduct: null,
    editField: '',
    editValue: '',
    isSavingModal: false,
    currentPlayingId: null,
    audio: null
  },
  computed: {
    editModalTitle() {
      const labels = { 'post_title': 'Editar Música', 'url_demo': 'Editar URL Demo' };
      return labels[this.editField] || 'Editar';
    },
    editModalLabel() {
      const labels = { 'post_title': 'Música (título do produto)', 'url_demo': 'URL da demonstração' };
      return labels[this.editField] || this.editField;
    }
  },
  watch: {
    filters: {
      handler() { this.debouncedLoad(); },
      deep: true
    }
  },
  created() {
    this.debouncedLoad = this.debounce(() => this.loadProducts(1), 300);
    this.init();
  },
  methods: {
    async init() {
      await Promise.all([
        this.loadTaxonomy('product_cat'),
        this.loadTaxonomy('genero_musical'),
        this.loadTaxonomy('mes_de_lancamento'),
        this.loadTaxonomy('tipo'),
        this.loadStats()
      ]);
      this.loadProducts(1);
    },
    async callApi(action, extra = {}) {
      const formData = new FormData();
      formData.append('action', action);
      formData.append('_wpnonce', nonce);
      Object.keys(extra).forEach(k => formData.append(k, extra[k]));
      const response = await fetch(ajaxUrl, { method: 'POST', body: formData });
      return response.json();
    },
    async loadStats() {
      const data = await this.callApi('pa_admin_stats');
      if (data.success) this.stats = data.data.results.stats;
    },
    async loadTaxonomy(tax) {
      const data = await this.callApi('pa_admin_get_taxonomy_terms', { taxonomy: tax });
      if (data.success) this.taxonomies[tax] = data.data;
    },
    async loadProducts(page) {
      this.isLoading = true;
      this.currentPage = page;
      const params = {
        page: this.currentPage,
        limit: this.limit,
        ...this.filters
      };
      const data = await this.callApi('pa_admin_list_products', params);
      if (data.success) {
        const d = data.data;
        this.products = d.results.map(p => ({
          ...p,
          // ids vindos do servidor. Adivinhar pelo nome apagava o termo em
          // produtos com mais de um, como o 1146331.
          artista_id: (p.artista_ids && p.artista_ids.length) ? p.artista_ids[0] : '',
          artista_ids: p.artista_ids || [],
          artista_busca: (p.artista && p.artista.length) ? p.artista[0] : '',
          artista_opcoes: [],
          isSaving: false
        }));
        this.totalPages = d.total_pages;
        this.totalProducts = d.total;
      }
      this.isLoading = false;
    },
    async saveProduct(p, field) {
      p.isSaving = true;
      let value;
      // Escolher um valor preserva os termos que existem mas nao aparecem no
      // select; escolher "Nenhum" limpa de proposito. O escolhido vai para a
      // FRENTE porque o backend trata o primeiro termo como o principal
      // (meta _centralmidi_artista e coluna artista_id da tabela).
      const mesclar = (atuais, escolhido) => {
        if (escolhido === '' || escolhido === null || escolhido === undefined) return [];
        return [escolhido].concat((atuais || []).filter(id => String(id) !== String(escolhido)));
      };

      if (field === 'product_cat') value = mesclar(p.artista_ids, p.artista_id);
      else if (field === 'genero_musical') value = mesclar(p.genero_ids, p.genero_ids[0]);
      else if (field === 'mes_de_lancamento') value = mesclar(p.mes_ids, p.mes_ids[0]);
      else if (field === 'tipo') value = mesclar(p.tipo_ids, p.tipo_ids[0]);
      else value = p[field === '_regular_price' ? 'preco' : (field === 'post_title' ? 'title' : field)];

      try {
        const data = await this.callApi('pa_admin_save_product', {
          product_id: p.id,
          field: field,
          value: value
        });
        
        if (data && data.success) {
          await this.sincronizarLinha(p, field);
          this.showToast('Salvo com sucesso!');
          return true;
        } else {
          this.showToast('Erro ao salvar!', 'error');
          return false;
        }
      } catch (e) {
        this.showToast('Erro de conexão!', 'error');
        return false;
      } finally {
        p.isSaving = false;
      }
    },
    // Relê a linha gravada para a tela refletir exatamente o que o backend
    // gravou (ordem dos artistas, termo principal, ids das taxonomias).
    async sincronizarLinha(p, field) {
      let data;
      try {
        data = await this.callApi('pa_admin_list_products', { search_id: p.id, page: 1, limit: 1 });
      } catch (e) {
        return;
      }
      if (!data || !data.success) return;
      const row = (data.data && data.data.results && data.data.results[0]) || null;
      if (!row || parseInt(row.id, 10) !== parseInt(p.id, 10)) return;

      if (field === 'product_cat') {
        p.artista_ids = row.artista_ids || [];
        p.artista = row.artista || [];
        p.artista_id = p.artista_ids.length ? p.artista_ids[0] : '';
        p.artista_busca = (p.artista.length) ? p.artista[0] : '';
      } else if (field === 'genero_musical') {
        p.genero_ids = row.genero_ids || [];
      } else if (field === 'mes_de_lancamento') {
        p.mes_ids = row.mes_ids || [];
      } else if (field === 'tipo') {
        p.tipo_ids = row.tipo_ids || [];
      } else if (field === 'rlm') {
        p.rlm = row.rlm;
      }
    },
    buscarArtista(p) {
      clearTimeout(p._timerArtista);
      const termo = (p.artista_busca || '').trim();
      p.artista_opcoes = [];
      if (termo.length < 2) return;
      p._timerArtista = setTimeout(async () => {
        const data = await this.callApi('pa_admin_buscar_artistas', { termo });
        if (data && data.success) p.artista_opcoes = data.data;
      }, 300);
    },
    async escolherArtista(p, o) {
      p.artista_id = o.id;
      p.artista_busca = o.name;
      p.artista_opcoes = [];
      await this.saveProduct(p, 'product_cat');
    },
    async limparArtista(p) {
      p.artista_id = '';
      p.artista_busca = '';
      p.artista_opcoes = [];
      await this.saveProduct(p, 'product_cat');
    },
    showToast(message, type = 'success') {
      this.toast.message = message;
      this.toast.type = type;
      this.toast.show = true;
      setTimeout(() => { this.toast.show = false; }, 3000);
    },
    openEditModal(p, field) {
      this.editingProduct = p;
      this.editField = field;
      this.editValue = field === 'post_title' ? p.title : p.url_demo;
      this.$nextTick(() => {
        if (this.$refs.modalInput) {
          this.$refs.modalInput.focus();
          this.$refs.modalInput.select();
        }
      });
    },
    closeEditModal() {
      this.editingProduct = null;
    },
    async saveEditModal() {
      this.isSavingModal = true;
      const p = this.editingProduct;
      const originalValue = this.editField === 'post_title' ? p.title : p.url_demo;
      
      // Atualiza temporariamente o objeto para o saveProduct pegar o valor correto
      if (this.editField === 'post_title') p.title = this.editValue;
      else p.url_demo = this.editValue;

      const success = await this.saveProduct(p, this.editField === 'post_title' ? 'post_title' : 'url_demo');
      
      if (!success) {
        // Reverte se falhar
        if (this.editField === 'post_title') p.title = originalValue;
        else p.url_demo = originalValue;
        alert('Erro ao salvar. Verifique as permissões.');
      }

      this.isSavingModal = false;
      this.closeEditModal();
    },
    clearFilters() {
      Object.keys(this.filters).forEach(k => this.filters[k] = k === 'filtro_demo' ? 'all' : '');
      this.loadProducts(1);
    },
    async exportCSV() {
      this.isExporting = true;
      try {
        // Uma requisicao so: o PHP faz o streaming do CSV inteiro. Antes o
        // navegador batia 819 vezes para percorrer 81.852 produtos.
        const fd = new FormData();
        fd.append('action', 'pa_admin_export_csv');
        fd.append('_wpnonce', nonce);
        Object.keys(this.filters).forEach(k => {
          const v = this.filters[k];
          if (Array.isArray(v)) v.forEach(x => fd.append(k + '[]', x));
          else fd.append(k, v);
        });

        const resp = await fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        const blob = await resp.blob();
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'centralmidi-produtos.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(a.href);
        this.showToast('CSV exportado!');
      } catch (e) {
        this.showToast('Erro ao exportar!', 'error');
      } finally {
        this.isExporting = false;
      }
    },
    async checkDuplicates() {
      this.isCheckingDupes = true;
      const data = await this.callApi('pa_admin_check_duplicates');
      if (data.success && data.data.length) {
        let csv = 'Tipo;Valor;ID;Produto;URL Demo\n';
        data.data.forEach(r => {
          csv += `${r.tipo};"${r.valor}";${r.id};"${r.nome}";"${r.url_demo}"\n`;
        });
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `duplicidades_${Date.now()}.csv`;
        a.click();
      } else {
        alert('Nenhuma duplicidade encontrada.');
      }
      this.isCheckingDupes = false;
    },
    togglePlay(p) {
      if (this.currentPlayingId === p.id) {
        this.audio.pause();
        this.currentPlayingId = null;
      } else {
        if (this.audio) this.audio.pause();
        this.audio = new Audio(p.url_demo);
        this.audio.play();
        this.currentPlayingId = p.id;
        this.audio.onended = () => { this.currentPlayingId = null; };
      }
    },
    debounce(fn, ms) {
      let timer;
      return function(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), ms);
      };
    }
  }
});
</script>

<?php get_footer(); ?>
