# 🚀 Guía de Deployment - Milla Backend

## 📋 Arquitectura de Ambientes

Este proyecto utiliza 2 ambientes separados:

- **PRODUCCIÓN**: Servidor real con datos de clientes
- **TEST/QA**: Servidor para pruebas antes de pasar a producción

### Flujo de Trabajo

```
Desarrollo Local (tu PC)
    ↓
git push origin develop → GitHub Actions → Droplet TEST
    ↓ (después de probar)
git push origin main → GitHub Actions → Droplet PRODUCCIÓN
```

---

## 🏗️ Infraestructura

### Droplet PRODUCCIÓN (ya configurado)
- **Ubicación**: `/opt/milla-api/`
- **Rama Git**: `main`
- **Base de datos**: `milla_backend_prod`
- **Deploy automático**: Cuando haces push a `main`
- **Workflow**: `.github/workflows/main.yml`

### Droplet TEST (por configurar)
- **Ubicación**: `/opt/milla-api-test/`
- **Rama Git**: `develop`
- **Base de datos**: `milla_backend_test`
- **Deploy automático**: Cuando haces push a `develop`
- **Workflow**: `.github/workflows/deploy-test.yml`

---

## ⚙️ Configuración Inicial del Droplet TEST

### 1. Crear Droplet en DigitalOcean

```bash
# Specs recomendados:
# - Ubuntu 22.04 LTS
# - 2 GB RAM / 1 vCPU (mínimo)
# - 50 GB SSD
```

### 2. Conectar al Droplet por SSH

```bash
ssh root@IP_DEL_DROPLET_TEST
```

### 3. Instalar Docker y Docker Compose

```bash
# Actualizar sistema
apt update && apt upgrade -y

# Instalar Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Instalar Docker Compose
apt install docker-compose-plugin -y

# Verificar instalación
docker --version
docker compose version
```

### 4. Instalar Git

```bash
apt install git -y
```

### 5. Clonar el Repositorio

```bash
# Crear directorio
mkdir -p /opt/milla-api-test
cd /opt/milla-api-test

# Clonar repo
git clone https://github.com/pakatnamu2022/milla-backend.git app
cd app

# Cambiar a rama develop
git checkout develop
```

### 6. Configurar Archivo .env

```bash
cd /opt/milla-api-test/app

# Copiar plantilla
cp .env.test.example .env

# Editar con tus valores
nano .env

# Generar APP_KEY
docker run --rm -v $(pwd):/app composer:2 bash -c "cd /app && php artisan key:generate"
```

### 7. Copiar archivos de configuración

```bash
# Volver al directorio principal
cd /opt/milla-api-test

# Copiar docker-compose
cp app/resources/server-config/docker-compose.server.yml docker-compose.yml

# Crear directorio de configuración PHP
mkdir -p ops/php

# Crear archivo php.ini básico
cat > ops/php/php.ini << 'EOF'
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 256M
max_execution_time = 300
date.timezone = America/Lima
EOF
```

### 8. Iniciar Docker

```bash
cd /opt/milla-api-test

# Construir e iniciar contenedores
docker compose up -d --build

# Ver logs
docker compose logs -f
```

### 9. Instalar dependencias y migrar BD

```bash
# Instalar composer dependencies
docker compose exec app composer install --no-dev --optimize-autoloader

# Ejecutar migraciones
docker compose exec app php artisan migrate --seed

# Limpiar cache
docker compose exec app php artisan optimize:clear
```

### 10. Configurar Nginx (si usas Nginx como proxy)

```bash
nano /etc/nginx/sites-available/milla-api-test

# Agregar configuración:
server {
    listen 80;
    server_name api-test.tudominio.com;

    location / {
        proxy_pass http://127.0.0.1:9001;  # Nota: puerto diferente al de prod
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# Activar sitio
ln -s /etc/nginx/sites-available/milla-api-test /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### 11. Configurar SSL con Certbot (opcional pero recomendado)

```bash
apt install certbot python3-certbot-nginx -y
certbot --nginx -d api-test.tudominio.com
```

---

## 🔐 Configurar GitHub Actions Secrets

Ve a tu repositorio en GitHub:
`https://github.com/pakatnamu2022/milla-backend/settings/secrets/actions`

### Secrets necesarios para TEST:

```
TEST_HOST = IP_del_droplet_test
TEST_USERNAME = root
TEST_PASSWORD = tu_password_ssh
```

### Secrets de PRODUCCIÓN (ya deberías tenerlos):

```
HOST = IP_del_droplet_prod
USERNAME = root
PASSWORD = tu_password_ssh
```

---

## 🔄 Flujo de Deploy

### Deploy a TEST

```bash
# 1. Crear rama develop si no existe
git checkout -b develop

# 2. Hacer cambios en tu código
# ... editas archivos ...

# 3. Commit y push a develop
git add .
git commit -m "Add new feature"
git push origin develop

# 4. GitHub Actions automáticamente:
#    - Detecta push a develop
#    - Se conecta al droplet TEST
#    - Hace git pull
#    - Rebuilds Docker
#    - Ejecuta composer install
#    - Limpia cache
```

### Deploy a PRODUCCIÓN

```bash
# 1. Después de probar en TEST, mergear a main
git checkout main
git merge develop

# 2. Push a main
git push origin main

# 3. GitHub Actions automáticamente deploya a PRODUCCIÓN
```

---

## 🔧 Comandos Útiles

### En el Droplet TEST

```bash
# Ver logs en tiempo real
docker compose logs -f app

# Reiniciar servicios
docker compose restart

# Ejecutar comandos artisan
docker compose exec app php artisan cache:clear
docker compose exec app php artisan migrate

# Ver estado de contenedores
docker compose ps

# Entrar al contenedor
docker compose exec app bash
```

### Rollback Manual (si algo sale mal)

```bash
cd /opt/milla-api-test/app
git log --oneline  # Ver commits
git reset --hard COMMIT_HASH  # Volver a commit anterior
cd /opt/milla-api-test
docker compose restart
```

---

## 📊 Diferencias Clave entre TEST y PROD

| Aspecto | TEST | PRODUCCIÓN |
|---------|------|------------|
| Rama Git | `develop` | `main` |
| APP_DEBUG | `true` | `false` |
| LOG_LEVEL | `debug` | `error` |
| Base de Datos | `milla_backend_test` | `milla_backend_prod` |
| Directorio | `/opt/milla-api-test/` | `/opt/milla-api/` |
| Email | Mailtrap (no envía reales) | SMTP real |
| Puerto Docker | 9001 | 9000 |
| Subdomain | `api-test.tudominio.com` | `api.tudominio.com` |

---

## 🆘 Troubleshooting

### Error: "Permission denied" al hacer git pull

```bash
cd /opt/milla-api-test/app
git config --global --add safe.directory /opt/milla-api-test/app
```

### Error: Docker no puede conectar a la BD

```bash
# Verificar que DB_HOST apunte correctamente
# Si BD está en el mismo droplet:
DB_HOST=host.docker.internal

# Agregar en docker-compose.yml:
extra_hosts:
  - "host.docker.internal:host-gateway"
```

### Error: "Class not found" después de deploy

```bash
docker compose exec app composer dump-autoload
docker compose exec app php artisan optimize:clear
```

---

## 📝 Checklist de Deployment

### Antes del primer deploy a TEST:

- [ ] Droplet TEST creado y configurado
- [ ] Docker y Docker Compose instalados
- [ ] Repositorio clonado en `/opt/milla-api-test/app`
- [ ] Archivo `.env` configurado con credenciales TEST
- [ ] Base de datos `milla_backend_test` creada
- [ ] Secrets de GitHub configurados (TEST_HOST, TEST_USERNAME, TEST_PASSWORD)
- [ ] Nginx configurado (si aplica)
- [ ] SSL configurado (si aplica)

### Antes de cada deploy a PRODUCCIÓN:

- [ ] Cambios probados en TEST
- [ ] Migraciones de BD revisadas
- [ ] Tests pasando (si existen)
- [ ] Backup de BD de producción realizado
- [ ] Equipo notificado del deploy

---

## 🎯 Siguientes Pasos

1. **Ahora**: Los archivos están listos en tu proyecto local
2. **Commit y push**: Sube estos cambios a GitHub
3. **Crear Droplet TEST**: Cuando estés listo, crea el droplet en DigitalOcean
4. **Configurar**: Sigue esta guía paso a paso
5. **Probar**: Haz un push a `develop` y observa el deploy automático

---

¿Preguntas? Revisa esta guía o consulta con el equipo.