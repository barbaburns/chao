# Design: Busca do Cliente com Highlights no Viewer de Manuscritos

## Resumo

Esta etapa define o fluxo publico de consulta aos manuscritos. O cliente tera acesso apenas as imagens do manuscrito e a uma busca por palavra. O texto extraido por HTR continuara sendo um dado interno de indexacao: ele nao sera exibido no frontend, mas servira para localizar ocorrencias e desenhar highlights diretamente sobre a imagem da pagina encontrada.

O viewer continuara baseado em `OpenSeadragon`, com navegacao por pagina. Quando houver busca, o sistema abrira a pagina inteira ajustada na tela e exibira todas as ocorrencias encontradas naquela pagina ao mesmo tempo.

## Objetivos

- Manter o texto HTR fora da interface publica.
- Permitir busca exata por palavra dentro do manuscrito aberto.
- Abrir a pagina correta no viewer ao clicar em um resultado.
- Exibir highlights visuais sobre a imagem da pagina.
- Mostrar todas as ocorrencias encontradas na mesma pagina.
- Preservar a navegacao atual do viewer com `OpenSeadragon`.

## Fora de Escopo

- Exibir a transcricao integral no frontend.
- Navegacao ocorrencia por ocorrencia dentro da mesma pagina.
- Busca fuzzy, aproximada ou semantica.
- Edicao manual de highlights no frontend.
- Indexacao global entre varios manuscritos.

## Experiencia do Cliente

### Estado padrao

Na pagina do manuscrito, o cliente ve:

- o viewer com a imagem da pagina
- a navegacao por paginas
- o formulario de busca por palavra

Sem busca ativa, o viewer permanece no comportamento atual, sem highlights visiveis.

### Busca

Quando o cliente envia uma palavra:

1. o backend pesquisa a palavra no texto efetivo de cada pagina
2. o resultado lista apenas as paginas com ocorrencia
3. cada resultado aponta para a pagina correspondente no viewer
4. ao abrir a pagina, o viewer mostra a pagina inteira ajustada na tela
5. os highlights da pagina ativa aparecem sobre a imagem

Se a palavra aparecer varias vezes na mesma pagina, todas as ocorrencias ficam destacadas ao mesmo tempo.

### Mudanca de pagina

- Ao navegar para uma pagina sem ocorrencias da busca atual, nenhum highlight deve permanecer visivel.
- Ao voltar para uma pagina com ocorrencias, os highlights daquela pagina devem reaparecer.
- O viewer nao deve dar zoom automatico em uma palavra especifica nesta fase. A pagina deve abrir em enquadramento completo.

## Dados e Contrato

O backend ja calcula `highlight_boxes` por pagina a partir do HTR. Nesta etapa, esses dados voltam a ser publicados para o payload do viewer, mas sem expor a transcricao ao cliente.

Cada pagina enviada ao frontend deve conter:

- `pageNumber`
- `imageUrl`
- `imageAlt`
- `highlightBoxes`

Cada item de `highlightBoxes` deve conter, no minimo:

- `x`
- `y`
- `width`
- `height`
- `approximate`
- `box_source`

As coordenadas continuam no sistema original da imagem, para permitir mapeamento consistente no `OpenSeadragon`.

## Arquitetura Frontend

### Template

A view `content-single-manuscrito` deve continuar serializando os dados das paginas para o viewer, mas agora incluindo novamente `highlightBoxes` no JSON usado pelo JavaScript.

Nenhum bloco de transcricao deve ser renderizado no frontend publico.

### Viewer

O route `single-manuscrito.js` continua responsavel por:

- inicializar o `OpenSeadragon`
- trocar paginas
- sincronizar botoes de navegacao
- abrir a pagina correta quando o cliente clicar em um resultado

Ele passa a assumir tambem:

- limpar highlights antigos antes de abrir uma nova pagina
- desenhar os highlights da pagina ativa usando overlays nativos do `OpenSeadragon`
- reaplicar os highlights corretos sempre que a pagina ativa mudar
- manter o enquadramento da pagina inteira ao abrir uma nova imagem

### Estilo visual

Os highlights devem ser claramente visiveis sobre a imagem, com contraste suficiente em fundos claros ou escuros. O estilo pode usar:

- contorno marcado
- preenchimento translucido
- raio discreto nos cantos

O objetivo e sinalizar a regiao encontrada sem encobrir a leitura da imagem.

## Arquitetura Backend

O backend continua usando `effective_text` apenas para busca e usando `layout_data` apenas para coordenadas.

As regras permanecem:

- `reviewed_text` tem prioridade sobre `raw_text`
- `highlight_boxes` sao derivados do `layout_data`
- o frontend nao recebe nem renderiza o texto integral como conteudo da pagina

Se nao houver coordenadas suficientes para uma ocorrencia, o sistema pode continuar usando os fallbacks ja existentes para caixas aproximadas por linha.

## Acessibilidade e Comportamento

- O fluxo de busca deve continuar acessivel por teclado.
- O clique em um resultado deve manter o viewer como regiao focal do fluxo.
- Highlights nao devem depender exclusivamente de hover.
- A pagina atual deve continuar sendo anunciada como hoje.

## Testes

Cobrir esta etapa com testes de contrato para garantir:

- o payload do viewer inclui `highlightBoxes`
- a transcricao nao aparece na view publica
- o JavaScript limpa highlights ao trocar de pagina
- o JavaScript desenha highlights apenas quando a pagina ativa possui ocorrencias
- o clique em resultado continua abrindo a pagina correta
- o viewer continua em modo pagina inteira ao abrir resultados

## Riscos e Mitigacoes

### Sincronizacao visual no viewer

Risco: highlights desalinharem com a imagem durante zoom e pan.

Mitigacao: usar overlays nativos do `OpenSeadragon` em vez de uma camada HTML posicionada manualmente.

### Poluicao visual

Risco: paginas com muitas ocorrencias ficarem carregadas demais.

Mitigacao: usar destaque translucido e enquadramento em pagina inteira, sem zoom automatico por ocorrencia.

### Regressao no fluxo atual

Risco: a migracao quebrar a navegacao ou a abertura por resultado.

Mitigacao: preservar os hooks atuais do viewer e ampliar os testes de contrato existentes.
