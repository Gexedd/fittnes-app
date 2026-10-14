# Puesta en marcha en otra máquina

Guía para clonar el proyecto y dejarlo funcionando. Incluye el `.env`, que **no
viaja en el repositorio** porque contiene secretos.

---

## 1. Requisitos

| Necesitas | Para qué |
|---|---|
| **Docker** + Compose | Todo el entorno (Sail) |
| **Node.js 20+** | Compilar los assets con Vite |
| **PHP 8.3+ y Composer** | Solo para el primer `composer install` |

Si no tienes PHP ni Composer, puedes saltártelos usando Docker (paso 4B).

---

## 2. Clonar

```bash
git clone https://github.com/Gexedd/fittnes-app.git
cd fittnes-app
```

---

## 3. Crear el `.env`

```bash
cp .env.example .env
```

Y ajusta estos valores:

### 3.1 URL de la aplicación

Sail sirve en el puerto **80**, así que:

```dotenv
APP_URL=http://localhost
```

### 3.2 Base de datos (Sail usa MySQL)

En la plantilla la base viene en SQLite. Descomenta y deja así:

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

### 3.3 Mercado Pago (opcional, solo para probar pagos)

Los valores están en el panel de MP → *Tus integraciones* → tu app →
**Credenciales de prueba** (ver `docs/MERCADOPAGO.md`).

```dotenv
PAYMENT_DRIVER=mercadopago
MERCADOPAGO_ACCESS_TOKEN=
MERCADOPAGO_PUBLIC_KEY=
MERCADOPAGO_WEBHOOK_SECRET=
MERCADOPAGO_SANDBOX=true
MERCADOPAGO_TEST_PAYER_EMAIL=
```

> 💡 ¿No tienes las credenciales a mano? Pon `PAYMENT_DRIVER=fake` y la app
> funciona igual con una pasarela simulada.

El resto (correo, Redis, Mailpit) ya viene correcto para Sail. `APP_KEY` se genera
en el paso 5.

---

## 4. Dependencias

### 4A. Con PHP y Composer instalados

```bash
composer install
```

### 4B. Solo con Docker

```bash
docker run --rm -u "$(id -u):$(id -g)" \
  -e COMPOSER_HOME=/tmp/composer -e HOME=/tmp \
  -v "$PWD":/app -w /app composer:latest install
```

---

## 5. Levantar y preparar la base de datos

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

El `--seed` crea los planes, usuarios y contenido de ejemplo (solo en `local`).

---

## 6. Assets

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev      # desarrollo, con recarga en vivo
# o: ./vendor/bin/sail npm run build    # compilado estático
```

---

## 7. Comprobar que todo está bien

```bash
./vendor/bin/sail test
```

Deberías ver **100 tests en verde**.

| Servicio | URL |
|---|---|
| Aplicación | http://localhost |
| Mailpit (correos enviados) | http://localhost:8025 |
| Horizon (colas) | http://localhost/horizon |

### Usuarios de ejemplo

| Rol | Email | Contraseña |
|---|---|---|
| Entrenador (admin) | `admin@fittnes.test` | `password` |
| Suscriptor sin plan | `demo@fittnes.test` | `password` |

---

## 8. Cosas que hay que recordar

- **El `.env` nunca se sube.** Cada máquina tiene el suyo; si cambias un secreto,
  cámbialo en las dos.
- **Los correos y las colas necesitan un worker** en local:
  ```bash
  ./vendor/bin/sail artisan queue:work
  ```
  Y para las tareas programadas (recordatorios, reconciliación de pagos):
  ```bash
  ./vendor/bin/sail artisan schedule:work
  ```
- **El retorno del checkout de Mercado Pago no funciona en `localhost`**: MP
  rechaza esa URL. Necesitas un túnel público (ver `docs/MERCADOPAGO.md`, 5.1).
- **Los webhooks son por aplicación de MP**: si cambias de túnel, actualiza la URL
  en el panel.

---

## 9. Documentación relacionada

| Documento | Contenido |
|---|---|
| `docs/MERCADOPAGO.md` | Probar pagos en sandbox, webhooks, trampas y diagnóstico |
| `docs/DEPLOY.md` | Puesta en producción (Forge, Horizon, backups, rollback) |
| `AGENTS.md` | Convenciones del proyecto y guías para asistentes de código |
