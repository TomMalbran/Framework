# API de MercadoPago

1. [Vincular](#vincular)
2. [Preferencias](#preferencias)
3. [Pagos](#pagos)
4. [Reportes](#reportes)



## Vincular

Cuando se usa el modelo de Marketplace la idea es vincular la cuenta Comprador con la Aplicación del Marketplace. Esto se hace en 3 pasos:

**Docs:** https://www.mercadopago.com.ar/developers/es/docs/checkout-pro/additional-content/security/oauth/creation


### 1. Crear un código de autenticación:

Se redirige al usuario a la url: `https://auth.mercadopago.com/authorization?client_id=APP_ID&response_type=code&platform_id=mp&state=RANDOM_ID&redirect_uri=https://www.redirect-url.com`.

Donde se debe reemplazar:
- `APP_ID` por el ID de la aplicación.
- `RANDOM_ID` por un código aleatorio.
- `redirect_uri` por la url de redirección que debe estar configurada en la cuenta.

Luego de obtener la url se debe redirigir al usuario a esa pantalla. Ahí primero se pide el país y luego confirmar la integración. Se usa el usuario logueado y si no hay ninguno se pide.


### 2. Crear un Access Token

Luego de que el usuario confirma regresa a la url `https://www.redirect-url.com?code=CODE&state=RANDOM_ID`.

Con el código de autenticación (`CODE`) se puede crear el access token haciendo un POST a la url `https://api.mercadopago.com/oauth/token` con los datos de la aplicación y el código enviado.

**API:** https://www.mercadopago.com.ar/developers/es/reference/oauth/_oauth_token/post


**Ejemplo:**

```bash
curl -X POST \
    'https://api.mercadopago.com/oauth/token'\
    -H 'Content-Type: application/json' \
    -d '{
        "client_id": "client_id",
        "client_secret": "client_secret",
        "code": "TG-XXXXXXXX-241983636",
        "grant_type": "authorization_code",
        "redirect_uri": "https://www.redirect-url.com"
    }' | jq .
```


### 3. Actualizar el Access Token

Luego de 180 días es necesario actualizarlo usando el Refresh Token. Para eso se hace un post a la url `https://api.mercadopago.com/oauth/token` los datos de la aplicación y el refresh token.

**API:** https://www.mercadopago.com.ar/developers/es/reference/oauth/_oauth_token/post


**Ejemplo:**

```bash
curl -X POST \
    'https://api.mercadopago.com/oauth/token'\
    -H 'Content-Type: application/json' \
    -d '{
        "client_id": "client_id",
        "client_secret": "client_secret",
        "refresh_token": "TG-XXXXXXXX-241983636",
        "grant_type": "refresh_token"
    }' | jq .
```



## Preferencias

La API de preferencias se usa para crear urls de pagos


### 1. Obtener preferencia

Realizar un **GET** a la url `https://api.mercadopago.com/checkout/preferences/{id}` reemplazando el **id** con el de la preferencia y enviando el **access token** correspondiente en el header.


**API:** https://www.mercadopago.com.ar/developers/es/reference/preferences/_checkout_preferences_id/get


**Ejemplo:**

```bash
curl -X GET \
    'https://api.mercadopago.com/checkout/preferences/{id}'\
    -H 'Content-Type: application/json' \
    -H 'Authorization: Bearer APP_USR-...' | jq .
```


### 2. Crear preferencia

Para crear una preferencia de pago se debe realizar un **POST** a la url `https://api.mercadopago.com/checkout/preferences` con los datos necesarios para hacer el pago y enviando el **access token** correspondiente en el header.

El único dato requerido es el listado de **items**.

**API:** https://www.mercadopago.com.ar/developers/es/reference/preferences/_checkout_preferences/post


**Ejemplo:**

```bash
curl -X POST \
    'https://api.mercadopago.com/checkout/preferences'\
    -H 'Content-Type: application/json' \
    -H 'Authorization: Bearer APP_USR-...' \
    -d '{
        "back_urls": {},
        "differential_pricing": {
            "id": null
        },
        "expires": false,
        "items": [
            {
                "title": "Dummy Title",
                "description": "Dummy description",
                "picture_url": "http://www.myapp.com/myimage.jpg",
                "category_id": "car_electronics",
                "quantity": 1,
                "currency_id": "U$",
                "unit_price": 10
            }
        ],
        "marketplace_fee": null,
        "metadata": null,
        "payer": {
            "phone": {
                "number": null
            },
            "identification": {},
            "address": {
                "street_number": null
            }
        },
        "payment_methods": {
        "excluded_payment_methods": [
            {}
        ],
        "excluded_payment_types": [
            {}
        ],
        "installments": null,
        "default_installments": null
    },
    "shipments": {
        "local_pickup": false,
        "default_shipping_method": null,
        "free_methods": [
            {
                "id": null
            }
        ],
        "cost": null,
        "free_shipping": false,
        "receiver_address": {
            "street_number": null
        }
    },
    "tracks": [
        {
            "type": "google_ad"
        }
    ]
}'
```


### 3. Actualizar preferencia

Para crear una preferencia de pago se debe realizar un **PUT** a la url `https://api.mercadopago.com/checkout/preferences/{id}` reemplazando el `{id}` por el de la preferencia y enviando en el cuerpo los datos a modificar y enviando el **access token** correspondiente en el header.

Los datos a enviar son los mismos que se usan para crear una preferencia. En caso de querer cancelar una preferencia se puede modificar la expiración al día actual.

**API:** https://www.mercadopago.com.ar/developers/es/reference/preferences/_checkout_preferences_id/put



## Pagos

La API de Pagos se usa para obtener y modificar los pagos realizados con las urls de pagos


### 1. Obtener pago

Realizar un **GET** a la url `https://api.mercadopago.com/v1/payments/{id}` reemplazando el **id** con el del pago y enviando el **access token** correspondiente en el header.


**API:** https://www.mercadopago.com.ar/developers/es/reference/payments/_payments_id/get


**Ejemplo:**

```bash
curl -X GET \
    'https://api.mercadopago.com/v1/payments/{id}'\
    -H 'Content-Type: application/json' \
    -H 'Authorization: Bearer APP_USR-...' | jq .
```

**Respuesta:**

```json
{
    "accounts_info": null,
    "acquirer_reconciliation": [],
    "additional_info": {
        "authentication_code": null,
        "available_balance": null,
        "ip_address": "186.182.14.74",
        "items": [
            {
                "category_id": null,
                "description": null,
                "id": "1576",
                "picture_url": null,
                "quantity": "1",
                "title": "AMOXICILINA SANT GALL FRIBURG 250 mg susp.ext.x 60 ml",
                "unit_price": "29.58"
            }
        ],
        "nsu_processadora": null,
        "payer": {
            "first_name": "Tomás",
            "last_name": "Malbran"
        }
    },
    "authorization_code": null,
    "binary_mode": false,
    "brand_id": null,
    "build_version": "3.31.0",
    "call_for_authorize_id": null,
    "captured": true,
    "card": {},
    "charges_details": [
        {
            "accounts": {
                "from": "collector",
                "to": "marketplace_owner"
            },
            "amounts": {
                "original": 3.22,
                "refunded": 3.22
            },
            "client_id": 0,
            "date_created": "2023-12-28T19:20:58.000-04:00",
            "id": "69431444661-001",
            "last_updated": "2023-12-28T20:03:51.000-04:00",
            "metadata": {},
            "name": "third_payment",
            "refund_charges": [
                {
                    "amount": 3.22,
                    "client_id": 6978451360688203,
                    "currency_id": "ARS",
                    "date_created": "2023-12-28T20:03:51.000-04:00",
                    "operation": {
                        "id": 1576182673,
                        "type": "refund_payment"
                    },
                    "reserve_id": null
                }
            ],
            "reserve_id": null,
            "type": "fee"
        },
        {
            "accounts": {
                "from": "collector",
                "to": "mp"
            },
            "amounts": {
                "original": 2.5,
                "refunded": 2.5
            },
            "client_id": 0,
            "date_created": "2023-12-28T19:20:58.000-04:00",
            "id": "69431444661-002",
            "last_updated": "2023-12-28T20:03:51.000-04:00",
            "metadata": {},
            "name": "mercadopago_fee",
            "refund_charges": [
                {
                    "amount": 2.5,
                    "client_id": 6978451360688203,
                    "currency_id": "ARS",
                    "date_created": "2023-12-28T20:03:51.000-04:00",
                    "operation": {
                        "id": 1576182673,
                        "type": "refund_payment"
                    },
                    "reserve_id": null
                }
            ],
            "reserve_id": null,
            "type": "fee"
        }
    ],
    "collector_id": 90076076,
    "corporation_id": null,
    "counter_currency": null,
    "coupon_amount": 0,
    "currency_id": "ARS",
    "date_approved": "2023-12-28T19:20:59.000-04:00",
    "date_created": "2023-12-28T19:20:58.000-04:00",
    "date_last_updated": "2023-12-28T20:03:53.000-04:00",
    "date_of_expiration": null,
    "deduction_schema": null,
    "description": "AMOXICILINA SANT GALL FRIBURG 250 mg susp.ext.x 60 ml",
    "differential_pricing_id": null,
    "external_reference": "159",
    "fee_details": [
        {
            "amount": 2.5,
            "fee_payer": "collector",
            "type": "mercadopago_fee"
        },
        {
            "amount": 3.22,
            "fee_payer": "collector",
            "type": "application_fee"
        }
    ],
    "financing_group": null,
    "id": 69431444661,
    "installments": 1,
    "integrator_id": null,
    "issuer_id": null,
    "live_mode": true,
    "marketplace_owner": 730763777,
    "merchant_account_id": null,
    "merchant_number": null,
    "metadata": {},
    "money_release_date": "2023-12-28T19:20:59.000-04:00",
    "money_release_schema": null,
    "money_release_status": "released",
    "notification_url": "https://dev.benvida.ar/hook/mp/notification.php",
    "operation_type": "regular_payment",
    "order": {
        "id": "14488548511",
        "type": "mercadopago"
    },
    "payer": {
        "email": "malbrantomas@gmail.com",
        "entity_type": null,
        "first_name": null,
        "id": "26527658",
        "identification": {
            "number": "20331934977",
            "type": "CUIL"
        },
        "last_name": null,
        "operator_id": null,
        "phone": {
            "number": null,
            "extension": null,
            "area_code": null
        },
        "type": null
    },
    "payment_method": {
        "forward_data": {
            "credits_pricing_id": "26527658-20231228232031295-jUEeTCM"
        },
        "id": "consumer_credits",
        "type": "digital_currency"
    },
    "payment_method_id": "consumer_credits",
    "payment_type_id": "digital_currency",
    "platform_id": null,
    "point_of_interaction": {
        "business_info": {
            "branch": null,
            "sub_unit": "checkout_pro",
            "unit": "online_payments"
        },
        "location": {
            "source": "Payer",
            "state_id": "AR-C"
        },
        "transaction_data": {
            "e2e_id": null
        },
        "type": "CHECKOUT"
    },
    "pos_id": null,
    "processing_mode": "aggregator",
    "refunds": [
        {
            "additional_data": null,
            "adjustment_amount": 0,
            "alternative_refund_mode": null,
            "amount": 29.58,
            "amount_refunded_to_payer": null,
            "date_created": "2023-12-28T20:03:51.000-04:00",
            "e2e_id": null,
            "expiration_date": null,
            "external_operations": [],
            "external_refund_id": 26872134,
            "funder": null,
            "id": 1576182673,
            "labels": [],
            "metadata": {
                "status_detail": null
            },
            "partition_details": [],
            "payment_id": 69431444661,
            "reason": null,
            "refund_mode": "standard",
            "source": {
                "id": "90076076",
                "name": "Juan Pablo Parral",
                "type": "collector"
            },
            "status": "approved",
            "unique_sequence_number": null
        }
    ],
    "shipping_amount": 0,
    "sponsor_id": null,
    "statement_descriptor": null,
    "status": "refunded",
    "status_detail": "refunded",
    "store_id": null,
    "tags": null,
    "taxes_amount": 0,
    "transaction_amount": 29.58,
    "transaction_amount_refunded": 29.58,
    "transaction_details": {
        "acquirer_reference": null,
        "external_resource_url": null,
        "financial_institution": null,
        "installment_amount": 0,
        "net_received_amount": 23.86,
        "overpaid_amount": 0,
        "payable_deferral_period": null,
        "payment_method_reference_id": "572485087",
        "total_paid_amount": 29.58
    }
}
```


### 2. Cancelar un pago

**Cancelaciones** ocurren cuando se realiza una compra pero el pago aún no ha sido aprobado por algún motivo. En este caso, considerando que la transacción no fue procesada y el establecimiento no recibió ningún monto, la compra se cancela y no hay cargo.

Realizar un **PUT** a la url `https://api.mercadopago.com/v1/payments/{id}` reemplazando el **id** con el del pago, enviando el estado de cancelado en el cuerpo y el **access token** correspondiente en el header.


**Docs:** https://www.mercadopago.com.ar/developers/es/docs/checkout-pro/additional-content/cancellations-and-refunds

**API:** https://www.mercadopago.com.ar/developers/es/reference/chargebacks/_payments_payment_id/put


**Ejemplo:**

```bash
curl -X PUT \
    'https://api.mercadopago.com/v1/payments/{id}'\
    -H 'Content-Type: application/json' \
    -H 'Authorization: Bearer APP_USR-...' \
    -d '{ "status": "cancelled" }' | jq .
```


### 3. Reembolsar un pago

**Reembolsos** son transacciones que se realizan cuando un determinado cargo se revierte y las cantidades pagadas se devuelven al comprador. Esto significa que el cliente recibirá en su cuenta o en el extracto de su tarjeta de crédito el monto pagado por la compra de un determinado producto o servicio.

Realizar un **POST** a la url `https://api.mercadopago.com/v1/payments/{id}/refunds` reemplazando el **id** con el del pago y el **access token** correspondiente en el header.

Si se quiere hacer un reembolso parcial se puede enviar el campo **amount** en el cuerpo con el valor a reembolsar.

Al ejecutar las API a las que se hace referencia en esta documentación, es posible que encuentres el atributo **X-Idempotency-Key**. Completarlo es importante para asegurar la ejecución y re-ejecución de solicitudes sin efectos secundarios como pagos duplicados en casos de reembolso.


**Docs:** https://www.mercadopago.com.ar/developers/es/docs/checkout-pro/additional-content/cancellations-and-refunds

**API:** https://www.mercadopago.com.ar/developers/es/reference/chargebacks/_payments_id_refunds/post


**Ejemplo:**

```bash
curl -X POST \
      'https://api.mercadopago.com/v1/payments/{id}/refunds'\
       -H 'Content-Type: application/json' \
       -H 'X-Idempotency-Key: 77e1c83b-7bb0-437b-bc50-a7a58e5660ac' \
       -H 'Authorization: Bearer APP_USR-...' \
       -d '{ "amount": 5 }' | jq .
```


### 4. Respuesta del Hook

Cada vez que se genera un pago o se modifica uno el dato a:
https://dash.benvida.ar/hook/mp/notification.php

Pago creado:

```json
{
    "action": "payment.created",
    "api_version": "v1",
    "data": {
        "id": "69663016414"
    },
    "date_created": "2023-12-29T16:11:23Z",
    "id": 109499601564,
    "live_mode": true,
    "type": "payment",
    "user_id": "90076076"
}
```

Pago actualizado:

```json
{
    "action": "payment.updated",
    "api_version": "v1",
    "data": {
        "id": "69663016414"
    },
    "date_created": "2023-12-29T16:11:23Z",
    "id": 109500510036,
    "live_mode": true,
    "type": "payment",
    "user_id": "90076076"
}
```

Para probar se puede hacer (reemplazando la url):

```bash
curl -X POST \
    'http://localhost:8888/path/mp.php' \
    -d '{
        "action": "payment.created",
        "api_version": "v1",
        "data": {
            "id": "69663016414"
        },
        "date_created": "2023-12-29T16:11:23Z",
        "id": 109499601564,
        "live_mode": true,
        "type": "payment",
        "user_id": "90076076"
    }'
```



## Reportes

**Docs:** https://www.mercadopago.com.ar/developers/es/docs/checkout-api/additional-content/reports/account-money/api


### 1. Generar de forma manual

Primero se puede generar un reporte manual o usar la generación automática.

```bash
curl -X POST \
    'https://api.mercadopago.com/v1/account/settlement_report' \
    -H 'accept: application/json' \
    -H 'content-type: application/json' \
    -H 'Authorization: Bearer APP_USR-...' \
    -d '{
        "begin_date": "2023-10-01T00:00:00Z",
        "end_date": "2023-10-30T00:00:00Z"
    }' | jq .
```

Recibirás como respuesta un `HTTP STATUS 202 (Accepted)`, y el reporte se generará de manera asincrónica.


### 2. Listar reportes

Luego se debe buscar el reporte en el listado de reportes:

```bash
curl -X GET \
    'https://api.mercadopago.com/v1/account/settlement_report/list'\
    -H 'accept: application/json' \
    -H 'Authorization: Bearer APP_USR-...' | jq .
```

Ejemplo de respuesta:

```json
[
    {
        "id": 12345678,
        "user_id": USER-ID,
        "begin_date": "2015-05-01T00:00:00Z",
        "end_date": "2015-06-01T23:59:59Z",
        "file_name": "settlement-report-USER_ID-2016-01-20-131015.csv",
        "created_from": "manual",
        "date_created": "2016-01-20T10:07:53.000-04:00"
    },
    {
        ...
    }
]
```


### 3. Descargar reporte

Finalmente tomando el nombre de `file_name` del resultado anterior se puede descargar un reporte:

```bash
curl -X GET \
    'https://api.mercadopago.com/v1/account/settlement_report/file_name.csv' \
    -H 'Authorization: Bearer APP_USR-...' \
    > 'report.csv'
```


### 4. Respuesta del Hook

Cada vez que se genera un reporte se envía el dato a la url correspondiente.

```json
{
    "payload": {
        "creation_type": "manual",
        "files": [
            {
                "name": "filename.csv",
                "type": "file/csv",
                "url": "https://mercadopago.com.ar/balance/reports/release/statements/release-123456-123456/download?format=csv"
            },
            {
                "name": "filename.xlsx",
                "type": "file/xlsx",
                "url": "https://mercadopago.com.ar/balance/reports/release/statements/release-123456-123456/download?format=xlsx"
            }
        ],
        "generation_date": "2023-08-25T21:41:41.897Z",
        "is_test": true,
        "report_type": "report_test",
        "request_date": "2023-08-25T21:41:41.897Z",
        "signature": "$2b$10$Mlvm2Mn9AAFoXzfqkdHHmuKMBEJ4YucGT6OmFGvNMTyDX6eR4V5ea",
        "status": "processed",
        "transaction_id": "test_transaction_id"
    }
}
```

Para probar se puede hacer (modificando la url):

```bash
curl -X POST \
    'http://localhost:8888/path/mp.php' \
    -d '{
        "payload": {
            "creation_type": "manual",
            "files": [
                {
                    "name": "Benvida-Mercadopago-2023-10-31-103542.csv",
                    "type": "file/csv",
                    "url": "https://www.mercadopago.com.ar/balance/reports/settlement/statements/settlement-730763777-32388409/download?format=csv"
                },
                {
                    "name": "Benvida-Mercadopago-2023-10-31-103542.xlsx",
                    "type": "file/xlsx",
                    "url": "https://www.mercadopago.com.ar/balance/reports/settlement/statements/settlement-730763777-32388409/download?format=xlsx"
                }
            ],
            "generation_date": "2023-10-31T9:35:42-0400",
            "is_test": false,
            "report_type": "settlement",
            "request_date": "2023-10-31T9:35:42-0400",
            "signature": "$2a$10$HilCfo9z2ujJpcjkAkxJZ.BePAIcUHMWyz8C3iUlHBDrEL3LxE7A.",
            "status": "enabled",
            "transaction_id": "32388409"
        }
    }'
```
