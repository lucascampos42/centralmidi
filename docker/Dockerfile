FROM wordpress:php8.2-apache

# Mapeia o usuário do Apache (www-data) para o UID/GID do usuário host,
# permitindo editar os arquivos de wp-content diretamente no host.
ARG UID=1000
ARG GID=1000

RUN usermod -u ${UID} www-data && groupmod -g ${GID} www-data