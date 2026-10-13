# Guía: probar Mercado Pago en modo sandbox

Guía práctica para probar el flujo de suscripción con las **credenciales de prueba**
de Mercado Pago, sin cobrar dinero real.

---

## 1. Qué necesita la aplicación

La app solo necesita **dos secretos** y un dato no secreto:

| Variable de `.env` | Qué es | Dónde lo encuentras |
|---|---|---|
| `MERCADOPAGO_ACCESS_TOKEN` | 🔒 Token de **prueba** (suele empezar con `TEST-`; si usas un **usuario de prueba**, es `APP_USR-`) | Panel MP → *Tus integraciones* → tu app → **Credenciales de prueba** |
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

## 5. Webhooks

Las **renovaciones, cancelaciones y pagos fallidos** llegan solos si Mercado Pago
puede llamar a tu servidor. Para eso necesita una **URL pública** (rechaza
`localhost`).

### 5.1 Exponer tu app

| Opción | Comando | Notas |
|---|---|---|
| **cloudflared** ⭐ | `~/.local/bin/cloudflared tunnel --url http://localhost:80 --no-autoupdate` | Da `https://algo.trycloudflare.com` con TLS válido, sin cuenta |
| `sail share` | `./vendor/bin/sail share` | A veces el HTTPS (443) de expose está caído: entonces usa `http://TU-TUNEL.laravel-sail.site:8080` |

> ⚠️ La URL de un túnel rápido **cambia** cada vez que lo reinicias. Si la usas para
> el webhook tendrás que actualizarla en el panel cada vez. Para eso sirve un staging
> con dominio fijo (ver `docs/DEPLOY.md`).

### 5.2 Configurarlo en el panel

*Tus integraciones* → tu app → **Webhooks** → pestaña **Modo de prueba**:

1. **URL para prueba**: `https://TU-TUNEL/webhooks/mercadopago`
2. Marca **"Planes y suscripciones"**. Está en **Otros eventos**, *no* en los
   "recomendados para Checkout Pro" (esos son para pagos únicos).
3. Pulsa **Guardar configuración**.
4. Copia la **Clave secreta** a `MERCADOPAGO_WEBHOOK_SECRET`.
5. `./vendor/bin/sail artisan config:clear`

**Cómo saber si quedó guardado:** vuelve a la pantalla principal de *Webhooks* y lee
la línea **"Eventos:"**. Debe decir `Planes y suscripciones`. Si dice
`Vinculación de aplicaciones, Alertas de...`, **no se guardó**: desmarca y vuelve a
marcar la casilla para que el botón se habilite, y guarda otra vez.

> `MERCADOPAGO_WEBHOOK_SECRET` **vacío** solo permite webhooks en `local`/`testing`.
> Con cualquier otro valor se exige firma válida (o sea: `MERCADOPAGO_WEBHOOK_SECRET=true`
> haría que **todos** los webhooks devuelvan `401`).

### 5.3 ⚠️ `notification_url` NO funciona en suscripciones

Comprobado contra la API real: el recurso `preapproval` **ignora** el campo
`notification_url`, tanto al crear la suscripción como con un `PUT` posterior.
Para suscripciones la única vía es el panel (punto 5.2).

### 5.4 El simulador sirve... pero no del todo

"Simular notificación" sirve para comprobar que tu endpoint **recibe, valida la
firma y responde `204`** (y `401` si la firma es falsa). Pero **no** sirve para
probar la sincronización:

- Manda siempre el mismo `id: 123456`, así que tu **idempotencia** lo bloquea en el
  segundo intento (correcto, pero ya no procesa nada).
- Su `data.id` **no** corresponde a ningún preapproval real: no hay nada que sincronizar.

Para probar la sincronización de verdad hace falta un evento real (un pago, o el
comando de reconciliación de la sección 9).

### 5.5 Si el webhook no llega

No es grave: al volver del checkout la app reconsulta el estado a Mercado Pago
(`retrieveStatus`) y, además, está la reconciliación periódica (sección 9).

---

## 6. Errores comunes

| Síntoma | Causa probable | Solución |
|---|---|---|
| `403` / `invalid access token` | Token de **producción** con `SANDBOX=true`, o token mal copiado | Usa el token de *Credenciales de prueba* |
| `User bad request` al crear el preapproval | Pusiste el **usuario** (`TESTUSER123…`) en vez del **email** (`test_user_123…@testuser.com`) | Son dos campos distintos del panel: usa el **Email** |
| `payer email must be a test user` | El pagador no es usuario de prueba | Rellena `MERCADOPAGO_TEST_PAYER_EMAIL` con el Buyer Test User |
| `Payer and collector cannot be the same user` | Usaste tu propia cuenta como pagador | Paga con el Buyer Test User |
| `Invalid value for back_url` | La URL apunta a `localhost` | Necesitas una URL pública (sección 5.1) |
| `payer_email is required` | Falta `MERCADOPAGO_TEST_PAYER_EMAIL` | Rellénala |
| El navegador vuelve solo a Mercado Pago | La `back_url` llevaba query string y MP añadió `?` en vez de `&`, anidando `preapproval_id` | La `back_url` va limpia: `route('subscriptions.callback')` |
| Tras cambiar de plan sigue mostrando el anterior | Quedaron varias suscripciones autorizadas | Ver sección 8.3 |
| Webhook devuelve `401` | Firma inválida o clave mal cargada | Revisa `MERCADOPAGO_WEBHOOK_SECRET` y ejecuta `config:clear` |
| No llega ningún webhook | La config del panel no está guardada, o el evento no está marcado | Verifica la línea "Eventos:" (sección 5.2) |

---

## 7. Cómo verificar que todo funciona sin internet

El gateway real está cubierto por tests con `Http::fake()`, y los tests **no** tocan
la API real (`phpunit.xml` fuerza `PAYMENT_DRIVER=fake`):

```bash
./vendor/bin/sail test
```

---

## 8. Trampas encontradas probando de verdad

Cosas que solo aparecen con la pasarela real. Cada una costó su rato de depuración.

### 8.1 Mercado Pago no redirige solo al terminar

El checkout de **suscripciones** no vuelve automáticamente: muestra una pantalla de
confirmación con dos botones. **"Ir al sitio del vendedor"** es el que usa la
`back_url`; el botón azul "Ir a tus suscripciones" te deja dentro de Mercado Pago.

👉 **Nunca dependas de que el cliente haga clic** para activarle el servicio.

### 8.2 La `back_url` no debe llevar query string

Si la URL ya trae parámetros, Mercado Pago agrega el suyo con **otro `?`** en lugar
de `&`, y el parámetro queda anidado dentro del anterior:

```
❌ /suscripcion/retorno?back=https://...?preapproval_id=abc123
                                          ↑ anidado: llega dentro de "back"

✅ /suscripcion/retorno?preapproval_id=abc123
```

Y el callback **no debe exigir** `preapproval_id` con `validate()`: si falta, Laravel
redirige "de vuelta" al referer (o sea, al propio checkout de Mercado Pago) y el
usuario ve su app un instante antes de salir de nuevo. Mejor tolerar su ausencia y
usar la suscripción pendiente del usuario, que es única.

### 8.3 Un usuario = una sola suscripción viva

Si al activar un plan nuevo no se cancelan las anteriores, quedan **varios
preapprovals vivos en Mercado Pago y se cobran todos cada mes**. La invariante se
aplica en `SubscriptionService::applyStatus()` al pasar a activa (se cancelan en el
proveedor y en local).

Y `User::activeSubscription()` debe ordenar (`latestOfMany()`): sin orden, con dos
autorizadas devolvía **la más antigua** y el panel seguía mostrando el plan viejo.

### 8.4 El email del comprador de prueba

En *Cuentas de prueba* hay **dos campos parecidos** y es fácil confundirlos:

| Campo | Valor | ¿Sirve como `payer_email`? |
|---|---|---|
| Usuario | `TESTUSER3329645329204024371` | ❌ → `User bad request` |
| **Email** | `test_user_3329645329204024371@testuser.com` | ✅ |

### 8.5 Los tests no deben depender del `.env`

Con `PAYMENT_DRIVER=mercadopago` en `.env`, la suite hacía **llamadas HTTP reales** a
Mercado Pago y fallaba. Por eso `phpunit.xml` fuerza `PAYMENT_DRIVER=fake` y
`VIDEO_DRIVER=null`.

### 8.6 Detrás de un túnel, Laravel ve HTTP

Sin `trustProxies`, las URLs generadas salen en `http://` aunque el usuario navegue
por HTTPS (y la `back_url` que recibe MP queda con el esquema equivocado). Está
resuelto en `bootstrap/app.php`, configurable con `TRUSTED_PROXIES`.

---

## 9. Reconciliación: la red de seguridad

Los webhooks son **best effort**: pueden perderse, llegar tarde o no estar
configurados. Si eso pasa, el cliente paga y se queda sin servicio.

```bash
./vendor/bin/sail artisan subscriptions:reconcile
```

Busca las suscripciones que siguen `pending` dentro de una ventana de tiempo
(`--minutes=120` por defecto), pregunta el estado real a la pasarela y las
sincroniza. Las que la pasarela sigue reportando pendientes **se dejan igual**: son
intentos abandonados, no clientes esperando acceso.

En producción va programado cada 5 minutos (`routes/console.php`). En local el
scheduler no corre solo:

```bash
./vendor/bin/sail artisan schedule:work
```

> 💡 **Regla general:** los webhooks aceleran, la reconciliación garantiza.

---

## 10. Recetas de diagnóstico

**Comparar el estado real en Mercado Pago con tu base de datos:**

```bash
./vendor/bin/sail artisan tinker --execute='
$s = App\Models\Subscription::latest("id")->first();
echo "BD: " . $s->status->value . PHP_EOL;
echo "MP: " . Illuminate\Support\Facades\Http::withToken(config("services.mercadopago.access_token"))
    ->get("https://api.mercadopago.com/preapproval/" . $s->mp_preapproval_id)->json("status") . PHP_EOL;
'
```

**Saber qué mandó Mercado Pago en la vuelta del checkout** (auditoría de pagos):

```bash
grep -o 'Retorno de la pasarela.\{0,320\}' storage/logs/laravel.log | tail -1
```

**Saber si llegaron webhooks y cómo se procesaron:**

```bash
./vendor/bin/sail artisan tinker --execute='
foreach (Illuminate\Support\Facades\DB::table("webhook_events")->orderByDesc("id")->take(5)->get() as $e) {
    $p = json_decode($e->payload, true);
    echo $e->event_id . " | " . ($p["type"] ?? "?") . " | procesado=" . ($e->processed_at ? "si" : "no") . PHP_EOL;
}
'
```

**Aislar si el problema es tu app o Mercado Pago:** firma tú mismo un payload y
mándalo a tu propio endpoint. Si responde `204` con firma válida y `401` con firma
falsa, **tu lado está bien** y el problema está en la configuración del panel.

```php
$ts = time();
$manifest = "id:TU_PREAPPROVAL_ID;request-id:prueba;ts:{$ts};";
$hash = hash_hmac('sha256', $manifest, config('services.mercadopago.webhook_secret'));

$r = Http::withHeaders(['x-signature' => "ts={$ts},v1={$hash}", 'x-request-id' => 'prueba'])
    ->post('https://TU-TUNEL/webhooks/mercadopago', [
        'id' => 99001,
        'type' => 'subscription_preapproval',
        'data' => ['id' => 'TU_PREAPPROVAL_ID'],
    ]);

echo $r->status(); // 204 esperado
```
