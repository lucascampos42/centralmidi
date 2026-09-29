# Produtos para revisar — pós-migração

**Gerado em:** 2026-09-27 06:57 UTC — via SQL, após a migração Central MIDI v1.2.3 em produção  
**Origem:** banco de produção Central MIDI (host/usuário omitidos neste repo público)

A migração rodou limpa (`done=1`, 81.852 produtos, sem fatal). O total de
`_centralmidi_*` bateu exato (**736.668**), que é o validador mais forte de que as
9 postmetas por produto estão completas. As divergências abaixo são **qualidade de
dado herdado**, não falha de migração.

> **Nenhuma ação destrutiva foi executada.** As postmetas legadas (`_rlm`,
> `_url_demo`, `rlm`, `url_demo`) continuam intactas — 234.017 linhas.

---

## Prioridade 1 — 707 produtos sem gênero

**O que é:** 707 dos 81.852 produtos publicados **não possuem** o termo
`genero_musical` na taxonomia. A migração gravou `genero_id = 0` (sentinela de
"sem gênero") em vez de deixar NULL. Como `id=0` não existe na tabela de gêneros,
aparece como referência órfã se alguém criar FK estrita.

**Impacto real: nenhum.** O texto em `_centralmidi_genero` está **vazio em 707/707** —
e é o texto que o frontend usa. A página pública renderiza normalmente.

**Ação sugerida:** em `/artistas` ou nos filtros, esses produtos simplesmente não
aparecem por gênero. Se quiser, dá para categorizar manualmente depois. Não é bloqueador.

### Lista (707 produtos)

| # | post_id | Título |
|---:|---:|---|
| 1 | 1137060 | Antonello Venditti/Goodbye Novecento#RLM |
| 2 | 1137075 | Antonello Venditti/Medley |
| 3 | 1137166 | ANTONIO CARLOS E JOCAFI/Voce Abusou#L V.2 |
| 4 | 1137496 | Armando Manzanero/Nada Personal |
| 5 | 1137501 | Armando Manzanero/Usted |
| 6 | 1137566 | 2 Broother On The 4thfloor/Come Take My Hand |
| 7 | 1137613 | 2 Unlimited/No One#M |
| 8 | 1137617 | 2 Unlimited/Twilight Zone |
| 9 | 1137646 | 3 T/I Need You |
| 10 | 1139848 | ADRIANO CELENTANO/Tutti Frutti#RLM |
| 11 | 1140287 | AGEPE/Deixa Eu Te Amar#L |
| 12 | 1140298 | AGEPE/Me Leva#RLM |
| 13 | 1140301 | AGEPE/Moca Crianca#L |
| 14 | 1140322 | AGEPE/No Calor Do Teu Amor#L |
| 15 | 1140324 | AGEPE/Tempo De Amar#L |
| 16 | 1140762 | A-HA/Take On Me (Unplugged) |
| 17 | 1140779 | A-HA/Take On Me#M V.2 |
| 18 | 1140781 | A-HA/Take On Me#RLM |
| 19 | 1141512 | Albano/Nel Sole#L |
| 20 | 1142864 | Barbra Streisand/Somewhere#L |
| 21 | 1144925 | Blood Sweat E Tears/Hi-de-ho (that Old Sweet Roll)#RLM [t1000] |
| 22 | 1145623 | Bon Jovi/Livin On A Prayer (live)#L |
| 23 | 1145694 | Amedeo Mingh/Cantare D Amore#RLM |
| 24 | 1145730 | BONDE DO FORRO/Brincar De Ser Feliz#L |
| 25 | 1145764 | BONDE DO FORRO/Deus Me Livre#L |
| 26 | 1145860 | BONDE DO FORRO/Pinga Ni Mim#L |
| 27 | 1145866 | AMICI/Voce Vai Ver#RLM |
| 28 | 1146123 | Ana Castela E Gustavo Mioto/Princesa#L |
| 29 | 1146145 | Ana Castela/Olha Onde Eu To#RLM |
| 30 | 1146567 | ANGELA RO RO/So Nos Resta Viver#RLM |
| 31 | 1148190 | Bruno E Marrone/Favo De Mel (acustico)#L |
| 32 | 1148191 | BRUNO E MARRONE/Favo De Mel (Acustico)#L V.2 |
| 33 | 1148518 | Bruno Rosa E Jorge E Mateus/A Grama Do Vizinho#RLM |
| 34 | 1152242 | CHICO REY E PARANA/Voce Nao Sabe Amar#L |
| 35 | 1152265 | CHICOTE DE LUXO/Xonado Sem Quantia#RLM |
| 36 | 1153893 | CLAUDIO NEY E JULIANA E LEO SANTANA/Desliza#RLM |
| 37 | 1153894 | CLAUDIO NEY E JULIANA/Balanca Essa Sanfona#RLM |
| 38 | 1153963 | CLAYTON E ROMARIO E ZE FELIPE/Se Eu Te Perdoar#RLM |
| 39 | 1154164 | COGUMELO PLUTAO/Esperando Na Janela (Versao Pagode)#L |
| 40 | 1154841 | COUNTRYBEAT E RIO NEGRO E SOLIMOES/Apaixona Muita Gente#RLM |
| 41 | 1157116 | DIEGO E ARNALDO E ZE NETO E CRISTIANO/Vai Cair Agua#RLM |
| 42 | 1157144 | Diego E Victor Hugo/Dama Do Baralho#RLM |
| 43 | 1157194 | DIK DIK/Sognando California#RLM |
| 44 | 1157334 | Dino Fonseca/Everytime You Go Away#RLM |
| 45 | 1157345 | DINO FONSECA/La Barca#RLM |
| 46 | 1157351 | DINO FONSECA/My Sacrifice (Creed)#L |
| 47 | 1157371 | Dino Fonseca/Uma Brasileira#RLM |
| 48 | 1157494 | AMIE/Pure Prarie League#RLM |
| 49 | 1157495 | ANA CASTELA/As Cowgirl#RLM |
| 50 | 1157496 | ANA CASTELA/Quero Camboriu#RLM |
| 51 | 1157501 | BRENNO E MATEUS/Eu Quero E Praia#RLM |
| 52 | 1157502 | BRUNO E MARRONE E HENRIQUE E DIEGO/Esqueci Voce#RLM |
| 53 | 1157503 | CALCINHA PRETA/Hoje A Noite (Ao Vivo)#RLM |
| 54 | 1157507 | DAZARANHA/Meu Paraiso#L |
| 55 | 1157511 | DOMENICO MODUGNO/Questa E La Mia Vita#L |
| 56 | 1157521 | EIFFEL65/Bad Touch (Remix) |
| 57 | 1157546 | GRUPO VOCAL SINTONIA FRATERNA/Guenta Coracao#M |
| 58 | 1157550 | GUILHERME SILVA/Ela Vem Ni Mim#RLM |
| 59 | 1157554 | HINARIO LUTERANO 214/Ao Meu Deus Nao Cantaria#M |
| 60 | 1157559 | I GIGANTI/Proposta#RLM |
| 61 | 1157569 | JOAO NETO E FREDERICO/Seu Amor Ainda E Tudo#L |
| 62 | 1157606 | MUSICA MEXICANA/Alla En El Rancho Grande#L |
| 63 | 1157608 | MUSICA MEXICANA/Ay Jalisco No Te Rajes#L |
| 64 | 1157610 | MUSICA MEXICANA/Besame#RLM |
| 65 | 1157611 | MUSICA MEXICANA/Cachita#RLM |
| 66 | 1157614 | MUSICA MEXICANA/Conga Mix Fin De Fiesta |
| 67 | 1157615 | MUSICA MEXICANA/Cucurucucu Paloma#RLM |
| 68 | 1157616 | MUSICA MEXICANA/Ella (Me Canse De Rogarle)#RLM |
| 69 | 1157618 | MUSICA MEXICANA/Fallaste Corazon#RLM |
| 70 | 1157620 | MUSICA MEXICANA/La Tuna#RLM |
| 71 | 1157621 | MUSICA MEXICANA/La Yenka#RLM |
| 72 | 1157622 | MUSICA MEXICANA/Maria Elena#RLM |
| 73 | 1157624 | MUSICA MEXICANA/Moliendo Cafe#RLM |
| 74 | 1157626 | MUSICA MEXICANA/Pot Pourri Mambomix |
| 75 | 1157627 | MUSICA MEXICANA/Tequila#RLM |
| 76 | 1157628 | NANDO MORENO E RIONEGRO E SOLIMOES/Chapeludo#RLM |
| 77 | 1157632 | NATANZINHO LIMA/Se Nao Valorizar#RLM |
| 78 | 1157634 | PABLO/Perdeu Pai#RLM |
| 79 | 1157636 | PANDA/Eu Te Seguro#RLM |
| 80 | 1157639 | PANDA E GUSTTAVO LIMA/Calcinha De Renda#RLM |
| 81 | 1157644 | POOH/Che Vuoi Che Sia#RLM |
| 82 | 1157646 | POOH/Classe 58#RLM |
| 83 | 1157647 | POOH/Come Si Fa#RLM |
| 84 | 1157648 | POOH/Devi Crederci#RLM |
| 85 | 1157652 | POOH/Dialoghi#RLM |
| 86 | 1157654 | POOH/Dove Sto Domani#RLM |
| 87 | 1157655 | POOH/Figli#RLM |
| 88 | 1157656 | POOH/Giorni Infiniti#RLM |
| 89 | 1157657 | POOH/Il Silenzio Della Colomba#RLM |
| 90 | 1157660 | POOH/L Anno Il Posto L Ora#RLM |
| 91 | 1157662 | POOH/La Donna Del Mio Amico#RLM |
| 92 | 1157664 | POOH/La Ragazza Con Gli Occhi Di Sole#RLM |
| 93 | 1157665 | POOH/Le Canzoni Di Domani#RLM |
| 94 | 1157676 | POOH/Quel Che Non Si Dice#RLM |
| 95 | 1157685 | POOH/Susanna E Basta#RLM |
| 96 | 1157687 | POOH/Tanta Voglia Di Lei#RLM V.2 |
| 97 | 1157691 | POOH/Uomini Soli#RLM |
| 98 | 1157693 | POP TOPS/Con Su Blanca Palidez#RLM |
| 99 | 1157695 | PORGY & BESS/Summertime#RLM |
| 100 | 1157697 | PRESUNTOS IMPLICADOS/Como Hemos Cambiado |
| 101 | 1157699 | PRESUNTOS IMPLICADOS/Como Hemos Cambiado#L |
| 102 | 1157700 | PRESUNTOS IMPLICADOS/Mi Pequeno Tesoro |
| 103 | 1157702 | PRESUNTOS IMPLICADOS/Mil Mariposas |
| 104 | 1157703 | PRINCE/The Most Beautiful Girl In The World#RLM |
| 105 | 1157705 | PROCOL HARUM/A Whiter Shade Of Pale#RLM |
| 106 | 1157708 | PROZAC/Acida#RLM |
| 107 | 1157710 | PUERTO RICIAN/Aliviame |
| 108 | 1157712 | PUERTO RICIAN/Amor Mio No Te Vayas |
| 109 | 1157714 | PUERTO RICIAN/Amores Como El Nuestro |
| 110 | 1157716 | PUERTO RICIAN/Apiadate |
| 111 | 1157723 | PUERTO RICIAN/Lloraras |
| 112 | 1157725 | PUERTO RICIAN/Lluvia |
| 113 | 1157727 | PUERTO RICIAN/No Vale La Pena |
| 114 | 1157738 | QUARTETTO CETRA/Nella Vecchia Fattoria#RLM |
| 115 | 1157741 | QUEEN/Bohemian Rhapsody#RLM |
| 116 | 1157745 | QUEEN/Friends Will Be Friends#L |
| 117 | 1157747 | QUEEN/The Miracle#L |
| 118 | 1157749 | QUEEN/The Show Must Go On#L |
| 119 | 1157751 | QUEEN/This Could Be Heaven#RLM |
| 120 | 1157753 | QUEEN/Too Much Love Will Kill You#RLM |
| 121 | 1157754 | QUEEN/Under Pressure#L |
| 122 | 1157755 | QUEEN/We Are The Champions#RLM |
| 123 | 1157757 | QUEEN/Who Wants To Live Forever#L |
| 124 | 1157758 | QUEEN/Winter Fall#RLM |
| 125 | 1157760 | QUEEN/You Re My Best Friend#RLM |
| 126 | 1157761 | R. & R. SHERMAN/Its A Small World#RLM |
| 127 | 1157764 | R. & R. SHERMAN/You Re Sixteen#RLM |
| 128 | 1157770 | R KELLY/I Believe I Can Fly#RLM |
| 129 | 1157773 | RADIO FUTURA/En La Selva#RLM |
| 130 | 1157774 | RADIO FUTURA/Semilla Negra |
| 131 | 1157775 | RAF/Il Battito Animale#RLM |
| 132 | 1157780 | RAF/Sei La Piu Bella Del Mondo#RLM |
| 133 | 1157782 | RAFFAELLA CARRA/Medley |
| 134 | 1157788 | RAOUL CASADEI/Tango Della Gelosia#RLM |
| 135 | 1157790 | RASTA CHINELA/Ela Tem O Dom De Me Fazer Chorar#L |
| 136 | 1157792 | RAUL/Baila#RLM |
| 137 | 1157794 | RAUL/Mujer Prohibida |
| 138 | 1157796 | RAUL/Suenno Su Boca |
| 139 | 1157797 | RAUOL CASADEI/Ciao Mare#RLM |
| 140 | 1157799 | RAUOL CASADEI/La Mazurka Di Periferia#L |
| 141 | 1157805 | RAUOL CASADEI/Medley Tarantelle |
| 142 | 1157806 | RAUOL CASADEI/Medley Valzer |
| 143 | 1157807 | RAUOL CASADEI/Romagna E Sangiovese#RLM |
| 144 | 1157809 | RAUOL CASADEI/Romagna Mia#L |
| 145 | 1157810 | RAUOL CASADEI/Simpatia#L |
| 146 | 1157812 | RAYA REAL/Rumbamix |
| 147 | 1157816 | REM/Everybody Hurts#RLM |
| 148 | 1157822 | RENATO CAROSONE/Torero#RLM |
| 149 | 1157823 | RENATO RASCEL/Arrivederci Roma#RLM |
| 150 | 1157829 | RENATO ZERO/Cercami#RLM |
| 151 | 1157831 | RENATO ZERO/Dimmi Chi Dorme Accanto A Te#RLM |
| 152 | 1157833 | RENATO ZERO/I Migliori Anni Della Nostra Vita#RLM |
| 153 | 1157836 | RENATO ZERO/Libera#RLM |
| 154 | 1157838 | RENATO ZERO/Madame#RLM |
| 155 | 1157840 | RENATO ZERO/Marciapiedi#RLM |
| 156 | 1157842 | RENATO ZERO/Medley |
| 157 | 1157849 | RENATO ZERO/Si Sta Facendo Notte#RLM |
| 158 | 1157852 | RENATO ZERO/Spiagge#RLM |
| 159 | 1157853 | RENATO ZERO/Triangolo#RLM |
| 160 | 1157856 | RENATO ZERO/Via Dei Martiri#RLM |
| 161 | 1157857 | RENT/Seasons Of Love#RLM |
| 162 | 1157862 | RENZO ARBORE/Cocorito#RLM |
| 163 | 1157864 | RENZO ARBORE/Il Clarinetto#RLM |
| 164 | 1157866 | RENZO ARBORE/Il Materasso#RLM |
| 165 | 1157867 | RENZO ARBORE/Ma Come Fanno I Marinai#RLM |
| 166 | 1157868 | RENZO ARBORE/Medley |
| 167 | 1157870 | RENZO ARBORE/Medley Slow[1] |
| 168 | 1157871 | RENZO ARBORE/Si La Vita E Tutta Un Quiz#RLM |
| 169 | 1157872 | RENZO ARBORE/Spaghetti A Detroit#RLM |
| 170 | 1157874 | RENZO ARBORE E ORCHESTRA ITALIANA/Comme Facette Mammeta#M |
| 171 | 1157882 | REO SPEEDWAGON/Time For Me To Fly#RLM |
| 172 | 1157885 | RICHARD MARX/Right Here Waiting |
| 173 | 1157886 | RICHIE VALENS/La Bamba V.2 |
| 174 | 1157888 | RICKY MARTIN/La Bomba |
| 175 | 1157890 | RICKY MARTIN/La Copa De La Vida |
| 176 | 1157892 | RICKY MARTIN/La Copa De La Vida#RLM V.2 |
| 177 | 1157893 | RICKY MARTIN/Livin La Vida Loca |
| 178 | 1157894 | RICKY MARTIN/Livin La Vida Loca#RLM |
| 179 | 1157898 | RICKY MARTIN/Livin La Vida Loca#RLM V.2 |
| 180 | 1157899 | RICKY MARTIN/Maria#M |
| 181 | 1157901 | RICKY MARTIN/Maria M V.2 |
| 182 | 1157903 | RICKY MARTIN/Shake Your Bom Bom |
| 183 | 1157905 | RICKY MARTIN/Vuelve#RLM V.2 |
| 184 | 1157906 | RICKY NELSON/Hello Mary Lou#RLM |
| 185 | 1157907 | RICO VACILON/Cha Cha Cha#M |
| 186 | 1157908 | RIGHTEOUS BROTHERS/You Ve Lost That Lovin Feeling#RLM |
| 187 | 1157910 | RINO GAETANO/Aida#RLM |
| 188 | 1157912 | RINO GAETANO/Gianna Gianna#RLM |
| 189 | 1157913 | RITA PAVONE/Andavo A 100 All Ora#RLM |
| 190 | 1157919 | RITA PAVONE/Qui Ritornera#RLM |
| 191 | 1157920 | ROBBIE WILLIAMS/Angels#RLM |
| 192 | 1157923 | ROBBIE WILLIAMS/Rock Dj#RLM |
| 193 | 1157925 | ROBBIE WILLIAMS/Something Stupid#RLM |
| 194 | 1157927 | ROBBIE WILLIAMS E KYLIE MINOGUE/Kids#RLM |
| 195 | 1157935 | ROBERTO CARLOS/Amigo (Espanhol)#L |
| 196 | 1157942 | ROCH VOISINE/Helene#L |
| 197 | 1157944 | ROCIO DURCAL/Amor Eterno |
| 198 | 1157945 | ROCIO DURCAL/Como Han Pasado Los Anos#M |
| 199 | 1157946 | ROCIO DURCAL/Desaires#M |
| 200 | 1157947 | ROCIO DURCAL/Fue Tan Poco Tu Carino#RLM V.2 |
| 201 | 1157948 | ROCIO DURCAL/Para Toda La Vida |
| 202 | 1157950 | ROCIO DURCAL/Que El Mundo Ruede |
| 203 | 1157951 | ROD STEWART/Do You Think Im Sexy#RLM |
| 204 | 1157955 | ROD STEWART/Some Guys Have All The Luck#RLM |
| 205 | 1157971 | RODGERS E HAMMERSTEIN/My Favorite Things#RLM |
| 206 | 1157973 | ROGER COOK E SAM HOGIN/I Believe In You#RLM |
| 207 | 1157974 | ROLLING STONES/Jumping Jack Flash#RLM |
| 208 | 1157975 | ROLLING STONES/Song Title#RLM |
| 209 | 1157976 | RON/Joe Temerario#RLM |
| 210 | 1157979 | RON/Vorrei Incontrarti Tra Cent Anni#RLM |
| 211 | 1157981 | RONAN KEATING E GIORGIA/We Ve Got Tonight#RLM |
| 212 | 1157989 | ROSSANA CASALE/Brividi#RLM |
| 213 | 1157991 | ROY ORBISON/Pretty Womam#RLM |
| 214 | 1157994 | ROY ORBISON/You Got It#RLM |
| 215 | 1158006 | SURVIVOR/Eye Of The Tiger |
| 216 | 1158007 | TANGO/Preghiera Innamorata |
| 217 | 1158012 | TIBURON/Proyecto Uno |
| 218 | 1158016 | TRAIA VEIA/Os Coracoes Nao Sao Iguais#RLM |
| 219 | 1158052 | WESLEY SAFADAO/Comigo E Assim Lapada Lapada (Ao Vivo)#RLM |
| 220 | 1158053 | ZE FELIPE/Eu Sou Desejo, Voce E Paixao (Right Here Waiting Fot You)#RLM |
| 221 | 1158054 | ZE VAQUEIRO/Que Pancada De Mulher#RLM |
| 222 | 1158055 | ZE VAQUEIRO/Volta Bebe, Volta Nenem#L |
| 223 | 1158056 | ZE VAQUEIRO E NATTAN/Nao Te Quero#RLM |
| 224 | 1158298 | Don Williams/I Believe In You#RLM [t1000] |
| 225 | 1161418 | ERASURE/Stars |
| 226 | 1162567 | FAGNER/Oracao De Sao Francisco#RLM |
| 227 | 1162815 | Fats Domino/Im In Love Again#RLM [t1000] |
| 228 | 1162971 | FELIPE E RODRIGO/Banquinho#RLM |
| 229 | 1162972 | FELIPE E RODRIGO/Caos De Alguem#RLM |
| 230 | 1163966 | Forro 2025/(o Dia Vai A Noite Vem/Devagar/Meu Cavalo Sabe/Chibata Do Veim) |
| 231 | 1163967 | Forro 2025/(quem Sera Seu Outro Amor/Quem E O Dono Dos Seus Olhos/Ti Ti Ti) |
| 232 | 1164018 | Forro Boys/Reboladin#L |
| 233 | 1165376 | G Giordani/Caro Mio Ben#RLM |
| 234 | 1165706 | Gardel Lepera/Volver#RLM |
| 235 | 1165957 | GARY MOORE/Parisienne Walkways |
| 236 | 1167721 | Glen Campbell E Rita Coolidge/Somethin Bout You Baby I Like#RLM [t1000] |
| 237 | 1167728 | Glen Campbell/Try A Little Kindness#RLM [t1000] |
| 238 | 1170148 | GUILHERME E SANTIAGO/Anjo Na Terra#L |
| 239 | 1170303 | GUILHERME SILVA E TJ/E Bom#RLM |
| 240 | 1170412 | GUILHERME SILVA/Tira O Pe Tira O Pe#L |
| 241 | 1170545 | GUSTTAVO LIMA E LUIS FONSI/Vagabundo#RLM |
| 242 | 1170699 | GUSTTAVO LIMA/Retrovisor#RLM |
| 243 | 1171343 | HENRIQUE E JULIANO/Amor Dos Outros#RLM |
| 244 | 1171417 | HENRIQUE E JULIANO/Vida#L |
| 245 | 1172279 | Hugo E Guilherme E Ana Castela/Todo Mundo Menos Eu#RLM |
| 246 | 1172280 | Hugo E Guilherme E Ana Castela/Todo Mundo Menos Eu#RLM |
| 247 | 1172304 | Hugo E Guilherme/Foi Mal Deus#RLM |
| 248 | 1172790 | Irene Cara/What A Feeling |
| 249 | 1173098 | ISRAEL E RODOLFFO E LEONARDO/Conto De Fadas#RLM |
| 250 | 1173195 | ITALO CREMA/Rossana |
| 251 | 1173196 | ITALO CREMA/Sottovoce |
| 252 | 1174306 | JAYNE E MIGUEL ZINOVIC/Amigos Para Siempre |
| 253 | 1174638 | JENNIFER E STEPHANY E COUNTRYBEAT/To Na Moda#L |
| 254 | 1176325 | JOHNNY DORELLI/Solo Piu Che Mai#RLM |
| 255 | 1177425 | JU MARQUES/Always Remember Us This Way (Seresta Internacional)#RLM |
| 256 | 1178993 | LAERCIA DANTAS/Onde Anda Meu Amor (Tem Pitu)#RLM |
| 257 | 1179254 | LAUANA PRADO/Temporal De Amor#RLM |
| 258 | 1179386 | LAYLA KAYLIF/Shakespeare In Love |
| 259 | 1180049 | LEO SANTANA E MELODY/Desliza#RLM |
| 260 | 1180056 | LEO SANTANA/Eta Novinha#RLM |
| 261 | 1180106 | LEONARDO E EDUARDO COSTA/Nao Quero Piedade (Cabare)#L |
| 262 | 1180127 | LEONARDO SULLIVAN/Quando Chegar O Amanha#L V.2 |
| 263 | 1180494 | LEONARDO/Sextou#RLM |
| 264 | 1180610 | LETTER B/Sesame Street#RLM |
| 265 | 1180917 | Lisa Stansfield/All Woman#RLM [t1000] |
| 266 | 1180923 | Lisa Stansfield/Soul Deep#RLM [t1000] |
| 267 | 1180928 | Lisa Stansfield/You Cant Deny It#RLM [t1000] |
| 268 | 1182292 | LUAN SANTANA E LEO FOGUETE/Dona#RLM |
| 269 | 1183612 | M Travis/Sixteen Tons#L |
| 270 | 1185862 | Marina Rei/Un Inverno Da Baciare#RLM |
| 271 | 1186812 | MAURICIO MANIERI/Caca E Cacador#RLM |
| 272 | 1187410 | Menos E Mais E Nattan/Pela Ultima Vez#RLM |
| 273 | 1187411 | MENOS E MAIS E NATTAN/Pela Ultima Vez#RLM |
| 274 | 1187800 | MICHEL TELO/Anunciacao#RLM |
| 275 | 1187832 | MICHEL TELO/Metamorfose Ambulante#RLM |
| 276 | 1189213 | MURILO HUFF/Deixa Eu#RLM |
| 277 | 1190712 | NATANZINHO LIMA/Que Se Chama Amor#RLM |
| 278 | 1190714 | NATANZINHO LIMA/Sonho De Amor#RLM |
| 279 | 1191020 | NEK/Ci Sei Tu#RLM V.2 |
| 280 | 1191023 | NEK/In Te#RLM |
| 281 | 1191026 | NEK/Laura No Esta#RLM V.2 |
| 282 | 1191030 | NEK/Parliamo Al Singolare#RLM |
| 283 | 1191031 | NEK/Se Io Non Avessi Te#RLM |
| 284 | 1191807 | NIVALDO MARQUES E NATTAN/Tem Cabare Essa Noite#L V.2 |
| 285 | 1192966 | OS BAROES DA PISADINHA E IGUINHO E LULINHA/Coracao Na Cancela#RLM |
| 286 | 1193705 | Os Originais Do Samba/Alegrias De Domingo#RLM |
| 287 | 1194221 | OVELHA/Sem Voce Nao Viverei (Versao Pisadinha)#L |
| 288 | 1194370 | PABLO/Quem Ama Nao Machuca#RLM |
| 289 | 1196444 | Peruvian/Trujillo Mio |
| 290 | 1196483 | Pet Shop Boys/Its A Sin#L |
| 291 | 1197029 | Pino Donaggio/Una Casa In Cima Al Mondo |
| 292 | 1198232 | Raca Negra/Deus Me Livre (versao Forro)#L |
| 293 | 1198546 | RAGAZZI DEI MONTI/La Bella Polenta#RLM |
| 294 | 1199405 | REGINALDO ROSSI/Amor Amor Amor#L |
| 295 | 1199718 | RENATO CAROSONE/Serenatella Sciues Sciue#RLM |
| 296 | 1199719 | RENATO CAROSONE/Speranzella#M |
| 297 | 1199721 | RENATO CAROSONE/Tre Numeri Al Lotto#M |
| 298 | 1199905 | RENATO ZERO/Sterili#l |
| 299 | 1202138 | Roberto Vecchioni/Le Mie Ragazze#RLM |
| 300 | 1202142 | Roberto Vecchioni/Sogna Ragazzo Sogn#RLM |
| 301 | 1202143 | ROBERTO VECCHIONI/Sogna Ragazzo Sogna#M |
| 302 | 1202225 | ROCKING HORSE/Forza Sugar |
| 303 | 1202325 | RODRIGO SILVA/Chibata Do Veim#RLM |
| 304 | 1202866 | ROSANNA FRATELLO/Sono Una Donna Non Sono Una Santa |
| 305 | 1203217 | ROXETTE/Daniel Boone Theme#RLM |
| 306 | 1203224 | ROXETTE/Fading Like A Flower#RLM |
| 307 | 1203247 | ROXETTE/It Must Have Been Love#L V.2 |
| 308 | 1203293 | ROXETTE/Spending My Time#RLM |
| 309 | 1203320 | ROXY MUSIC/Avalon#RLM |
| 310 | 1203449 | RUGGERI/Il Mare D&#039;Inverno#RLM |
| 311 | 1203450 | RUGGERI/Il Portiere Di Notte#RLM |
| 312 | 1203451 | RUGGERI/Nuovo Swing#RLM |
| 313 | 1203452 | RUGGERI/Peter Pan#RLM |
| 314 | 1203453 | RUGGERI/Primavera A Sarajevo#RLM |
| 315 | 1203454 | RUGGERI/Rien Ne Va Plus#RLM |
| 316 | 1203729 | S. A. KIPNER E TERRY SHADDICK/Physical#RLM |
| 317 | 1203797 | SACHA DISTEL/Escandalo En La Familia#RLM |
| 318 | 1203813 | SADE/Siempre Hay Esperanza |
| 319 | 1203817 | SADE/The Sweetest Taboo#RLM |
| 320 | 1203818 | SADE/The Sweetest Taboo#RLM |
| 321 | 1203885 | SAL MARINA/Deja Que Te Mire |
| 322 | 1203943 | SAMANTHA FOX/I Only Want To Be With You (Ahora Te Puedes Marchar)#RLM |
| 323 | 1204031 | SAMUELE BASSEY/And I Love You So#RLM |
| 324 | 1204037 | SAMUELE BERSANI/Freak#RLM V.2 |
| 325 | 1204039 | SAMUELE BERSANI/Giudizi Universali#RLM |
| 326 | 1204048 | SAN FRANCESCO/Fratello Sole Sorella Luna#RLM |
| 327 | 1204239 | SANDRO GIACOBBE/Gli Occhi Verdi Di Tua Madre#RLM |
| 328 | 1204575 | SANTANA/Black Magic Woman#RLM |
| 329 | 1204582 | SANTANA/Corazon Espinado#RLM V.2 |
| 330 | 1204585 | SANTANA/Evil Ways#RLM |
| 331 | 1204598 | SANTANA/Maria Maria#RLM V.2 |
| 332 | 1204602 | SANTANA/Oye Como Va |
| 333 | 1204703 | SARAH VAUGHAN/Moonlight In Vermont#L |
| 334 | 1204718 | SASH/Mysterious Times#RLM V.2 |
| 335 | 1204721 | SASHA/That If You Believe#RLM |
| 336 | 1204787 | SCIALPI/Cigarettes And Coffee#L |
| 337 | 1204798 | SCOOTER/Logical Song#L |
| 338 | 1204799 | SCOOTER/Logical Song#L |
| 339 | 1204859 | SCORPIONS/Wind Of Change#RLM |
| 340 | 1204862 | SCORPIONS/You And I#RLM |
| 341 | 1205000 | SELIA/Sera (Versao Pagode)#L |
| 342 | 1205133 | SERGIO CAMMARIERE/Tutto Quello Che Un Uomo#RLM |
| 343 | 1205136 | SERGIO CAPUTO/Bimba Se Sapessi#RLM |
| 344 | 1205143 | SERGIO CAPUTO/Mambo Italiano#RLM |
| 345 | 1205145 | SERGIO CAPUTO/Metamorfosi#RLM |
| 346 | 1205149 | SERGIO CAPUTO/Un Sabato Italiano#RLM |
| 347 | 1205171 | SERGIO E THE LADIES/Sister#RLM |
| 348 | 1206243 | SHAKIRA/Estoy Aqui#M V.2 |
| 349 | 1206267 | SHAKIRA/Suerte#RLM |
| 350 | 1206274 | SHAKIRA/Whenever, Wherever#L |
| 351 | 1206283 | SHANIA TWAIN/Any Man Of Mine#RLM |
| 352 | 1206293 | SHANIA TWAIN/I&#039;M Gonna Getcha Good#RLM |
| 353 | 1206310 | SHANIA TWAIN/Whose Bed Are Your Boots Been Under#RLM |
| 354 | 1206312 | SHANIA TWAIN/You Are Still The One#RLM |
| 355 | 1206323 | SHAWN COLVIN/Sunny Came Home#RLM |
| 356 | 1206328 | SHEENA EASTON/For Your Eyes Only#RLM |
| 357 | 1206329 | SHEENA EASTON/The Nearness Of You#L |
| 358 | 1206511 | SILVERCHAIR/Miss You Love#RLM |
| 359 | 1206568 | SILVIO RODRIGUEZ/Angel Para Un Final#M |
| 360 | 1206570 | Silvio Rodriguez/El Unicornio Azul |
| 361 | 1206571 | Silvio Rodriguez/El Unicornio Azul#M |
| 362 | 1206573 | SILVIO RODRIGUEZ/Elegido#M |
| 363 | 1206574 | Silvio Rodriguez/En Mi Calle#L |
| 364 | 1206575 | SILVIO RODRIGUEZ/Imaginate |
| 365 | 1206576 | SILVIO RODRIGUEZ/Los Cazabrujas De Dores#M |
| 366 | 1206578 | SILVIO RODRIGUEZ/Nadamas |
| 367 | 1206579 | SILVIO RODRIGUEZ/Ojala#RLM |
| 368 | 1206582 | Silvio Rodriguez/Quien Fuera#L |
| 369 | 1206583 | Silvio Rodriguez/Rabo De Nube |
| 370 | 1206585 | SILVIO RODRIGUEZ/Supon#L |
| 371 | 1206587 | Silvio Rodriguez/Tomame O Dejame |
| 372 | 1206588 | SILVIO RODRIGUEZ/Yolanda#M |
| 373 | 1206616 | SIMON E GARFUNKEL/Homeward Bound#RLM |
| 374 | 1206709 | SIMONE MENDES/Mesmice#RLM |
| 375 | 1206820 | SIMPLY MINDS/Dont You (Forget About Me)#RLM V.2 |
| 376 | 1206831 | SIMPLY RED/Holding Back The Years#RLM |
| 377 | 1206865 | SINEAD O CONNOR/Nothing Compares To You#RLM |
| 378 | 1206866 | SINEAD O CONNOR/Skin On Skin#RLM |
| 379 | 1206938 | SISTER ACT/I Will Follow Him |
| 380 | 1207090 | SKEETER DAVIS/The End Of The World#RLM |
| 381 | 1207154 | SMASHING PUMPKINS/Today#RLM |
| 382 | 1207165 | SMOKEY ROBINSON/Track Of My Tears#RLM |
| 383 | 1207314 | SODA STEREO/Cuando Pase El Temblor |
| 384 | 1207317 | SODA STEREO/De Musica Ligeira#RLM |
| 385 | 1207320 | SODA STEREO/Final Caja Negra |
| 386 | 1207321 | SODA STEREO/Nada Personal |
| 387 | 1207494 | SONIQUE/Sky#RLM |
| 388 | 1207495 | SONIQUE/Sky#RLM |
| 389 | 1207496 | SONIQUE/Sky#RLM |
| 390 | 1207497 | SONNY BONO/Bang Bang#RLM |
| 391 | 1207498 | SONNY BONO/Bang Bang#RLM |
| 392 | 1207499 | SONNY BONO/Bang Bang#RLM |
| 393 | 1207500 | SONNY BONO/I Got You Babe#RLM |
| 394 | 1207501 | SONNY BONO/I Got You Babe#RLM |
| 395 | 1207522 | SONORA DE MARGARITA/Evocacion |
| 396 | 1207523 | SONORA DE MARGARITA/Evocacion |
| 397 | 1207524 | SONORA DINAMITA/Se Me Perdio La Cadenita |
| 398 | 1207703 | SOUL ASYLUM/Somebody To Shove#RLM |
| 399 | 1207750 | SPANDAU BALLET/True#RLM |
| 400 | 1207756 | SPICE GIRLS/2 Become 1#RLM |
| 401 | 1207769 | SPICE GIRLS/Love Don&#039;T Cost A Thing#RLM |
| 402 | 1207779 | SPICE GIRLS/Say You Be There#RLM |
| 403 | 1207782 | SPICE GIRLS/Spice Up Your Life#RLM |
| 404 | 1207789 | SPICE GIRLS/Viva Forever#RLM |
| 405 | 1207792 | SPICE GIRLS/Wanna Be#RLM |
| 406 | 1207796 | SPIRAL STAIRCASE/More Today Than Yesterday#L |
| 407 | 1207804 | SQUALLOR/Chi Cazzo Mo Ffa Fa#RLM |
| 408 | 1207821 | STADIO/In Paradiso Con Te#RLM |
| 409 | 1207823 | STADIO/Sorprendimi#RLM |
| 410 | 1207829 | STAIND/Its Been A While#L |
| 411 | 1207838 | STAR BOYS/Pra Mudar Minha Vida (Forro)#L |
| 412 | 1207864 | STEELY DAN/Aja#RLM |
| 413 | 1207916 | STEPHEN STILLS/Love The One You&#039;Re With#RLM |
| 414 | 1207917 | STEPPENWOLF/Born To Be Wild#RLM |
| 415 | 1207937 | STEVE NELSON E J ROLLINS/Frosty The Snow Man#RLM |
| 416 | 1207989 | STEVIE WONDER/For Once In My Life#RLM |
| 417 | 1207998 | STEVIE WONDER/Higher Ground#RLM |
| 418 | 1208004 | STEVIE WONDER/I Just Called To Say I Love You#RLM |
| 419 | 1208011 | STEVIE WONDER/Isn T She Lovely#RLM |
| 420 | 1208019 | STEVIE WONDER/My Cherie Amour#RLM |
| 421 | 1208039 | STEVIE WONDER/You Are The Sunshine Of My Life#RLM |
| 422 | 1208041 | STEWARD/The Year Of The Cat#L |
| 423 | 1208061 | STING/If I Ever Lose My Faith In You#RLM |
| 424 | 1208110 | STRAWBERRY ALARM CLOCK/Incense And Peppermints#RLM |
| 425 | 1208162 | SUGABABES/New Year#RLM |
| 426 | 1208163 | SUGABABES/Overload#RLM |
| 427 | 1208174 | SUI GENERIS/Aprendizaje#L |
| 428 | 1208177 | SUI GENERIS/Cuando Ya Me Empiece A Quedar Solo |
| 429 | 1208179 | SUI GENERIS/El Fantasma De Canterville |
| 430 | 1208218 | SUPERMAN LOVERS/Starlight |
| 431 | 1208234 | SUPERTRAMP/Give A Little Bit#RLM |
| 432 | 1208241 | SUPERTRAMP/Its Raining Again#RLM |
| 433 | 1208249 | SUPERTRAMP/School#RLM |
| 434 | 1208252 | SUPERTRAMP/Take The Long Way Home#RLM |
| 435 | 1208259 | SUPERTRAMP/The Logical Song#RLM |
| 436 | 1208274 | SURVIVOR/Eye Of The Tiger#RLM |
| 437 | 1208284 | SUZANNE VEGA/Luka (Acustico)#RLM |
| 438 | 1208392 | SYRIA/Non Ci Sto#RLM |
| 439 | 1208396 | SYRIA/Station Wagon |
| 440 | 1208410 | T HATCH/Downtown#RLM |
| 441 | 1208415 | T Rundgren/You Dont Have To Camp Around#L |
| 442 | 1208470 | TAKE THAT/Back For Good#RLM |
| 443 | 1208495 | TAMARA/Campanitas |
| 444 | 1208497 | TAMARA/En Tu Pelo#M |
| 445 | 1208502 | TAMARA/Que Nadie Sepa Mi Sufrir |
| 446 | 1208669 | TARKAN/Simarik#RLM |
| 447 | 1208687 | TATY GIRL/Se Nao Valorizar#L |
| 448 | 1208961 | TEARS FOR FEARS/Shout#RLM |
| 449 | 1209546 | TEN SHARP/You#RLM |
| 450 | 1209913 | TEQUILA/Vamos A Tocar Un Rock &amp; Roll A La Plaza Del Pueblo |
| 451 | 1210207 | THALIA/Amor A La Mexicana |
| 452 | 1210209 | THALIA/Arrasando |
| 453 | 1210226 | THALIA/Piel Morena#RLM |
| 454 | 1210230 | THALIA/Rosalinda#RLM |
| 455 | 1210275 | The Animals/Dont Let Me Be Misunderstood#RLM [t1000] |
| 456 | 1210337 | The Beatles/Because V.2 |
| 457 | 1210843 | THE BUGGLES/Video Killed The Radio Star#RLM |
| 458 | 1210856 | THE CALLING/Wherever You Will Go#RLM |
| 459 | 1210911 | THE CARPENTERS/They Long To Be (Close To You)#RLM |
| 460 | 1210913 | THE CARPENTERS/This Masquerade#RLM |
| 461 | 1210929 | THE CLUBHOUSE/Im Not Saying A Word#RLM |
| 462 | 1210930 | THE CLUBHOUSE/Memory#RLM |
| 463 | 1210943 | THE CORRS/Breathless#RLM |
| 464 | 1210959 | THE CORRS/Irresistable#RLM |
| 465 | 1210977 | THE CORRS/So Young#RLM |
| 466 | 1210990 | THE CRANBERRIES/Analyse#RLM |
| 467 | 1211001 | THE CRANBERRIES/Dreams#RLM |
| 468 | 1211062 | THE CRANBERRIES/Zombie#RLM |
| 469 | 1211550 | THE SEARCHERS/Love Potion No. 9#RLM |
| 470 | 1211557 | THE SEEKERS/I&#039;Ll Never Find Another You#RLM |
| 471 | 1211628 | THE STEWART/Family Affair#RLM |
| 472 | 1211629 | THE STEWART/Family Affair#RLM |
| 473 | 1211674 | THE TROGGS/Wild Thing#RLM |
| 474 | 1211696 | THE WHO/Go To The Mirror Boy#RLM |
| 475 | 1211697 | THE WHO/I Can See For Miles Now#RLM |
| 476 | 1211699 | THE WHO/Love Reign Ore Me#RLM |
| 477 | 1211701 | THE WHO/Substitute#RLM |
| 478 | 1211702 | THE WHO/The Kids Are Alright#RLM |
| 479 | 1211703 | THE WHO/These Eyes#RLM |
| 480 | 1211704 | THE WHO/Wont Get Fooled Again#RLM |
| 481 | 1211708 | The Yardbirds/Heart Full Of Soul#RLM [t1000] |
| 482 | 1211771 | THIAGUINHO E NEGRA LI/Clareou (Tres Gracas)#RLM |
| 483 | 1211835 | THREE DOG NIGHT/Easy To Be Hard#L |
| 484 | 1212025 | TIERRA DEL SUR/Salve Rociera#RLM |
| 485 | 1212210 | TINA TURNER E ROD STEWART/It Takes Two#RLM |
| 486 | 1212218 | TINA TURNER/On Silent Wings#RLM |
| 487 | 1212225 | TINA TURNER/Simply The Best#RLM |
| 488 | 1212234 | TINA TURNER/We Dont Need Another Hero#RLM V.2 |
| 489 | 1212236 | TINA TURNER/Whats Love Got To Do With It#RLM |
| 490 | 1212266 | TIROMANCINO/Due Destini#RLM |
| 491 | 1212269 | TIROMANCINO/Per Me E Importante#RLM |
| 492 | 1212357 | TIZIANO FERRO/Imbranato#RLM V.2 |
| 493 | 1212533 | TOM JONES/Delilah#RLM |
| 494 | 1212547 | TOM JONES/Sex Bomb#RLM V.2 |
| 495 | 1212549 | TOM JONES/The Green Green Grass Of Home#RLM |
| 496 | 1212565 | TOM PETTY/Into The Great Wide Open#RLM |
| 497 | 1212596 | TOMMY JAMES E THE SHONDELLS/Mony Mony#RLM |
| 498 | 1212613 | TONI BRAXTON/Spanish Guitar#RLM |
| 499 | 1212764 | TONY BENNETT/The Shadow Of Your Smile#RLM |
| 500 | 1212768 | TONY CAREY/Room With A View#RLM |
| 501 | 1212917 | TOQUINHO/Aquarela#RLM V.2 |
| 502 | 1212997 | TORO CUTUGNO/Le Mamme#RLM |
| 503 | 1213016 | TOTO/Africa#RLM |
| 504 | 1213026 | TOTO/Malafemmena#RLM |
| 505 | 1213049 | TRACY CHAPMAN/Baby Can I Hold You Tonight#RLM V.2 |
| 506 | 1213065 | TRADITIONAL BLUEGRASS/Rocky Top#L |
| 507 | 1213073 | Traditional/Deck The Halls (fa La La La)#L |
| 508 | 1213074 | Traditional/Deck The Halls (fa La La La)#L |
| 509 | 1213142 | TRAIA VEIA/Casa Da Vo#RLM |
| 510 | 1213161 | TRAIA VEIA/Toque De Magica#RLM |
| 511 | 1213187 | TRAVIS/Flowers In The Window#RLM |
| 512 | 1213916 | TRIO PARADA DURA E RIONEGRO E SOLIMOES E GILBERTO E GILMAR/Caminheiro#L |
| 513 | 1214099 | TRISHA YEARWOOD/Believe Me Baby (I Lied)#RLM |
| 514 | 1214100 | TRISHA YEARWOOD/Believe Me Baby (I Lied)#RLM |
| 515 | 1214108 | TRUESTEPPERS/Out Of Your Mind#RLM |
| 516 | 1214116 | TULIO DE PISCOPO/Andamento Lento#RLM |
| 517 | 1214158 | TURMA DO PAGODE/Melhor Amigo#RLM |
| 518 | 1214182 | TWILA PARIS/How Beautiful#RLM |
| 519 | 1214199 | U 2/Beautiful Day#RLM |
| 520 | 1214206 | U 2/Electrical Storm#RLM |
| 521 | 1214210 | U 2/Elevation#RLM |
| 522 | 1214229 | U 2/Pride (In The Name Of Love)#RLM |
| 523 | 1214235 | U 2/Stay#L |
| 524 | 1214240 | U 2/Stuck In A Moment You Cant Get Out Of#RLM V.2 |
| 525 | 1214241 | U 2/Sunday Bloody Sunday#L |
| 526 | 1214274 | Ub 40/I Cant Help Falling In Love With You#RLM |
| 527 | 1214320 | Ultravox/Vienna#RLM |
| 528 | 1214323 | UMBERTO BALSAMO/Balla#RLM |
| 529 | 1214327 | UMBERTO BINDI/Il Mio Mondo#RLM |
| 530 | 1214337 | UMBERTO TOZZI/Gli Innamorati#RLM |
| 531 | 1214342 | UMBERTO TOZZI/Io Muoio Di Te#RLM |
| 532 | 1214391 | URIAH HEEP/Firefly#RLM |
| 533 | 1214402 | Usa For Africa/We Are The World#L |
| 534 | 1214417 | V Gill/Go Rest High On The Mountain#L |
| 535 | 1214547 | Valeria Rossi/Luna Di Lana#RLM |
| 536 | 1214548 | Valeria Rossi/Tre Parole#RLM |
| 537 | 1214570 | VALL SYLVA/Beija Flor#RLM |
| 538 | 1214764 | Van Halen/Jump V.2 |
| 539 | 1214782 | Van Morrison/Brown Eyed Girl#RLM |
| 540 | 1214783 | Van Morrison/Domino#L |
| 541 | 1214822 | Vanessa Carlton/A Thousand Miles#RLM |
| 542 | 1214869 | Vanessa Williams/Saved The Best For Last#L |
| 543 | 1215057 | Vasco Rossi/Bollicine#RLM |
| 544 | 1215069 | Vasco Rossi/Ciao#RLM |
| 545 | 1215084 | Vasco Rossi/Gabri#M |
| 546 | 1215093 | Vasco Rossi/Io No#RLM |
| 547 | 1215104 | Vasco Rossi/Liberi Liberi#RLM |
| 548 | 1215112 | Vasco Rossi/Ogni Volta#RLM |
| 549 | 1215116 | Vasco Rossi/Sally#RLM |
| 550 | 1215119 | Vasco Rossi/Senza Parole#RLM |
| 551 | 1215120 | Vasco Rossi/Siamo Soli#RLM |
| 552 | 1215125 | Vasco Rossi/Stupido Hotel#RLM |
| 553 | 1215132 | Vasco Rossi/Una Canzone Per Te#RLM |
| 554 | 1215141 | Vasco Rossi/Vita Spericolata#RLM |
| 555 | 1215167 | Venezuelan/Alma Llanera |
| 556 | 1215168 | Venezuelan/La Portra Zaina |
| 557 | 1215169 | Venezuelan/Mosaico Billos Caracas Boys |
| 558 | 1215170 | Venezuelan/Yo Quiero Ser Como Ariel |
| 559 | 1215176 | Vengaboys/Kiss When The Sun Dont Shine#RLM V.2 |
| 560 | 1215183 | Vengaboys/We Like To Party#RLM |
| 561 | 1215257 | Vianella/Semo Gente De Borgata#L |
| 562 | 1215291 | Vicenzo Palleschi/Canzone Di Una Notte#RLM |
| 563 | 1215292 | Vicenzo Palleschi/La Vita E Strana (ironic)#RLM |
| 564 | 1215293 | Vicenzo Palleschi/Lamento Notturno Di Un Musicista Sul Palco#RLM |
| 565 | 1215294 | Vicenzo Palleschi/Non Piangere Piu (no Woman No Cry)#L |
| 566 | 1215295 | Vicenzo Palleschi/Sara Un Giorno#L |
| 567 | 1215296 | Vicenzo Palleschi/Sei Bellissima Stasera#L |
| 568 | 1215297 | Vicenzo Palleschi/Sei Tu (hey Jude)#RLM |
| 569 | 1215434 | Victor Manuel/Quien Puso Mas |
| 570 | 1215487 | Vila Palma E Vampiros/Autorojo#RLM |
| 571 | 1215488 | Vila Palma E Vampiros/La Pachanga |
| 572 | 1215489 | Vila Palma E Vampiros/La Pachanga V.2 |
| 573 | 1215491 | Village People/In The Navy#L |
| 574 | 1215496 | Village People/Ymca#L |
| 575 | 1215529 | Vince Gill/Look At Us#RLM |
| 576 | 1215682 | Vitti Na Crozza/K-modugno#M |
| 577 | 1215683 | Vitti Na Crozza/K-modugno#RLM |
| 578 | 1215698 | Vivaldi/Dalle 4 Stagioni-la Primavera |
| 579 | 1215699 | Vivaldi/Dalle 4 Stagioni-l&#039;autunno |
| 580 | 1215700 | Vivaldi/Dalle 4 Stagioni-l&#039;estate |
| 581 | 1215701 | Vivaldi/Dalle 4 Stagioni-l&#039;inverno |
| 582 | 1216376 | WESLEY SAFADAO/Borboletas#RLM |
| 583 | 1216482 | Westlife/Uptown Girl#RLM |
| 584 | 1216492 | Wham/Careless Whisper#L |
| 585 | 1216493 | Wham/Everything She Wants#RLM |
| 586 | 1216494 | Wham/If You Were There#RLM |
| 587 | 1216496 | Wham/Last Christmas#RLM V.2 |
| 588 | 1216499 | Wham/Wake Me Up Before You Go-go#RLM |
| 589 | 1216501 | Wheatus/Teenage Dirtbag#RLM |
| 590 | 1216521 | White Town/Your Womam#RLM |
| 591 | 1216526 | Whitesnake/Here I Go Again#RLM |
| 592 | 1216536 | Whitney Houston/Didnt We Almost Have It All#L |
| 593 | 1216541 | Whitney Houston/Greatest Love Of All#L |
| 594 | 1216550 | Whitney Houston/I Have Nothing#RLM |
| 595 | 1216559 | Whitney Houston/I Will Always Love You#RLM |
| 596 | 1216580 | Whitney Houston/Run To You#RLM |
| 597 | 1216583 | Whitney Houston/Saving All My Love For You#RLM |
| 598 | 1216634 | Wilfrido Vargas/A Mover La Colita#L |
| 599 | 1216635 | Wilfrido Vargas/Abusadora#L |
| 600 | 1216636 | Wilfrido Vargas/El Baile Del Perrito#L |
| 601 | 1216678 | William Walford/Sweet Hour Of Prayer#L |
| 602 | 1216690 | Willie Nelson/Crazy#RLM [t1000] |
| 603 | 1216691 | Willie Nelson/Funny How Time Slips Away#RLM [t1000] |
| 604 | 1216754 | Wilson Pickett/In The Midnight Hour#RLM |
| 605 | 1216846 | Woods E Campbell E Connelly/Try A Little Tenderness#L |
| 606 | 1216847 | Woods E Campbell E Connelly/Try A Little Tenderness#L |
| 607 | 1216854 | X Perience/A Never Ending Dream#RLM |
| 608 | 1216957 | Xuxa/Ilarie (ao Vivo)#RLM |
| 609 | 1217020 | YANNI/Santorini V.2 |
| 610 | 1217062 | Yes/Heart Of The Sunrise#RLM |
| 611 | 1217063 | Yes/Holy Lamb#RLM |
| 612 | 1217071 | Yes/Machine Messiah#RLM |
| 613 | 1217147 | Yuri/Todo Mi Corazon |
| 614 | 1217157 | Zanier/Amami#RLM |
| 615 | 1217171 | Zarzuelas/Los Nardos#RLM |
| 616 | 1217202 | ZE FELIPE E ANA CASTELA/Sua Boca Mente (Versao Pagode)#L |
| 617 | 1217203 | ZE FELIPE E ANA CASTELA/Sua Boca Mente#RLM |
| 618 | 1217220 | ZE FELIPE/Meu Grito De Amor (Country Sessions)#RLM |
| 619 | 1217350 | ZE NETO E CRISTIANO/Escondendo Ouro#RLM |
| 620 | 1218366 | Zeze Di Camargo/Me Leva Pra Casa (acustico)#L |
| 621 | 1218367 | ZEZE DI CAMARGO/Me Leva Pra Casa (Rustico Ao Vivo)#L |
| 622 | 1218501 | Zombies/Time Of The Season#RLM |
| 623 | 1218506 | Zucchero/Baila Sexy Thing#RLM |
| 624 | 1218516 | Zucchero/Cosi Celeste#RLM |
| 625 | 1218522 | Zucchero/Diamante#RLM |
| 626 | 1218529 | Zucchero/Dune Mosse#RLM |
| 627 | 1218533 | Zucchero/Hey Man#RLM |
| 628 | 1218538 | Zucchero/Il Mare Impetuoso#RLM |
| 629 | 1218541 | Zucchero/Its All Right#RLM |
| 630 | 1218546 | Zucchero/Miserere#RLM |
| 631 | 1218547 | Zucchero/Music In Me#RLM |
| 632 | 1218548 | Zucchero/Nice Che Dice#RLM |
| 633 | 1218551 | Zucchero/Overdose D Amore#L |
| 634 | 1218553 | Zucchero/Pane, Salame E Birra#RLM |
| 635 | 1218558 | Zucchero/Pippo#RLM |
| 636 | 1218559 | Zucchero/Puro Amore#RLM |
| 637 | 1218756 | ALEXANDRE PIRES/Brincar De Ser Feliz (Pagonejo)#RLM |
| 638 | 1218757 | ALEXANDRE PIRES E LEONARDO/Temporal De Amor (Pagonejo)#RLM |
| 639 | 1218758 | ANA CASTELA E COUNTRYBEAT/Era Sol Que Me Faltava#RLM |
| 640 | 1218759 | ANA CASTELA E ZE FELIPE/Eu So Quero Voce#RLM |
| 641 | 1218760 | AVIOES DO FORRO/Agora E Com Voce#L |
| 642 | 1218767 | GRUPO CHOCOLATE E TURMA DO PAGODE/Alo Virginia (Ao Vivo)#RLM |
| 643 | 1218769 | GUILHERME E BENUTO E LUAN SANTANA/Quarto 67#RLM |
| 644 | 1218772 | ICARO E GILMAR E PANDA E HUMBERTO E RONALDO/Ce Ta Doido#RLM |
| 645 | 1218780 | LUIGI LOPEZ/Pinocchio, Perche No#M |
| 646 | 1218781 | MANEVA/Eu Te Devoro#RLM |
| 647 | 1218782 | MENOS E MAIS/Tempo Perdido#RLM |
| 648 | 1218789 | PINK/Dont Let Me Get Me#RLM V.2 |
| 649 | 1218791 | PINK/You Make Me Sick#RLM |
| 650 | 1218792 | PINK FLOYD/Bran Damage#L |
| 651 | 1218793 | PINK FLOYD/The Tin Ice#L |
| 652 | 1218794 | PINK FLOYD/Wish You Were Here#RLM V.2 |
| 653 | 1218798 | POOH/50 Primavere#RLM |
| 654 | 1218799 | POOH/A Cent Anni Non Si Sbaglia Piu#RLM |
| 655 | 1218800 | POOH/A Un Minuto Dall Amore#RLM |
| 656 | 1218801 | POOH/Alessandra#RLM |
| 657 | 1218802 | POOH/Ali Per Guardare Occhi Per Volare#RLM |
| 658 | 1218803 | POOH/Alle Nove In Centro#RLM |
| 659 | 1218804 | POOH/Amici Per Sempre#RLM |
| 660 | 1218805 | POOH/Aria Di Mezzanotte#RLM |
| 661 | 1218808 | POOH/Cercando Di Te#RLM |
| 662 | 1218813 | SIMONE MENDES/Pra Sempre Ou Por Enquanto#RLM |
| 663 | 1218814 | THE PLATTERS/The Great Pretender#RLM |
| 664 | 1218815 | THE POINTER SISTERS/Im So Excited#RLM |
| 665 | 1218816 | THE POLICE/Dont Stand So Close To Me#RLM V.2 |
| 666 | 1218817 | THE POLICE/Every Breath You Take#RLM V.2 |
| 667 | 1218818 | THE POLICE/Spirits In The Material World#RLM |
| 668 | 1218819 | THE POLICE/Wrapped Around Your Finger#RLM |
| 669 | 1218820 | THIAGUINHO/Ela E Demais (Ao Vivo) |
| 670 | 1218825 | XIRU MISSIONEIRO/Mega Repi Do Guasca#L |
| 671 | 1218939 | CHICOTE DE LUXO/Keyla#Rlm |
| 672 | 1218942 | DI PAULLO E PAULINO/Algemas Invisiveis |
| 673 | 1218943 | EDSON E HUDSON/Por Te Amar Assim |
| 674 | 1218949 | GUILHERME SILVA/Cachorro Pitoco |
| 675 | 1218950 | GUSTTAVO LIMA/Os Coracoes Nao Sao Iguais |
| 676 | 1218954 | JOVANOTTI/Penso Positivo#Rlm |
| 677 | 1218960 | LAURA PAUSINI/Tra Te Eil Mare#Rlm |
| 678 | 1218966 | NATANZINHO LIMA/Sina De Ofelia |
| 679 | 1218967 | OS PARALAMAS DO SUCESSO E DJAVAN/Uma Brasileira#Rlm |
| 680 | 1218968 | PABLO MILANES/Yolanda#Rlm |
| 681 | 1218970 | PAOLINO RUBIO/Vivre El Verano#Rlm |
| 682 | 1218971 | PEACHES/Presidents Of The Usa#Rlm |
| 683 | 1218972 | PEDRO SAMPAIO/Sequencia Feiticeira |
| 684 | 1218973 | PEPPINO DI CAPRI/Auguri |
| 685 | 1218974 | PEPPINO DI CAPRI/Freva#L |
| 686 | 1218975 | PERU FOLCLORE/Picaflor |
| 687 | 1218976 | PERUVIAN/Acuarela Criolla#M |
| 688 | 1218977 | PERUVIAN/Alma Corazon Y Vida#M |
| 689 | 1218978 | PERUVIAN/Cuando Ilora Mi Guitarra |
| 690 | 1218979 | PERUVIAN/El Plebeyo#M |
| 691 | 1218980 | PERUVIAN/Fina Estammpa |
| 692 | 1218981 | PERUVIAN/Hilda |
| 693 | 1218982 | PERUVIAN/La Conchepoerla |
| 694 | 1218983 | PERUVIAN/Las Virgenes Del Sol |
| 695 | 1218984 | PERUVIAN/Mi Peru |
| 696 | 1218985 | PERUVIAN/Nube Gris |
| 697 | 1218986 | PERUVIAN/Odiame |
| 698 | 1218987 | PERUVIAN/Sacachispas |
| 699 | 1218988 | PERUVIAN/Sentimiento |
| 700 | 1218989 | PERUVIAN/Todos Vuelven |
| 701 | 1218990 | PET SHOP BOYS/Always On My Mind#Rlm |
| 702 | 1218991 | PET SHOP BOYS/Go West#Rlm |
| 703 | 1218992 | PET SHOP BOYS/Its A Sin#Rlm |
| 704 | 1218993 | PETER PAUL E MARY/Puff The Magic Dragon#Rlm V.2 |
| 705 | 1218998 | ROKES/Piangi Con Me#Rlm |
| 706 | 1219004 | TURMA DO PAGODE/Deixa Em Off |
| 707 | 1219007 | XANDE DE PILARES/Ainda Bem |

---

## Prioridade 2 — 1 produto com dois nomes de artista

**O que é:** o produto está em duas categorias `product_cat` que são o mesmo artista
escrito de dois jeitos. A migração manteve a primeira e o dado está correto.

**Impacto real: nenhum.** Sugestão: fundir a categoria `ANDRE LEONO` na
`ANDRE LEONNO` no WordPress para evitar recorrência.

### Lista (1 produto)

| post_id | Título | Categorias `product_cat` |
|---:|---|---|
| 1146331 | Andre Leono/Laura | ANDRE LEONNO + ANDRE LEONO |

---

## Apêndice — as divergências de contagem, e por que são benignas

| Métrica | Valor | Leitura |
|---|---:|---|
| `_centralmidi_genero_id` preenchida | 81.852 (esperado 81.145) | +707 = coluna sempre preenchida; vazios recebem sentinela `0`, texto fica vazio |
| `wp_centralmidi_artistas` | 13.625 (anotado 13.627) | 2 a menos por **colisão de nome**: 13.631 `product_cat` com produto, todos já presentes na tabela por nome |
| `midis.artista_id` órfão | **0** | integridade referencial íntegra |
| produtos sem artista (`artista_id=0`) | **0** | todo produto tem artista |
| `_genero` vazio mas com gênero real | **0** | nenhum falso positivo |
| `_demo_audio` vazia | **0** | todo produto tem demo |
| `_classificacao` vazia | 47.045 | complemento normal (81.852 − 34.807 com `rlm`); ver Prioridade 3 |

---

## Prioridade 3 — 2 produtos com RLM inválido — **RESOLVIDO**

**O que era:** 2 produtos tinham `rlm` legado com erro de digitação, que não casou com
nenhum dos 4 valores reconhecidos (`Letra`, `Melodia`, `Letra | Melodia`, `letra`) e
portanto migraram com `_centralmidi_classificacao` **vazia**.

| post_id | Título | RLM legado | Título já terminava em |
|---:|---|---|---|
| 1138802 | GRUPO LENDAS/Agenda Rabiscada (Vaneira)#L | `Lendas` | `#L` |
| 1155982 | BUCHECHA/Fico Assim Sem Voce (Versao 2026)#L | `Lertra` | `#L` |

**Como foram resolvidos:** o próprio título dos dois produtos termina em `#L`, que é a
marcação que o site já usava para "somente Letra". Ambos foram gravados como `L` com
base nessa evidência. Os valores anteriores estavam **vazios**, então nada foi
sobrescrito.

**Estado final:** `L` subiu de 10.506 para **10.508**, e a reconciliação fechou — os
**34.807** produtos que tinham `rlm` legado agora estão 100% classificados, **zero
pendências**. Conferido pelo caminho de leitura real da aplicação (admin e front-end
usam o mesmo `get_post_meta`).

---

## Estado final da migração

Realizado **depois** da geração deste relatório:

- As 4 chaves legadas (`_rlm`, `rlm`, `_url_demo`, `url_demo` — 234.017 linhas) foram
  **apagadas**. Restam **0**.
- Auditoria 1:1 contra o dump de backup: os **81.852** valores de demo conferem
  exatamente com o original, um a um.
- ACF desativado (`active_plugins` 17 → 16). Player, selo RLM, estatísticas e
  salvamento no admin testados **sem** o ACF: todos funcionam.
- O painel **Migração** foi removido do admin, junto com os 4 endpoints AJAX de
  analyze/prepare/batch/reset — depois de apagar as chaves legadas, um re-run leria
  chaves inexistentes e zeraria as `_centralmidi_*`.

**Conclusão:** nenhuma pendência justifica reverter a migração. As Priorities 1 e 2
são cosméticas e podem ser tratadas no dia a dia; a Priority 3 está fechada.
