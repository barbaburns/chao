# Design: Single de Manuscrito com Layout de Single Post

## Resumo

A `single` de `manuscrito` deve passar a seguir a mesma estrutura visual base da `single-post`, mas preservando o comportamento funcional ja existente do manuscrito: imagem destacada, texto introdutorio, busca por palavras, viewer com `OpenSeadragon`, resultados de busca e livros relacionados.

O objetivo nao e transformar o manuscrito em um post editorial comum, e sim reutilizar a mesma malha visual do tema para reduzir divergencia de layout e manter consistencia entre tipos de conteudo.

## Objetivos

- Alinhar a `single-manuscrito` ao layout visual da `single-post`.
- Exibir `featured image` do manuscrito na mesma posicao visual usada em posts.
- Exibir o texto do manuscrito como conteudo principal antes do viewer.
- Exibir o campo ACF `livros_relacionados` com o mesmo componente visual ja usado no tema.
- Preservar busca, viewer, highlights e resultados de busca do manuscrito.

## Fora de Escopo

- Reutilizar comentarios da `single-post`.
- Reutilizar posts relacionados da `single-post`.
- Alterar o fluxo de importacao ZIP ou indexacao do manuscrito.
- Mudar o comportamento do viewer alem do necessario para encaixe visual.

## Estrutura Visual Alvo

A `single-manuscrito` deve espelhar a hierarquia visual da `single-post`:

1. `page-header`
2. `post-header`
3. `feautured-image`
4. coluna lateral
5. area principal de conteudo

### Distribuicao dos blocos

#### Cabecalho

O titulo do manuscrito deve ocupar o mesmo papel visual do `post-header` na `single-post`.

Esse bloco deve conter:

- titulo do manuscrito

Nao e necessario reproduzir categorias, tags, autor ou metadata editoriais da `single-post`.

#### Coluna esquerda

A coluna esquerda deve seguir esta ordem:

1. `featured image`
2. `Busca no manuscrito`
3. `Obras relacionadas`

Essa ordem foi aprovada para manter primeiro o contexto visual do manuscrito e, em seguida, os blocos de navegacao e descoberta.

#### Coluna principal

A coluna principal deve seguir esta ordem:

1. texto do manuscrito
2. viewer
3. resultados da busca, quando houver

O texto entra antes do viewer para que a pagina mantenha uma leitura editorial semelhante a `single-post` antes da exploracao visual do fac-simile.

## Dados Necessarios

O backend da `single-manuscrito` deve continuar entregando o payload atual de busca e viewer.

Adicionalmente, deve passar para o template:

- `livros_relacionados`, lido do campo ACF `livros_relacionados`

O formato ideal e o mesmo ja aceito por `resources/views/components/books-list.blade.php`, para evitar criar um segundo componente de obras relacionadas.

## Template

O template `resources/views/partials/content-single-manuscrito.blade.php` deve ser reorganizado para seguir a estrutura base da `single-post`.

### Estrutura esperada

- `page-header`
- `header.post-header`
- `figure.feautured-image`, quando existir thumbnail
- bloco lateral com busca e `books-list`
- bloco principal com texto, viewer e resultados

O viewer deve continuar com seus hooks de `data-*`, `script` com JSON de paginas e navegacao atual, sem regressao funcional.

## Estilos

O grid de `single-manuscrito` deve reaproveitar a mesma logica visual de `single-post`, sem copiar comentarios ou areas inexistentes.

### Ajustes previstos

- atualizar `resources/assets/styles/layouts/_grid.scss` para incluir `single-manuscrito` em uma malha equivalente a `single-post`
- ajustar `resources/assets/styles/layouts/_manuscripts.scss` para o novo encaixe estrutural
- reaproveitar o visual existente de `post-header`, `feautured-image` e `related-books`

O CSS especifico de viewer, busca e resultados permanece em `_manuscripts.scss`.

## Comportamento Responsivo

Em `desktop`:

- coluna esquerda com imagem, busca e livros relacionados
- coluna direita com texto, viewer e resultados

Em `mobile`:

- mesma ordem vertical da `single-post`
- `page-header`
- `featured image`
- `post-header`
- conteudo textual
- busca
- viewer
- resultados
- livros relacionados

Se a estrutura exata de `mobile` precisar respeitar a malha atual do tema, a prioridade e preservar legibilidade e proximidade entre busca e viewer.

## Riscos e Cuidados

- O template atual do manuscrito nao segue a mesma hierarquia de classes da `single-post`, entao a simples troca de classes pode causar regressao de espacamento.
- O componente `books-list` depende do formato de dados ja usado em autores e posts; o campo `livros_relacionados` deve ser normalizado para esse formato.
- A imagem destacada nao deve conflitar visualmente com a area do viewer, que continua sendo o elemento principal da experiencia.

## Testes

Cobrir ao menos:

- renderizacao do `featured image` do manuscrito
- renderizacao do texto antes do viewer
- renderizacao do bloco `related-books` quando `livros_relacionados` existir
- preservacao dos hooks do viewer e da busca
- ausencia de dependencia de comentarios ou posts relacionados

## Resultado Esperado

A `single-manuscrito` passa a parecer parte da mesma familia visual da `single-post`, mas continua funcionando como uma pagina de consulta de manuscrito, com busca lateral, viewer principal e obras relacionadas no mesmo padrao do restante do tema.
