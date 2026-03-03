#!/bin/sh
set -e

# Gera chave SSH se não existir
if [ ! -f "$HOST_SSH_KEY_PATH" ]; then
    echo "🔑 Gerando chave SSH..."
    ssh-keygen -t ed25519 -f "$HOST_SSH_KEY_PATH" -N ""
fi

# Adiciona host ao known_hosts
ssh-keyscan -p "$HOST_SSH_PORT" "$HOST_SSH_HOST" >> /var/www/.ssh/known_hosts

exec "$@"
