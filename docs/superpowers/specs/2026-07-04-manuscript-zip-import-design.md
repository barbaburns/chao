# Design: Importacao de Manuscritos por ZIP com ALTO XML

## Resumo

Esta etapa redefine o fluxo editorial dos manuscritos para eliminar dependencias de servicos externos e processamento em worker. O `WordPress` passa a ser responsavel por receber, validar e importar um pacote `.zip` enviado pela equipe diretamente na pagina do `manuscrito`.

Cada pacote deve conter:

- imagens das paginas
- arquivos `ALTO XML`

Cada imagem deve ter um arquivo XML correspondente com o mesmo nome-base. O formato esperado do nome pode incluir o identificador do manuscrito seguido da numeracao da pagina. Exemplo:

- `venanciaCaderno_0051B.jpg`
- `venanciaCaderno_0051B.xml`
- `venanciaCaderno_0052A.jpg`
- `venanciaCaderno_0052A.xml`

O texto extraido por HTR continua invisivel no frontend. Ele sera usado apenas para:

- indexacao de busca por palavra
- determinacao dos highlights sobre a imagem no visualizador

Este fluxo substitui a abordagem anterior baseada em `PDF mestre`, `worker`, `eScriptorium` e `Kraken`.

## Objetivos

- Permitir importacao editorial de manuscritos com cerca de `400` paginas por lote.
- Usar um unico arquivo `.zip` como entrada principal do manuscrito.
- Parear automaticamente imagem e `ALTO XML` pelo mesmo nome-base.
- Extrair do `ALTO XML` o texto pesquisavel e as coordenadas usadas no viewer.
- Manter o texto HTR fora da interface publica.
- Preservar a busca por palavra com highlights no `OpenSeadragon`.

## Fora de Escopo

- Upload individual de centenas de paginas como fluxo principal.
- Dependencia de servicos externos de HTR.
- Reprocessamento automatico via fila ou worker.
- Suporte a multiplos formatos de HTR nesta fase.
- Edicao manual de coordenadas no admin.
- OCR ou HTR executado dentro do tema.

## Entrada Editorial

### Campo principal

Cada post do tipo `manuscrito` tera um campo ACF de arquivo para receber o pacote `.zip`.

Esse campo passa a ser a fonte oficial das paginas do manuscrito nesta fase.

### Formato esperado do ZIP

O pacote deve conter:

- arquivos de imagem por pagina
- arquivos `ALTO XML` por pagina

Regras do pacote:

- cada pagina deve ter exatamente uma imagem e exatamente um XML com o mesmo nome-base
- a ordenacao das paginas sera derivada do nome-base ordenado alfabeticamente
- subpastas podem ser rejeitadas nesta fase para manter a importacao simples e previsivel
- extensoes de imagem aceitas devem ser explicitamente limitadas, por exemplo `jpg`, `jpeg`, `png`, `webp`
- a extensao do HTR aceita nesta fase sera apenas `.xml`

Exemplo valido:

- `venanciaCaderno_0051B.jpg`
- `venanciaCaderno_0051B.xml`
- `venanciaCaderno_0052A.jpg`
- `venanciaCaderno_0052A.xml`

Exemplos invalidos:

- `0001.jpg` sem `0001.xml`
- `0001.xml` sem `0001.jpg`
- dois arquivos de imagem para o mesmo basename
- dois XML para o mesmo basename

## Fluxo Editorial

### Cadastro

1. A equipe cria ou edita um `manuscrito`.
2. Faz upload do pacote `.zip` no campo ACF.
3. Salva o post.
4. O admin reconhece o pacote salvo e habilita a acao de importacao.
5. A equipe aciona `Importar pacote`.
6. O sistema valida o pacote e importa as paginas validas para a estrutura interna do manuscrito.
7. O admin exibe um resumo da importacao.

### Momento da importacao

Para reduzir risco editorial, a importacao nao deve acontecer silenciosamente em qualquer salvamento. O fluxo recomendado e:

1. a equipe salva o manuscrito com o `.zip`
2. o admin exibe um botao explicito como `Importar pacote`
3. a importacao roda de forma sincronizada na acao do admin
4. o manuscrito registra o resultado da ultima importacao

Essa abordagem evita processamento pesado em autosave e deixa claro quando o sistema esta substituindo as paginas internas do manuscrito.

### Regra de reimportacao

Quando um novo `.zip` for importado com sucesso, ele substitui o snapshot anterior de paginas desse manuscrito.

O sistema deve:

- descartar a estrutura interna de paginas importadas anteriormente
- reconstruir a colecao de paginas a partir do novo pacote
- manter o historico minimo da ultima importacao, como data, status e resumo de erros

## Estrutura Interna

As paginas do manuscrito continuam sendo dados internos, nao posts separados.

Cada pagina deve armazenar ao menos:

- `page_number`
- `basename`
- `image_id`
- `image_url`
- `image_width`
- `image_height`
- `raw_text`
- `layout_data`
- `import_status`
- `import_errors`

### Regra de numeracao

O numero da pagina sera derivado da ordem alfabetica dos basenames validados no pacote.

Isso evita dependencia de um campo numerico dentro do XML e torna o lote previsivel desde que a equipe use nomes consistentes. O basename nao precisa ser apenas numerico; ele pode seguir o padrao `nomeDoManuscrito_numeracao`, como:

- `venanciaCaderno_0051B`
- `venanciaCaderno_0052A`
- `venanciaCaderno_0052B`

Como a ordenacao depende do basename, a equipe deve manter um padrao estavel de prefixo e numeracao zero-padded para evitar inversoes.

## Interpretacao do ALTO XML

Nesta fase, o sistema assume que o HTR vira sempre em `ALTO XML`.

Do XML, o parser deve extrair:

- dimensoes da pagina
- texto por linha
- coordenadas por linha
- coordenadas por palavra, quando existirem

### Formato interno alvo

Cada pagina deve ser normalizada para uma estrutura interna equivalente a:

```json
{
  "text": "texto integral da pagina",
  "lines": [
    {
      "text": "Primeira linha",
      "bbox": { "x": 120, "y": 220, "width": 880, "height": 72 },
      "words": [
        {
          "text": "Primeira",
          "bbox": { "x": 120, "y": 220, "width": 210, "height": 72 }
        }
      ]
    }
  ]
}
```

### Requisito para highlights exatos

Para suportar highlight exato por ocorrencia no frontend, o XML importado precisa fornecer segmentacao suficiente para montar `words[].bbox`.

Se um lote vier apenas com geometria de linha e sem coordenadas confiaveis por palavra, o sistema pode:

- importar o manuscrito
- indexar o texto
- marcar a pagina como sem highlight exato por palavra

Nesta fase, o comportamento recomendado e nao bloquear o lote inteiro por isso, mas registrar claramente que o manuscrito tera apenas busca textual e highlights aproximados ou indisponiveis, conforme o parser conseguir derivar.

## Validacao do Pacote

Antes de importar, o sistema deve validar:

- existencia do arquivo `.zip`
- leitura do pacote
- ausencia de subpastas, se essa regra for adotada
- existencia de pares `imagem + xml` por basename
- ausencia de duplicidade por basename
- formato de imagem permitido
- XML bem formado
- presenca minima de texto e geometria esperada no `ALTO XML`

### Resultado da validacao

O admin deve receber um resumo claro com:

- quantidade total de paginas detectadas
- quantidade de pares validos
- quantidade de imagens sem XML
- quantidade de XML sem imagem
- quantidade de arquivos invalidos
- paginas importadas com sucesso
- paginas importadas sem suporte a highlight exato

Se houver erro estrutural grave no pacote, a importacao deve falhar de modo visivel, sem deixar o manuscrito em estado parcialmente opaco para a equipe.

## Integracao com o Frontend

O frontend continua exibindo:

- viewer por pagina
- busca por palavra
- resultados por pagina

O frontend nao exibe a transcricao integral.

O backend usa o `raw_text` e o `layout_data` importados do `ALTO XML` para:

- indexar o texto de cada pagina
- localizar ocorrencias da busca
- construir `highlight_boxes`
- alimentar o payload do viewer

## Viewer e Highlights

O visualizador continua baseado em `OpenSeadragon`.

Ao clicar em um resultado:

- a pagina correspondente abre no viewer
- a imagem e enquadrada na tela inteira
- todas as ocorrencias encontradas naquela pagina aparecem destacadas ao mesmo tempo

Se a pagina nao tiver dados suficientes para highlight exato:

- o sistema pode usar fallback aproximado por linha, quando possivel
- caso contrario, abre a pagina correta sem overlay

O importante e que o manuscrito continue navegavel e pesquisavel mesmo quando parte do lote tiver qualidade geometrica inferior.

## Admin e Observabilidade

O metabox atual orientado a `worker` deve ser substituido por um metabox orientado a importacao.

Esse painel deve mostrar:

- arquivo ZIP atual
- acao `Importar pacote`
- status da ultima importacao
- total de paginas importadas
- resumo de erros e avisos
- tabela de paginas importadas

Cada linha da tabela pode mostrar:

- numero da pagina
- basename
- preview da imagem
- status da importacao
- status do highlight
- trecho curto do texto indexado

## Persistencia

O resultado da importacao deve ser salvo em estrutura estavel no proprio `WordPress`, preferencialmente em post meta do manuscrito nesta fase, para reduzir escopo.

Metadados recomendados:

- referencia ao ZIP atual
- snapshot das paginas importadas
- resumo da ultima importacao
- erros da ultima importacao
- data da ultima importacao

## Acessibilidade e UX Editorial

- O fluxo deve deixar claro quando o ZIP foi apenas salvo e quando foi realmente importado.
- Mensagens de erro devem apontar o basename problematico.
- O sistema deve evitar textos tecnicos excessivos para a equipe editorial.
- O fluxo deve permitir repetir a importacao apos corrigir o pacote.

## Testes

Cobrir esta etapa com testes de contrato e unidade para garantir:

- o admin reconhece o campo ZIP como origem oficial do manuscrito
- o importador pareia imagem e XML por basename
- a ordenacao das paginas segue o basename
- XML invalido gera erro controlado
- paginas validas produzem `raw_text` e `layout_data`
- o frontend continua recebendo os dados necessarios para busca e viewer
- a transcricao nao aparece publicamente
- highlights exatos dependem de `words[].bbox`

## Riscos e Mitigacoes

### Pacotes muito grandes

Risco: importacao sincronica de `400` paginas ficar lenta no admin.

Mitigacao: manter a importacao por acao explicita e organizar o codigo para permitir evolucao futura para processamento em etapas, sem assumir isso agora.

### Lotes com XML inconsistente

Risco: alguns lotes terem texto valido, mas geometria insuficiente para highlight exato.

Mitigacao: separar status de `texto indexado` e `highlight exato`, evitando rejeitar material util quando apenas o overlay estiver incompleto.

### Erro humano no nome dos arquivos

Risco: imagens e XML nao parearem corretamente.

Mitigacao: validacao automatica por basename e relatorio claro no admin antes de considerar o manuscrito importado com sucesso.

### Reimportacao destrutiva

Risco: um novo ZIP sobrescrever paginas antigas sem percepcao editorial.

Mitigacao: usar acao explicita de importacao e exibir aviso de substituicao antes de processar o novo pacote.
