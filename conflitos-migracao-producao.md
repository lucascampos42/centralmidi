# Conflitos da migração — relatório para análise posterior

**Gerado em:** 2026-09-27 06:49 UTC — via SQL direto no banco de produção, **antes** de executar a migração  
**Origem:** banco de produção Central MIDI (host/usuário omitidos neste repo público) · **Plugin:** v1.2.3  
**Escopo:** os 45 conflitos previstos na etapa *Analisar* (44 + 1).

> A migração é **aditiva**: não apaga nenhuma postmeta legada. Este relatório existe
> para reconciliar o que foi mantido e o que foi descartado.

---

## 1. Produtos com mais de um `url_demo` — 44 produtos

| Métrica | Valor |
|---|---:|
| Produtos afetados | 44 |
| Linhas de `url_demo` envolvidas | 88 |
| Linhas por produto | 2 |
| Produtos com URL **divergente** | **0** |
| Linhas em branco | 0 |

**Diagnóstico:** em todos os 44, as duas linhas têm `meta_value` **byte-a-byte idêntico**.
São duplicatas created por reimportação, não conteúdo diferente.

**Regra aplicada:** a classe ordena por `meta_id` na query de `postmeta` e sobrescreve,
portanto vence a linha de **menor `meta_id`**. Os pares são sempre consecutivos
(`X` e `X+1`), confirmando reimportação adjacente.

**Impacto: nenhum.** Como as duas URLs são iguais, descartar a segunda não perde nada.

### Lista completa

| # | post_id | Título | `meta_id` mantido | `meta_id` descartado | URL |
|---:|---:|---|---:|---:|---|
| 1 | 1136933 | Anos 60/Medley Anos 60 7 | `27070043` | `27070044` | `/demos/Anos_60_-_Medley_Anos_60_7.mp3` |
| 2 | 1136948 | Anos 60/Pot Pourri (broto Legal/Biquine De Bolinha Amarelinha/Banho De Lua/Estupido Cupido) | `27070392` | `27070393` | `/demos/Anos_60_-_Pot_Pourri_(broto_Legal_-_Biquine_De_Bolinha_Amarelinha_-_Banho_De_Lua_-_Estupido_Cupido).mp3` |
| 3 | 1137022 | Antonello Venditti/Benvenuti In Paradiso V.2 | `27072026` | `27072027` | `/demos/Antonello_Venditti_-_Benvenuti_In_Paradiso_V.2.mp3` |
| 4 | 1137034 | Antonello Venditti/Bomba O Non Bomba#RLM | `27072328` | `27072329` | `/demos/Antonello_Venditti_-_Bomba_O_Non_Bomba_RLM.mp3` |
| 5 | 1137037 | Antonello Venditti/Buona Domenica | `27072412` | `27072413` | `/demos/Antonello_Venditti_-_Buona_Domenica.mp3` |
| 6 | 1137038 | Antonello Venditti/Buona Domenica#RLM [PRO] | `27072445` | `27072446` | `/demos/Antonello_Venditti_-_Buona_Domenica_RLM_[pro].mp3` |
| 7 | 1137044 | Antonello Venditti/Che Tesoro Che Sei#RLM [PRO] | `27072598` | `27072599` | `/demos/Antonello_Venditti_-_Che_Tesoro_Che_Sei_RLM_[pro].mp3` |
| 8 | 1137659 | Art Popular/Gente | `27086216` | `27086217` | `/demos/Art_Popular_-_Gente.mp3` |
| 9 | 1137683 | Art Popular/Petalas De Rosas | `27086811` | `27086812` | `/demos/Art_Popular_-_Petalas_De_Rosas.mp3` |
| 10 | 1138540 | Avioes do Forro/Amo voce | `27105690` | `27105691` | `/demos/Avioes_do_Forro_-_Amo_voce.mp3` |
| 11 | 1139237 | THEUZINHO/Nao Tente Me Impedir#RLM | `27121116` | `27121117` | `/demos/052026/Theuzinho_-_Nao_Tente_Me_Impedir_RLM.mp3` |
| 12 | 1139257 | Backstreet Boys/No One Else Comes Close | `27121577` | `27121578` | `/demos/Backstreet_Boys_-_No_One_Else_Comes_Close.mp3` |
| 13 | 1139922 | Bailao 2018/Arrocha (aceita Que Doi Menos-prazer Por Prazer-contrato) | `27136009` | `27136010` | `/demos/Bailao_2018_-_Arrocha_(aceita_Que_Doi_Menos-prazer_Por_Prazer-contrato).mp3` |
| 14 | 1141269 | Banda Dom/Louvar E Adorar | `27165451` | `27165452` | `/demos/Banda_Dom_-_Louvar_E_Adorar.mp3` |
| 15 | 1141558 | Banda Fusiforme/Onde Estas Afinal#L | `27171833` | `27171834` | `/demos/Banda_Fusiforme_-_Onde_Estas_Afinal_l.mp3` |
| 16 | 1141569 | Banda G 10/Porta Retrato#L | `27172078` | `27172079` | `/demos/Banda_G_10_-_Porta_Retrato_l.mp3` |
| 17 | 1142361 | Banda Sayonara/Pot Porrit Instrumental (ao Vivo) | `27189545` | `27189546` | `/demos/Banda_Sayonara_-_Pot_Porrit_Instrumental_(ao_Vivo).mp3` |
| 18 | 1142695 | Barao Vermelho/Maior Abandonado#M [CM] | `27196953` | `27196954` | `/demos/Barao_Vermelho_-_Maior_Abandonado_M_[cm].mp3` |
| 19 | 1143037 | Baya Do Caxito/Felicidade | `27204652` | `27204653` | `/demos/Baya_Do_Caxito_-_Felicidade.mp3` |
| 20 | 1143283 | Bee Gees/Melody Fair | `27210115` | `27210116` | `/demos/Bee_Gees_-_Melody_Fair.mp3` |
| 21 | 1144156 | Biagio Antonacci/Non Mai Stato Subito#RLM | `27229654` | `27229655` | `/demos/Biagio_Antonacci_-_Non_ _Mai_Stato_Subito_RLM.mp3` |
| 22 | 1144585 | Biquini Cavadao/Sexta Feira#M [CM] | `27239177` | `27239178` | `/demos/Biquini_Cavadao_-_Sexta_Feira_M_[cm].mp3` |
| 23 | 1144596 | Biquini Cavadao/Tedio#M [CM] | `27239411` | `27239412` | `/demos/Biquini_Cavadao_-_Tedio_M_[cm].mp3` |
| 24 | 1144776 | Black Sabbath/War Pigs | `27243466` | `27243467` | `/demos/Black_Sabbath_-_War_Pigs.mp3` |
| 25 | 1146261 | Anderson Freire/Ele Chegou#RLM | `27276448` | `27276449` | `/demos/Anderson_Freire_-_Ele_Chegou_RLM.mp3` |
| 26 | 1148073 | SERGIO SILVA/Medley (Nada Mudou-Trevo De Itumbiara) | `27316529` | `27316530` | `/demos/062026/Sergio_Silva_-_Medley_(Nada_Mudou_-_Trevo_de_Itumbiara).mp3` |
| 27 | 1149457 | Canta Napoli/Lacreme Napulitanev | `27347168` | `27347169` | `/demos/Canta_Napoli_-_Lacreme_Napulitanev.mp3` |
| 28 | 1150883 | Celine Dion/Fais Ce Que Tu Voudras | `27378645` | `27378646` | `/demos/Celine_Dion_-_Fais_Ce_Que_Tu_Voudras.mp3` |
| 29 | 1151876 | ELBA RAMALHO/A Natureza Das Coisas#RLM [Pro] | `27400281` | `27400282` | `/demos/072026/Elba_Ramalho_-_A_Natureza_das_Coisas_RLM_%5BPRO%5D.mp3` |
| 30 | 1154008 | Cleber E Cauan/Sonho#L | `27447493` | `27447494` | `/demos/Cleber_E_Cauan_-_Sonho_l.mp3` |
| 31 | 1154184 | Coldplay/Every Teardrop Is A Waterfall#RLM | `27451348` | `27451349` | `/demos/Coldplay_-_Every_Teardrop_Is_A_Waterfall_rlm.mp3` |
| 32 | 1155448 | JIM DIAMOND/I Wont Let You Down#RLM [PRO] | `27479187` | `27479188` | `/demos/082026/Jim_Diamond_-_I_Wont_Let_You_Down_RLM_%5BPRO%5D.mp3` |
| 33 | 1156884 | Diana Krall/I ve Got You Underu Under My Skin#RLM | `27510911` | `27510912` | `/demos/Diana_Krall_-_I_ve_Got_You_Underu_Under_My_Skin_RLM.mp3` |
| 34 | 1159031 | Edilson Morenno/Aviao | `27558539` | `27558540` | `/demos/Edilson_Morenno_-_Aviao.mp3` |
| 35 | 1164187 | Forro Siriguella/Bola De Sabao | `27672536` | `27672537` | `/demos/Forro_Siriguella_-_Bola_De_Sabao.mp3` |
| 36 | 1172418 | Humberto E Ronaldo/Dois Loucos De Amor#RLM | `27852033` | `27852034` | `/demos/Humberto_E_Ronaldo_-_Dois_Loucos_De_Amor_RLM.mp3` |
| 37 | 1175478 | Joao Mineiro E Marciano/Cancao Do Nosso Amor | `27919357` | `27919358` | `/demos/Joao_Mineiro_E_Marciano_-_Cancao_Do_Nosso_Amor.mp3` |
| 38 | 1177697 | Julio Iglesias/Avecessi | `27968149` | `27968150` | `/demos/Julio_Iglesias_-_Avecessi.mp3` |
| 39 | 1180811 | Lindsey Bukdingham/Trouble | `28036685` | `28036686` | `/demos/Lindsey_Bukdingham_-_Trouble.mp3` |
| 40 | 1189171 | Munhoz E Mariano/Eu Vou Pegar Voce E Tae#L | `28219278` | `28219279` | `/demos/Munhoz_E_Mariano_-_Eu_Vou_Pegar_Voce_E_Tae_L.mp3` |
| 41 | 1200215 | Riccardo Cocciante/Ti Amo Ancora Di Piu 1 | `28461336` | `28461337` | `/demos/Riccardo_Cocciante_-_Ti_Amo_Ancora_Di_Piu_1.mp3` |
| 42 | 1203171 | Rouxinol E Sabia/Chuva De Lagrima | `28526303` | `28526304` | `/demos/Rouxinol_E_Sabia_-_Chuva_De_Lagrima.mp3` |
| 43 | 1208209 | Sunday/American Pie-california Dreamin | `28636079` | `28636080` | `/demos/Sunday_-_American_Pie-california_Dreamin.mp3` |
| 44 | 1208831 | Tche Garotos/A Gang Da Vaneira | `28649779` | `28649780` | `/demos/Tche_Garotos_-_A_Gang_Da_Vaneira.mp3` |

---

## 2. Produto com mais de um artista — 1 produto

| post_id | Categoria mantida | Categoria ignorada |
|---:|---|---|
| 1146331 | `ANDRE LEONNO` | `ANDRE LEONO` |

**Diagnóstico:** o produto está em **2** taxonomias `product_cat`. A diferença entre
`ANDRE LEONNO` e `ANDRE LEONO` é um erro de digitação — é o mesmo artista.

**Regra aplicada:** mantém-se o primeiro artista encontrado (menor `term_id`).

**Impacto: nenhum**, pelo mesmo motivo.

> Nota: a migração **não** filtra `parent <> 0` de propósito. Segundo o comentário do
> código,这么做 descartaria silenciosamente artistas que foram arquivados como raízes.

---

## Conclusão

Os **45 conflitos são benignos**. Manter o primeiro valor não descarta nenhum dado
único: 44 são cópias exatas e 1 é o mesmo nome escrito de duas formas.

**Nenhuma ação corretiva era necessária** antes de apagar as postmetas legadas — e as
postmetas legadas já foram apagadas (234.017 linhas, restam 0).

### Como a contagem de artistas ficou

A previsão original neste relatório (13.627) estava **errada em 2**. O real foi
**13.625**:

| Origem | Qtde |
|---|---:|
| `product_cat` distintos com produto | 13.624 |
| Artistas órfãos (aparecem em `rlm`, sem produto) | 1 |
| **Total em `wp_centralmidi_artistas`** | **13.625** |

A divergência vem de colisão por nome: são 13.631 `product_cat` com produto, mas
vários nomes coincidem com a grafia que já existia em `rlm`, e foram unificados. Os
2 nomes do produto 1146331 (`ANDRE LEONNO` / `ANDRE LEONO`) viram 1 só.

Verificação de integridade: **0** `artista_id` órfão em `midis` e **0** produtos sem
artista.
