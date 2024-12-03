
# Projeto Laravel com Autenticação JWT

Este é um projeto Laravel configurado para usar Docker e autenticação JWT. Ele fornece uma API segura com rotas protegidas por autenticação.

---

## 🚀 Funcionalidades

- Configuração de ambiente usando Docker.
- Autenticação com JWT para APIs.
- Rotas protegidas usando middleware.

---

## 🛠️ Tecnologias

- [Laravel 8](https://laravel.com/)
- [JWT Auth](https://github.com/tymondesigns/jwt-auth)
- [Docker](https://www.docker.com/)
- MySQL 8.0

---

## 📦 Requisitos

- Docker e Docker Compose instalados.
- Composer instalado localmente, caso você deseje gerenciar dependências fora do container.

---

## 📝 Instalação

### 1. Clonar o Repositório
```bash
git clone https://github.com/seu-usuario/seu-projeto.git
cd seu-projeto
```

### 2. Configurar o Ambiente

1. Copie o arquivo de exemplo `.env`:
   ```bash
   cp .env.example .env
   ```

2. Ajuste as configurações do banco de dados no arquivo `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=root
   DB_PASSWORD=root
   ```

---

### 3. Subir os Containers com Docker

1. Construa e inicie os containers:
   ```bash
   docker compose up --build
   ```

2. Acesse o container Laravel:
   ```bash
   docker compose exec app bash
   ```

3. Instale as dependências do projeto:
   ```bash
   composer install
   ```

4. Gere a chave da aplicação:
   ```bash
   php artisan key:generate
   ```

5. Execute as migrações para criar as tabelas do banco de dados:
   ```bash
   php artisan migrate
   ```

6. Gere o segredo JWT:
   ```bash
   php artisan jwt:secret
   ```

---

### 4. Testar a Aplicação

1. O servidor estará disponível em: [http://localhost:8000](http://localhost:8000).

2. Use o comando `php artisan route:list` para visualizar as rotas disponíveis.

---

## Gerando a Documentação Swagger

Este projeto utiliza o Swagger para documentação da API. Siga os passos abaixo para gerar e visualizar a documentação:

### Gerar o arquivo `api-docs.json`
Execute o comando abaixo para gerar a documentação da API:

```bash
./vendor/bin/openapi app --output storage/api-docs/api-docs.json
```
acesse: http://localhost:8000/api/documentation/

## 🔐 Autenticação JWT

### Rotas

- **Login:** `POST /api/login`
  - **Payload:**
    ```json
    {
      "email": "user@example.com",
      "password": "password"
    }
    ```

- **Logout:** `POST /api/logout`
  - Requer o token JWT no cabeçalho:
    ```
    Authorization: Bearer <seu-jwt-token>
    ```

- **Rota Protegida:** `GET /api/protected`
  - Requer o token JWT no cabeçalho:
    ```
    Authorization: Bearer <seu-jwt-token>
    ```

### Gerenciar Usuários
Adicione usuários ao banco de dados para autenticação (ex.: via seeders ou usando o Tinker).

---

## 🛠️ Comandos Úteis

- **Listar rotas:**
  ```bash
  php artisan route:list
  ```

- **Limpar caches:**
  ```bash
  php artisan cache:clear
  php artisan config:clear
  php artisan route:clear
  ```

- **Recriar o banco de dados (migrar e popular):**
  ```bash
  php artisan migrate:fresh --seed
  ```

---

## 🐳 Gerenciar Containers Docker

- **Parar os containers:**
  ```bash
  docker compose down
  ```

- **Subir os containers novamente:**
  ```bash
  docker compose up
  ```

---

