# Design: Sessao de Manuscritos com HTR e Busca no Visualizador

## Resumo

Este documento define a primeira versao de uma sessao de manuscritos para o site, inspirada em bibliotecas digitais de acervo historico. Cada manuscrito sera um item unico com um PDF mestre como entrada editorial principal. O sistema extraira as paginas automaticamente, processara cada pagina por meio de uma integracao com `eScriptorium + Kraken`, permitira revisao manual da transcricao no WordPress e oferecera, no visualizador, busca local por palavra exata com indicacao da pagina e destaque da regiao correspondente na imagem.

O desenho prioriza:

- colocacao em producao com risco tecnico controlado
- suporte editorial dentro do WordPress
- busca confiavel sobre texto revisado
- independencia de fornecedor proprietario

## Objetivos

- Permitir cadastro de manuscritos multipagina no WordPress a partir de um PDF unico.
- Processar automaticamente cada pagina com HTR.
- Permitir revisao e correcao manual do texto por pagina.
- Exibir um visualizador com navegacao de paginas e controles de escala.
- Implementar busca local por palavra exata dentro do manuscrito aberto.
- Mostrar resultados da busca com pagina e destaque visual sobre a imagem.

## Fora de Escopo na V1

- Busca aproximada, fuzzy search ou tolerancia automatica a erro.
- Edicao manual de bounding boxes.
- Busca global em todo o acervo.
- Treinamento fino de modelos proprios do Kraken na V1.
- Rotacao, recorte ou limpeza avancada de imagem dentro do visualizador.

## Arquitetura Geral

O sistema sera dividido em quatro camadas:

1. Camada editorial no WordPress para cadastro e revisao.
2. Camada de processamento em background para envio de paginas ao servico HTR `eScriptorium + Kraken`.
3. Camada de armazenamento neutro dos resultados da transcricao.
4. Camada de visualizacao no frontend com busca e highlights.

O frontend nao deve depender do formato bruto de nenhum fornecedor. O WordPress deve normalizar a resposta do provedor para um formato interno unico.

## Stack HTR Escolhida

A V1 usara `eScriptorium + Kraken` como stack de HTR.

Papel de cada componente:

- `WordPress`: cadastro do manuscrito, upload do PDF, acompanhamento de status, revisao editorial e publicacao.
- `eScriptorium`: servico especializado para ingestao tecnica das paginas, predicao, gestao de modelos e API de processamento.
- `Kraken`: motor de reconhecimento e segmentacao usado por baixo do eScriptorium.

Na V1, o fluxo editorial continua centrado no WordPress. O eScriptorium entra como servico integrado de processamento, nao como interface principal da equipe.

## Modelo de Conteudo

### Entidade principal

Criar um CPT `manuscrito`.

Cada `manuscrito` representa um documento completo e contem:

- titulo
- subtitulo opcional
- descricao ou nota editorial
- metadados basicos como autor, data, origem e colecao
- `pdf_master_id`
- conjunto ordenado de paginas derivadas
- status editorial do manuscrito

### Estrutura de paginas

As paginas pertencem ao manuscrito como dados internos, nao como posts separados. A equipe editorial nao deve cadastrar pagina por pagina manualmente. O sistema deve gerar essa estrutura automaticamente a partir do PDF mestre, preservando ordenacao estavel por pagina.

As paginas derivadas podem ser persistidas em post meta, tabela auxiliar ou outra estrutura interna equivalente. O importante e que a experiencia editorial seja baseada em upload de PDF, nao em montagem manual de dezenas de itens.

Cada pagina deve armazenar:

- `image_id`
- `page_number`
- `htr_status`
- `htr_provider`
- `processed_at`
- `htr_confidence`
- `raw_text`
- `reviewed_text`
- `effective_text`
- `layout_data`
- `error_message`

### Regra de texto pesquisavel

`effective_text` segue esta prioridade:

1. `reviewed_text`, quando existir e tiver sido aprovado
2. `raw_text`, quando ainda nao houver revisao

O frontend sempre pesquisa em `effective_text`.

## Formato Interno da Transcricao

Cada pagina precisa ser persistida em formato neutro, independente do fornecedor:

```json
{
  "page": 12,
  "image": {
    "id": 345,
    "width": 2400,
    "height": 3600,
    "url": "..."
  },
  "text": "texto integral da pagina",
  "confidence": 0.93,
  "lines": [
    {
      "id": "l1",
      "text": "Primeira linha do manuscrito",
      "bbox": { "x": 120, "y": 220, "width": 880, "height": 72 },
      "words": [
        {
          "id": "w1",
          "text": "Primeira",
          "bbox": { "x": 120, "y": 220, "width": 210, "height": 72 },
          "confidence": 0.95
        }
      ]
    }
  ]
}
```

Requisitos para esse formato:

- guardar coordenadas na escala original da imagem
- permitir highlight por linha e por palavra
- preservar confianca por bloco quando o provedor retornar esse dado
- permitir armazenar texto bruto e texto revisado por linha

## Fluxo Editorial no WordPress

### Cadastro

1. A equipe cria um `manuscrito`.
2. Faz upload de um `PDF mestre` do manuscrito.
3. Salva o manuscrito.
4. O sistema extrai automaticamente as paginas do PDF.
5. O sistema gera as imagens derivadas por pagina.
6. Aciona o processamento HTR ou deixa o sistema enfileirar automaticamente.

### Ingestao do PDF

Ao receber o PDF mestre, o sistema deve:

1. validar que o arquivo e um PDF suportado
2. identificar o numero de paginas
3. renderizar cada pagina como imagem derivada em resolucao adequada
4. criar a estrutura interna ordenada de paginas do manuscrito
5. armazenar referencia entre manuscrito, PDF mestre e paginas derivadas

Essa etapa existe para reduzir trabalho editorial e organizar melhor a biblioteca de midia. Para a equipe, o manuscrito entra como um unico PDF. Para o sistema, ele passa a existir como um conjunto de paginas processaveis.

### Processamento

Para cada pagina:

1. O WordPress envia a imagem derivada de cada pagina para a camada de integracao com `eScriptorium`.
2. Recebe texto, coordenadas e metadados.
3. Normaliza a resposta para o formato interno.
4. Salva o resultado na pagina correspondente.
5. Atualiza o status da pagina.

Essa integracao pode funcionar com polling ou jobs assincronos, desde que o WordPress nao bloqueie a equipe durante o processamento.

Estados previstos:

- `pending`
- `processing`
- `completed`
- `error`
- `reviewed`

### Revisao manual

Cada pagina deve ter uma area de revisao com:

- preview da imagem
- transcricao automatica
- campo editavel para texto revisado
- indicador de status e confianca
- acao de aprovar como revisada

O admin tambem deve exibir informacoes do PDF de origem, como:

- nome do arquivo
- quantidade total de paginas
- status geral da extracao

Na V1, a revisao manual deve ocorrer em nivel de linha reconhecida. A equipe pode corrigir o texto de cada linha, mas nao precisa editar palavra por palavra nem alterar caixas de coordenadas manualmente.

Essa decisao preserva o mapeamento entre:

- texto revisado
- linha do manuscrito
- regiao destacada na imagem

### Regra de publicacao

O manuscrito pode ser publicado mesmo com paginas nao revisadas, mas o admin deve deixar claro:

- quantas paginas estao revisadas
- quantas estao pendentes
- quantas falharam

Quando houver revisao, o texto usado pela busca deve ser derivado da concatenacao das linhas revisadas, mantendo a mesma segmentacao estrutural de `layout_data`.

## Abstracao da Camada HTR

Mesmo usando `eScriptorium + Kraken`, o backend deve manter uma interface interna unica para nao acoplar o tema ao formato bruto do servico:

- `submitPageForTranscription(pageImage)`
- `fetchTranscriptionResult(jobId)`
- `normalizeProviderPayload(payload)`

O restante do sistema nao deve conhecer:

- endpoints do eScriptorium
- autenticacao do eScriptorium
- formato bruto da resposta

Persistir tambem:

- nome do `provider`
- identificador externo do job
- identificador do documento ou pagina no eScriptorium, se necessario
- data de processamento
- versao do adaptador, se necessario

Valor padrao de `provider` na V1:

- `escriptorium-kraken`

## Infraestrutura de Ingestao e Processamento

Para a V1, a infraestrutura tecnica deve separar claramente:

- ingestao do `PDF mestre`
- renderizacao das paginas derivadas
- fila de processamento
- envio e coleta de resultados do HTR

### Renderizacao do PDF

O sistema precisa converter o PDF em imagens por pagina antes de qualquer envio ao HTR.

Recomendacao para V1:

- usar `pdftoppm` como ferramenta principal de renderizacao
- gerar uma imagem derivada por pagina em resolucao suficiente para leitura e highlight
- usar `Imagick` apenas como opcao secundaria, se o ambiente ja estiver estavel

Motivos da recomendacao:

- previsibilidade em processamento em lote
- boa compatibilidade com pipeline de paginas
- menor acoplamento a extensoes mais sensiveis do PHP

### Fila Assincrona

O WordPress nao deve realizar extracao do PDF nem reconhecimento durante o request do admin.

Recomendacao para V1:

- usar `Action Scheduler` para orquestrar os jobs

Fila minima sugerida:

- `extract_pdf_pages`
- `register_manuscript_pages`
- `send_page_to_htr`
- `fetch_htr_result`
- `finalize_manuscript_processing`

Cada job deve ser idempotente ou tolerante a repeticao, para facilitar reprocessamento e recuperacao de falhas.

### Armazenamento de Arquivos e Derivados

O armazenamento deve distinguir tres camadas:

1. `PDF mestre` enviado pela equipe
2. imagens derivadas por pagina
3. dados de transcricao e layout

Recomendacao para V1:

- salvar o `PDF mestre` na biblioteca de midia do WordPress
- salvar as imagens derivadas como arquivos associados ao manuscrito ou como attachments tecnicos
- salvar o formato normalizado da transcricao como fonte oficial para revisao, busca e visualizacao

O payload bruto retornado pelo `eScriptorium` pode ser mantido apenas quando for util para:

- auditoria tecnica
- depuracao
- reprocessamento assistido

### Integracao com o Servico HTR

Depois da renderizacao, cada imagem derivada entra na pipeline do HTR:

1. o WordPress identifica a pagina pronta para processamento
2. envia a imagem para a camada de integracao com `eScriptorium`
3. registra um job externo ou identificador tecnico
4. consulta o status ate o resultado ficar disponivel
5. recebe a saida estruturada do reconhecimento
6. normaliza e persiste o resultado no manuscrito

Essa integracao deve ser assincrona do inicio ao fim.

### Pipeline Completa da V1

Fluxo tecnico consolidado:

1. upload do `PDF mestre`
2. job `extract_pdf_pages`
3. renderizacao das paginas em imagens derivadas
4. job `register_manuscript_pages`
5. criacao da estrutura interna do manuscrito
6. jobs `send_page_to_htr` para cada pagina
7. jobs `fetch_htr_result` para cada pagina
8. normalizacao e persistencia de texto, linhas, palavras e coordenadas
9. job `finalize_manuscript_processing`
10. liberacao das paginas para revisao manual no WordPress

### Estados Tecnicos Recomendados

Para evitar ambiguidades, o sistema pode separar estados editoriais de estados tecnicos.

Estados tecnicos recomendados:

- `pending_extraction`
- `extracting`
- `ready_for_htr`
- `processing`
- `completed`
- `error`

Estados editoriais podem continuar incluindo:

- `reviewed`

### Requisitos de Servidor

O ambiente da V1 deve prever:

- acesso a `pdftoppm`
- armazenamento local ou persistente para derivados
- execucao confiavel de jobs assincronos
- conectividade entre WordPress e `eScriptorium`

### Infraestrutura Sugerida para a V1

Como o site principal esta na `WP Engine`, a recomendacao e manter o WordPress la e contratar um servidor externo apenas para:

- `eScriptorium`
- `Kraken`
- `pdftoppm`
- worker da pipeline

Recomendacao principal de provedor para a V1:

- `DigitalOcean`

Configuracao sugerida para piloto:

- `4 vCPU`
- `8 GB RAM`
- `160 GB SSD`
- custo de referencia: `US$ 48/mes`

Configuracao sugerida para uma producao inicial com mais folga:

- `8 vCPU`
- `16 GB RAM`
- `320 GB SSD`
- custo de referencia: `US$ 96/mes`

Alternativa focada em custo:

- `Hetzner`, se a prioridade for reduzir gasto mensal e a equipe estiver confortavel com um provedor um pouco menos direto no onboarding

Esses valores sao referencias de infraestrutura e podem variar com:

- regiao
- backups
- snapshots
- trafego
- armazenamento adicional

Se o projeto crescer, a mesma arquitetura permite trocar apenas a camada de execucao dos jobs sem alterar o modelo editorial nem o visualizador.

## Visualizador do Manuscrito

O frontend deve tratar cada pagina como:

- imagem base
- camada de overlay para highlights
- dados de busca e navegacao

O PDF mestre nao deve ser a base direta do highlight. O visualizador deve trabalhar sobre as imagens derivadas por pagina, porque isso torna mais estavel o overlay de coordenadas, o zoom e os modos `fit-width`, `fit-height` e `100%`.

### Controles obrigatorios

- `Zoom in`
- `Zoom out`
- `100%`
- `Fit width`
- `Fit height`

### Comportamento de escala

- `Fit width` e o padrao inicial no desktop.
- O highlight deve usar coordenadas da imagem original, reescaladas no frontend.
- Ao mudar zoom ou modo de ajuste, os highlights devem permanecer alinhados.

### Navegacao

O visualizador precisa separar:

- navegacao entre paginas
- navegacao entre resultados da busca

## Busca Local no Visualizador

### Escopo da busca

A busca acontece somente dentro do manuscrito aberto. Nao deve disparar nova busca global do WordPress.

### Tipo de busca

Na V1, a busca sera por palavra exata.

Normalizacao minima recomendada:

- case-insensitive
- opcionalmente accent-insensitive
- ignorar diferencas simples de pontuacao ao redor da palavra

Nao incluir na V1:

- stemming
- sinonimos
- correspondencia aproximada
- expressoes regulares expostas ao usuario

### Resultado esperado

Ao buscar uma palavra, o sistema deve:

1. encontrar todas as ocorrencias em `effective_text`
2. informar em quais paginas o termo aparece
3. permitir navegar entre ocorrencias
4. levar o usuario para a pagina correta
5. destacar visualmente a area correspondente da imagem

### Estrategia de highlight

Mesmo que existam bounding boxes por palavra, a V1 deve destacar por linha. Isso reduz ruido visual e e mais robusto para o primeiro release.

Ainda assim, o sistema deve preservar dados por palavra para evolucao futura.

## Estrategia de Dados para Busca

Para cada manuscrito aberto, o frontend deve receber um payload enxuto com:

- dados basicos do manuscrito
- lista ordenada de paginas
- `effective_text` por pagina
- `lines` com bounding boxes
- mapeamento opcional de palavras para linhas

Esse payload pode ser entregue:

- embutido na pagina do manuscrito
- ou por endpoint JSON dedicado

Preferencia para V1:

- endpoint JSON dedicado, para manter o template mais leve e facilitar evolucao

## Erros e Reprocessamento

O sistema precisa prever falhas de HTR.

Cada pagina deve poder:

- registrar mensagem de erro
- ser reenviada individualmente para reprocessamento
- manter historico minimo de ultimo processamento

Se uma pagina falhar:

- o manuscrito nao deve quebrar
- a pagina pode continuar visivel sem busca nessa pagina
- o admin deve conseguir identificar e reprocessar o problema

## Evolucao Futura da Stack HTR

Como a escolha da V1 ja coloca o reconhecimento dentro de uma stack aberta e controlada por voces, a evolucao futura deixa de ser uma migracao de fornecedor e passa a ser uma melhoria da propria stack.

Evolucoes previstas:

- treinar modelos proprios do Kraken com base nas paginas revisadas
- separar modelos por colecao, periodo ou tipo de caligrafia
- mover parte da revisao tecnica para o eScriptorium, se isso fizer sentido depois
- escalar processamento com workers dedicados sem alterar o visualizador nem o fluxo publico do site

Com esse desenho, futuras melhorias nao devem exigir reescrever:

- CPT `manuscrito`
- fluxo de revisao no WordPress
- visualizador
- busca local

## Consideracoes de Implementacao no Tema Atual

Como o tema ja usa Sage/Blade e ACF, a implementacao pode seguir o padrao existente:

- registro de campos editoriais em `app/acf.php` e `acf-json`
- templates Blade para o single do manuscrito e componentes do visualizador
- logica de dados em controller dedicado
- scripts do visualizador no pipeline atual de assets

Fora do tema, a implementacao deve prever uma infraestrutura minima para o HTR:

- instancia do `eScriptorium`
- ambiente com `Kraken`
- fila ou rotina assincrona para envio e coleta de resultados
- camada de autenticacao entre WordPress e servico HTR

Para a camada editorial, a preferencia e por campos como:

- upload do `PDF mestre`
- metadados do manuscrito
- indicadores de extracao e processamento

Evitar na V1:

- bloco de galeria do Gutenberg como fonte de verdade
- ACF Repeater manual para dezenas de paginas

Separacoes recomendadas:

- classe ou modulo de provedor HTR
- classe ou modulo de serializacao do manuscrito para o frontend
- componente Blade do visualizador
- script frontend isolado para busca, escala e highlights

Mesmo com `eScriptorium` disponivel, a recomendacao na V1 e manter a revisao editorial diaria no WordPress. O eScriptorium fica como ferramenta tecnica de processamento e futura calibracao de modelos.

## Testes e Validacao

### Backend

- PDF mestre validado e associado ao manuscrito correto
- extracao do PDF gera paginas ordenadas corretamente
- manuscrito com varias paginas ordenadas corretamente
- integracao com `eScriptorium + Kraken` retorna payload normalizavel
- pagina processada salva com status correto
- `effective_text` respeita prioridade do texto revisado
- falhas do servico HTR nao corrompem o manuscrito

### Frontend

- busca encontra palavra exata em varias paginas
- clique no resultado abre a pagina correta
- highlight permanece alinhado em `fit-width`
- highlight permanece alinhado em `fit-height`
- highlight permanece alinhado em `100%`
- highlights continuam corretos apos zoom in/out

### Editorial

- equipe consegue publicar o manuscrito a partir de um unico PDF
- equipe consegue revisar uma pagina sem perder o texto bruto
- reprocessamento de uma unica pagina nao afeta as demais
- manuscrito publicado continua funcionando mesmo com paginas pendentes

## Decisoes Aprovadas

- cada manuscrito sera um item com um PDF mestre como entrada editorial
- as paginas/imagens serao derivadas automaticamente do PDF
- o reconhecimento sera feito dentro do fluxo do WordPress
- a primeira versao usara `eScriptorium + Kraken`
- a arquitetura deve permitir evolucao futura de modelos proprios do Kraken
- a busca sera local ao visualizador
- a busca sera por palavra exata
- o resultado indicara pagina e regiao da imagem
- a equipe podera revisar e corrigir manualmente a transcricao
- o visualizador tera `zoom`, `fit-width`, `fit-height` e `100%`

## Recomendacao Final

Implementar a V1 com `PDF mestre` como entrada editorial, extracao automatica de paginas e integracao com `eScriptorium + Kraken` por meio de uma camada interna de abstracao com formato neutro de armazenamento por pagina. Essa abordagem reduz trabalho manual da equipe, evita custo recorrente por pagina de fornecedores proprietarios, mantem o visualizador tecnicamente robusto e abre caminho para melhorar os modelos da propria colecao ao longo do tempo.
