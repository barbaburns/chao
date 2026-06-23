# eScriptorium Local Stack

Stack local de desenvolvimento do `eScriptorium`, baseada no `docker-compose.yml` oficial do projeto `scripta/escriptorium`, adaptada para rodar ao lado do `pdf-worker` deste tema.

## O que esta stack faz

- sobe uma instancia local do `eScriptorium`
- publica a interface em `http://127.0.0.1:8001`
- sobe `postgres`, `redis`, `nginx` e workers `celery`
- roda `migrate + collectstatic` uma unica vez no servico `init`
- deixa o ambiente pronto para gerar um `Token` de API para o `pdf-worker`

## Preparacao

Na primeira vez:

```bash
cd docker/escriptorium-local
cp variables.env.example variables.env
```

## Subir a stack

```bash
docker compose up -d
```

Para acompanhar os logs:

```bash
docker compose logs -f
```

Para parar:

```bash
docker compose down
```

## Bootstrap do admin

O `entrypoint` oficial do projeto executa bootstrap em todos os containers. Nesta stack local isso foi substituido por um servico `init`, para evitar `502 Bad Gateway` enquanto varios containers tentam rodar `migrate` ao mesmo tempo.

Depois que a stack subir, rode:

```bash
docker compose exec web python manage.py shell -c "from django.contrib.auth import get_user_model; User = get_user_model(); user, _ = User.objects.get_or_create(username='admin', defaults={'email': 'admin@admin.com'}); user.is_staff = True; user.is_superuser = True; user.set_password('admin'); user.save(); print('admin pronto')"
```

Depois disso, acesse:

```text
http://127.0.0.1:8001
```

Login padrao:

- usuario: `admin`
- senha: `admin`

## Gerar o token da API

Depois do login funcionar, o caminho mais simples nesta stack local e pedir o token pela propria API:

```bash
curl -X POST http://127.0.0.1:8001/api/token-auth/ \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}'
```

Resposta esperada:

```json
{"token":"SEU_TOKEN_AQUI"}
```

Guarde o valor retornado. Ele entra em `ESCRIPTORIUM_TOKEN` no `docker/manuscripts-local/docker-compose.yml`.

## Criar um projeto no eScriptorium

O worker cria documentos automaticamente, mas precisa que exista um `project` dentro do `eScriptorium`.

Fluxo recomendado:

1. entre em `http://127.0.0.1:8001`
2. use o projeto padrao `admin's Project` ou crie um novo pela interface
3. recupere o `project_id` pela API:

```bash
curl -H "Authorization: Token SEU_TOKEN_AQUI" http://127.0.0.1:8001/api/projects/
```

Use o `pk` do projeto em `ESCRIPTORIUM_PROJECT_ID` no `docker/manuscripts-local/docker-compose.yml`.

Na stack local montada aqui, o projeto padrao do usuario `admin` ficou com `pk = 1`.

## Conectar ao pdf-worker

No `docker/manuscripts-local/docker-compose.yml`, configure:

- `ESCRIPTORIUM_BASE_URL=http://host.docker.internal:8001`
- `ESCRIPTORIUM_TOKEN=<token gerado acima>`
- `ESCRIPTORIUM_PROJECT_ID=<pk do projeto>`

Depois reinicie o worker:

```bash
cd docker/manuscripts-local
docker compose up -d --build
```

## Verificacoes uteis

Interface:

```bash
curl -I http://127.0.0.1:8001/
```

Projetos via API:

```bash
curl -H "Authorization: Token SEU_TOKEN_AQUI" http://127.0.0.1:8001/api/projects/
```

## Observacoes

- Esta pasta usa imagens oficiais publicadas no registry do GitLab.
- A porta `8001` foi escolhida para nao conflitar com o `pdf-worker`, que usa `8002`.
- A stack foi simplificada para piloto local. Ela nao reproduz toda a camada de deployment de producao com Traefik, backups e monitoramento.
