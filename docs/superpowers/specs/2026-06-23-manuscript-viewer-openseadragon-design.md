# Manuscript Viewer OpenSeadragon Design

## Goal

Migrar o viewer da `single-manuscrito` do tema de uma implementação custom baseada em `img` + `transform: scale()` para `OpenSeadragon`, preservando a navegacao por paginas e o comportamento de abrir diretamente na pagina correta a partir da busca textual.

## Scope

Esta primeira fase vai:

- substituir o viewer custom atual por `OpenSeadragon`
- manter navegacao de paginas no frontend
- manter integracao com os resultados da busca, abrindo a pagina correta ao clicar em um resultado
- manter o indice/lista de paginas do viewer
- simplificar os controles do viewer para o modelo do `OpenSeadragon`

Esta primeira fase nao vai:

- portar `highlight_boxes` para overlays do `OpenSeadragon`
- manter os modos custom de escala `fit-width`, `fit-height` e `100%`
- redesenhar o fluxo editorial ou a busca textual no backend
- introduzir IIIF, Deep Zoom tiles, fullscreen ou anotacoes

## Current State

Hoje a `single-manuscrito` renderiza um viewer custom em `resources/views/partials/content-single-manuscrito.blade.php`, com logica JS em `resources/assets/scripts/routes/single-manuscrito.js` e estilos em `resources/assets/styles/layouts/_manuscripts.scss`.

O viewer atual:

- carrega uma imagem de pagina por vez
- controla escala manualmente com `transform: scale(...)`
- oferece botoes de navegacao entre paginas
- reposiciona o viewer para a pagina correta quando o usuario clica em um resultado de busca
- desenha destaques visuais a partir de `highlight_boxes`

Esse modelo funciona, mas concentra bastante logica manual de viewport, zoom e renderizacao, onde `OpenSeadragon` ja oferece uma base mais robusta.

## Proposed Approach

Vamos substituir o canvas custom atual por um container dedicado ao `OpenSeadragon`. Cada pagina continuara sendo representada como uma imagem comum (`image_url`), sem exigencia de IIIF ou tiles nesta fase.

No frontend:

- a rota `single-manuscrito` inicializa uma instancia unica de `OpenSeadragon`
- ao trocar de pagina, o viewer abre a imagem correspondente
- os botoes `Anterior` e `Proxima` continuam existindo
- o indice de paginas continua existindo
- os links dos resultados da busca continuam apontando para `#manuscript-viewer` e passam a sincronizar a pagina ativa no `OpenSeadragon`

No backend:

- `Search::buildViewModel()` continua resolvendo `initial_page_number`
- a estrutura de `pages` continua fornecendo `page_number` e `image_url`
- `highlight_boxes` podem continuar sendo gerados sem uso imediato no frontend, evitando retrabalho backend nesta fase

## UI Changes

O layout da `single-manuscrito` sera ajustado para refletir um viewer mais simples:

- o bloco principal do viewer passa a conter apenas o container do `OpenSeadragon`
- a toolbar remove os controles custom de escala (`fit-width`, `fit-height`, `100%`, `+`, `-`)
- a navegacao de pagina permanece visivel e separada do canvas do viewer
- o texto auxiliar do viewer passa a orientar o usuario a usar o zoom/pan nativo do visualizador

Os resultados de busca permanecem listados abaixo e continuam funcionando como pontos de entrada para abrir uma pagina especifica.

## Technical Design

### Frontend Script

`resources/assets/scripts/routes/single-manuscrito.js` sera simplificado para:

- importar e inicializar `OpenSeadragon`
- ler o JSON de paginas ja renderizado no Blade
- criar e manter um estado minimo com `activeIndex`
- abrir a pagina ativa no viewer usando `viewer.open({ type: 'image', url: page.imageUrl })` ou equivalente suportado pela biblioteca
- atualizar `Anterior`, `Proxima` e o indice ativo
- sincronizar cliques em `data-manuscript-go-page` com a pagina aberta

Saem dessa fase:

- logica manual de `scaleMode`
- `manualScale`
- `applyScale()`
- overlay DOM de highlights
- renderizacao de `manuscript-single__viewer-highlight`

### Blade Markup

`resources/views/partials/content-single-manuscrito.blade.php` sera ajustado para:

- manter o JSON de paginas e o `data-initial-page-number`
- trocar o frame/canvas custom por um container unico para `OpenSeadragon`
- remover a label de highlights e os botoes de escala que nao serao usados
- manter navegacao de pagina e indice

### Styles

`resources/assets/styles/layouts/_manuscripts.scss` sera atualizado para:

- estilizar o container do `OpenSeadragon`
- garantir altura util, borda e area de respiro adequadas
- remover estilos mortos do overlay antigo
- preservar responsividade da toolbar e da navegacao

## Dependency Change

Sera adicionada a dependencia `openseadragon` ao tema via `package.json`.

Como o projeto usa o pipeline atual de webpack do Sage 9, a integracao deve seguir o mesmo padrao das demais dependencias JS ja presentes no tema.

## Error Handling

Se o viewer nao encontrar paginas validas com `image_url`, a rota deve sair sem inicializar `OpenSeadragon`, preservando o fallback atual do template.

Se uma pagina do indice ou um resultado de busca apontar para um numero de pagina inexistente, o JS deve ignorar a acao sem quebrar o viewer.

Se uma imagem falhar ao abrir no `OpenSeadragon`, a navegacao do restante da pagina deve continuar funcional. Nesta fase nao sera criado um estado visual detalhado de erro alem do comportamento padrao da biblioteca.

## Testing Strategy

Vamos validar a migracao em tres camadas:

- teste de integracao/source-based cobrindo o markup do viewer e a inicializacao da rota `single-manuscrito`
- `npm run build` para verificar bundling com a nova dependencia
- verificacao manual do fluxo principal: abrir a `single-manuscrito`, navegar entre paginas e clicar em um resultado de busca para abrir a pagina correta

Nao faz parte desta fase testar overlays de highlights, porque eles serao removidos da implementacao inicial com `OpenSeadragon`.

## Files Expected To Change

- `package.json`
- `yarn.lock`
- `resources/views/partials/content-single-manuscrito.blade.php`
- `resources/assets/scripts/routes/single-manuscrito.js`
- `resources/assets/styles/layouts/_manuscripts.scss`
- possivelmente testes focados do fluxo `single-manuscrito`

## Success Criteria

Esta fase sera considerada pronta quando:

- a `single-manuscrito` usar `OpenSeadragon` como viewer
- o usuario puder dar zoom e pan no manuscrito com o comportamento nativo da biblioteca
- a navegacao entre paginas continuar funcional
- clicar em um resultado de busca abrir a pagina correta no viewer
- o build do tema continuar passando
- nao houver dependencia do overlay de highlights no frontend
