# Runbook de despliegue — Fitnes

Guía para llevar la aplicación a producción en **Laravel Forge + VPS**.

---

## 1. Arquitectura

```
Internet
   │  HTTPS (Let's Encrypt)
   ▼
Nginx ──── Laravel 13 (PHP-FPM)
              │
              ├── MySQL 8      (datos)
              ├── Redis        (colas + caché)
              ├── Horizon      (worker de colas, vía Supervisor)
              ├── Cron         (scheduler: recordatorios de renovación)
              ├── Bunny Stream (video, URLs firmadas)
              └── Mercado Pago (pagos + webhooks)
```

Un solo servidor es suficiente para el arranque (< 500 suscriptores).

---

## 2. Requisitos previos

| Cosa | Detalle |
|---|---|
| VPS | Hetzner CPX21 (2 vCPU / 4 GB) o DigitalOcean |
| Dominio | `app.tudominio.cl` apuntando a la IP del VPS |
| Cuentas | Forge, Bunny.net, Mercado Pago Developers, proveedor de email |
| Repositorio | GitHub con `main` protegida y la CI en verde |

---

## 3. Crear el servidor en Forge

1. **Servers → Create Server**: proveedor Hetzner/DO, PHP **8.4**, MySQL **8.4**, Redis.
2. **Sites → New Site**: dominio `app.tudominio.cl`, tipo *Web Application*,
   web directory `/public`.
3. **SSL → Let's Encrypt**: activar el certificado.
4. **Git Repository**: conectar el repo y la rama `main`.
5. **Deploy Script** (Deployments → Edit Deploy Script):

```bash
cd $FORGE_SITE_PATH

git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

$FORGE_PHP artisan migrate --force

# Caches de producción
$FORGE_PHP artisan optimize

# Reinicia los workers para que tomen el código nuevo
$FORGE_PHP artisan queue:restart

# Horizon se reinicia solo si está en modo "terminate on deploy";
# si no, descomenta:
# $FORGE_PHP artisan horizon:terminate

$FORGE_PHP artisan storage:link
```

> `$FORGE_PHP artisan optimize` ejecuta config/route/view/event cache.

---

## 4. Variables de entorno

En **Site → Environment**, pegar el contenido de `.env.production.example`
rellenando los valores reales:

```bash
cp .env.production.example .env      # referencia, no copiar tal cual
php artisan key:generate             # genera APP_KEY
```

Puntos críticos:

| Variable | Valor |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | **`false`** |
| `APP_URL` | `https://app.tudominio.cl` |
| `SESSION_SECURE_COOKIE` | `true` |
| `QUEUE_CONNECTION` | `redis` |
| `CACHE_STORE` | `redis` |
| `PAYMENT_DRIVER` | `mercadopago` |
| `VIDEO_DRIVER` | `bunny` |

> La aplicación fuerza `https` en los enlaces cuando `APP_ENV=production`
> (ver `AppServiceProvider::boot`).

---

## 5. Colas con Horizon

Horizon viene instalado (`laravel/horizon`). En Forge:

1. **Site → Queue → New Worker**, o usar la pestaña **Horizon** y pulsar
   *Install Horizon* (Forge configura Supervisor automáticamente).

Equivalente manual en Supervisor (`/etc/supervisor/conf.d/horizon.conf`):

```ini
[program:horizon]
process_name=%(program_name)s
command=php /home/forge/app.tudominio.cl/artisan horizon
autostart=true
autorestart=true
user=forge
redirect_stderr=true
stdout_logfile=/home/forge/app.tudominio.cl/storage/logs/horizon.log
stopwaitsecs=3600
```

Verificación:

```bash
php artisan horizon:status     # debe decir "running"
```

Dashboard de Horizon: `https://app.tudominio.cl/horizon` (protegido por
el gate de `HorizonServiceProvider`; restringir a los emails del entrenador).

---

## 6. Scheduler (recordatorios de renovación)

En Forge: **Site → Scheduler**. O un cron manual:

```
* * * * * cd /home/forge/app.tudominio.cl && php artisan schedule:run >> /dev/null 2>&1
```

Comprobar qué está programado:

```bash
php artisan schedule:list
# → 0 9 * * *  php artisan subscriptions:renewal-reminders
```

Ejecución manual (prueba):

```bash
php artisan subscriptions:renewal-reminders --days=3
```

---

## 7. Webhook de Mercado Pago

En el panel de Mercado Pago → **Tus integraciones → Webhooks**:

- URL: `https://app.tudominio.cl/webhooks/mercadopago`
- Eventos: `subscription_preapproval`, `subscription_authorized_payment`
- Copiar el **secreto** a `MERCADOPAGO_WEBHOOK_SECRET`

La ruta está excluida del CSRF (`bootstrap/app.php`) y se valida con firma
HMAC + idempotencia por `event_id`.

Prueba local con ngrok/Expose para recibir webhooks reales.

---

## 8. Monitoreo

| Herramienta | Qué cubre |
|---|---|
| **Sentry** | Errores y trazas (`SENTRY_LARAVEL_DSN`) |
| **Horizon** | Estado de colas, jobs fallidos, throughput |
| **Laravel logs** | `storage/logs/laravel.log` |
| **Health check** | `GET /up` (útil para uptime monitors) |
| **Bunny** | Consumo de bandwidth y alertas de gasto |

Configurar alertas de gasto en Bunny y de errores en Sentry.

---

## 9. Backups

Forge → **Site → Backups** (o DB → Backups):

- Frecuencia: **diaria**, retención 7–30 días.
- Destino: Bunny Storage o S3 (no en el mismo VPS).

Probar una restauración al menos una vez antes de lanzar.

---

## 10. Checklist post-deploy

- [ ] `https://app.tudominio.cl/up` responde 200
- [ ] La home, `/planes` y `/catalogo` cargan con estilos
- [ ] Registro de un usuario de prueba → llega el correo de bienvenida
- [ ] Suscripción de prueba (sandbox de Mercado Pago) → queda `authorized`
- [ ] El webhook llega (ver `webhook_events`) y es idempotente
- [ ] `/admin/metricas` muestra datos reales
- [ ] `horizon:status` → running; `schedule:list` → tarea programada
- [ ] `APP_DEBUG=false` (no se ven trazas de error)
- [ ] Sentry recibe eventos (forzar un error de prueba)
- [ ] Backup ejecutado y restauración verificada

---

## 11. Rollback

```bash
cd $FORGE_SITE_PATH
git log --oneline -5                 # elegir el commit bueno
git checkout <commit-anterior>
$FORGE_COMPOSER install --no-dev
php artisan migrate --force          # solo si hay migraciones compatibles
php artisan optimize
php artisan queue:restart
```

Si el problema es de datos, restaurar el backup desde Forge y volver a
desplegar el commit anterior.

---

## 12. Costos operativos estimados (CLP / mes)

| Ítem | Costo |
|---|---|
| VPS Hetzner CPX21 | ≈ 8.500 |
| Bunny Stream (mínimo + uso bajo) | ≈ 1.500 – 6.000 |
| Dominio `.cl` (anual, prorrateado) | ≈ 900 |
| Sentry (plan free) | 0 |
| Forge | ≈ 10.000 – 14.000 |
| **Total** | **≈ 21.000 – 30.000** |

Comisión de Mercado Pago: ≈ 3,49% + IVA por transacción (verificar tarifa vigente).
