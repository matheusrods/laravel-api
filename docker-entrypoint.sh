#!/bin/bash

# Espera pelo banco de dados estar acessível
echo "Aguardando o banco de dados estar disponível..."

until nc -z -v -w30 $DB_HOST $DB_PORT
do
  echo "Aguardando conexão com o banco de dados..."
  sleep 5
done

echo "Banco de dados disponível, iniciando o Laravel."

# Garantir que o diretório storage/api-docs existe e tem permissões corretas
mkdir -p storage/api-docs
chmod -R 775 storage/api-docs
chown -R www-data:www-data storage/api-docs

# Instala as dependências do Laravel
composer install

# Gera a chave JWT
php artisan jwt:secret --force

# Executa as migrations e seeds
php artisan migrate --force
php artisan db:seed --force

# Gera a documentação da API
./vendor/bin/openapi app --output storage/api-docs/api-docs.json

# Inicia o servidor Laravel
php artisan serve --host=0.0.0.0 --port=8000
