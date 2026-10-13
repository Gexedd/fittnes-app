# Guía: probar Mercado Pago en modo sandbox

Guía práctica para probar el flujo de suscripción con las **credenciales de prueba**
de Mercado Pago, sin cobrar dinero real.

---

## 1. Qué necesita la aplicación

La app solo necesita **dos secretos** y un dato no secreto:

| Variable de `.env` | Qué es | Dónde lo encuentras |
|---|---|---|
| `MERCADOPAGO_ACCESS_TOKEN` | 🔒 Token de **prueba** (empieza con `TEST-`) | Panel MP → *Tus integraciones* → tu app → **Credenciales de prueba** |
| `MERCADOPAGO_WEBHOOK_SECRET` | 🔒 Clave secreta del webhook | Panel MP → *Webhooks* → al crear/editar el webhook |
| `MERCADOPAGO_TEST_PAYER_EMAIL` | Email del **usuario comprador de prueba** | Panel MP → *Cuentas de prueba* → **Buyer Test User** |

> 🔒 = secreto. **Nunca** lo pegues en un chat, issue o commit: va directo en `.env`.

Variables de comportamiento:

```dotenv
PAYMENT_DRIVER=mercadopago   # activa la pasarela real
MERCADOPAGO_SANDBOX=true     # usa el checkout de prueba
```

---

## 2. Mapa de lo que tienes en el panel

| Lo que ves en el panel | Para qué sirve | ¿Lo necesitamos? |
|---|---|---|
| **Credenciales de prueba** | Public Key + **Access Token** de prueba | ✅ el **Access Token** |
| **Datos de las credenciales de prueba** | Lo mismo, en formato de ficha | ✅ (de aquí sale el token) |
| **N.º de la aplicación** (App ID) | Identificar la app para OAuth | ❌ (no hacemos OAuth) |
| **User ID** | Identificar la cuenta vendedora | ❌ |
| **Seller Test User** | Cuenta que **recibe** el dinero en las pruebas | ❌ (ya está ligada al token) |
| **Buyer Test User** (usuario + contraseña) | Cuenta que **paga** y con la que se ingresa al checkout | ✅ usamos su **email**; su contraseña la usas tú en el navegador |
| **Código de verificación** | Verificar la cuenta de prueba dentro de MP | ❌ |
| **Tarjetas de prueba** | Números de tarjeta para simular pagos | ✅ las usas al pagar |

---

## 3. Paso a paso

### 3.1 Pegar las credenciales

Abre el archivo `.env` del proyecto y edita estas líneas:

```dotenv
PAYMENT_DRIVER=mercadopago
MERCADOPAGO_ACCESS_TOKEN=TEST-0000000000000000-000000-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-000000000
MERCADOPAGO_PUBLIC_KEY=TEST-00000000-0000-0000-0000-000000000000
MERCADOPAGO_SANDBOX=true
MERCADOPAGO_TEST_PAYER_EMAIL=test_user_123456@testuser.com
```

`MERCADOPAGO_TEST_PAYER_EMAIL` debe ser el email del **Buyer Test User**: en sandbox
Mercado Pago exige que el pagador sea un usuario de prueba.

### 3.2 Limpiar la caché de configuración

```bash
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan optimize:clear   # si habías ejecutado optimize
```

### 3.3 Crear el usuario comprador en la app

Con Mailpit recibiendo los correos (http://localhost:8025), regístrate en
`http://localhost/register` usando **exactamente el email del Buyer Test User**.

### 3.4 Suscribirse

1. Ve a `http://localhost/planes`.
2. Pulsa **Suscribirme** en un plan.
3. Te redirige al **checkout de Mercado Pago (sandbox)**.
4. Inicia sesión con el **Buyer Test User** (usuario y contraseña del panel).
5. Paga con una **tarjeta de prueba** (ver abajo).
6. Al terminar, vuelves a `http://localhost/suscripcion/retorno` y deberías ver
   **"¡Listo! Tu suscripción está activa."** en el panel.

### 3.5 Verificar

```bash
./vendor/bin/sail artisan tinker --execute='App\Models\Subscription::with("plan")->latest()->first();'
# status = "authorized" y mp_preapproval_id con valor real de Mercado Pago
```

---

## 4. Tarjetas de prueba

Confirma los números en *Tarjetas de prueba* del panel. Las habituales:

| Tarjeta | Número | CVV | Vencimiento |
|---|---|---|---|
| Mastercard | `5031 4332 1540 6351` | `123` | `11/30` |
| Visa | `4509 9535 6623 3704` | `123` | `11/30` |
| American Express | `3757 781744 61804` | `1234` | `11/30` |

Para simular resultados, el **nombre del titular** define el estado:

| Nombre del titular | Resultado |
|---|---|
| `APRO` | Pago aprobado |
| `OTHE` | Rechazado por error general |
| `FUND` | Fondos insuficientes |

---

## 5. Webhooks (segunda fase)

Para que las **renovaciones, cancelaciones y pagos fallidos** lleguen solos,
Mercado Pago necesita una URL pública:

- **En local**: expón tu entorno con un túnel:
  ```bash
  ./vendor/bin/sail share      # o: expose share http://localhost
  ```
- En el panel: *Webhooks* → URL `https://TU-TUNEL/webhooks/mercadopago`,
  eventos `subscription_preapproval` y `subscription_authorized_payment`.
- Copia la **clave secreta** del webhook a `MERCADOPAGO_WEBHOOK_SECRET`.

> Aunque todavía no configures webhooks, el pago **sí se activa**: al volver del
> checkout la app reconsulta el estado a Mercado Pago (`retrieveStatus`).

---

## 6. Errores comunes

| Síntoma | Causa probable | Solución |
|---|---|---|
| `403` / `invalid access token` | Token de **producción** con `SANDBOX=true`, o token mal copiado | Usa el token de *Credenciales de prueba* |
| `payer email must be a test user` | El pagador no es usuario de prueba | Rellena `MERCADOPAGO_TEST_PAYER_EMAIL` con el Buyer Test User |
| No vuelve a la app | `back_url` inválida | Revisa que `APP_URL` sea correcto (`http://localhost`) |
| Redirige al checkout de producción | `MERCADOPAGO_SANDBOX` en `false` | Ponlo en `true` |
| Webhook devuelve 401 | Firma inválida | Revisa `MERCADOPAGO_WEBHOOK_SECRET` |

---

## 7. Cómo verificar que todo funciona sin internet

El gateway real está cubierto por tests con `Http::fake()`:

```bash
./vendor/bin/sail test --filter=SubscriptionFlowTest
```
