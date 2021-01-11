# laravel-stackdriver-gcl

Sistema de Storage para Logging en Google Cloud Storage para Laravel 7.

Este paquete es un `driver` para el registro y la generación de informes de errores para Google Cloud Platform Stackdriver.

## Instalación

```bash
composer require odem/laravel-stackdriver-gcl
```

Agregue un nuevo `driver` en su archivo de configuración `config/logging.php`

```php
        'stackdriver' => [
            'driver' => 'custom',
            'via' => \LaravelStackdriverGcl\StackdriverGoogle::class,
            'logName' => env('GCP_LOG_NAME'),
            'labels' => [
                'application' => env('APP_NAME'),
                'environment' => env('APP_ENV'),
            ],
            'level' => 'debug',
            'projectId' => env('GCP_PROJECT_ID'),
            'credentials' => env('GCP_CREDENTIALS')
        ]
```

### Autenticación

El cliente de Google utiliza algunos métodos para determinar cómo debe autenticarse con la API de Google.

Configurar las varibales enviroment `GCP_PROJECT_ID` y `GCP_CREDENTIALS` y `GCP_LOG_NAME` de la siguiente forma:
   ```
   GCP_PROJECT_ID=EL ID DE PROYECTO DE GOOGLE CLOUD
   GCP_CREDENTIALS=RUTA AL ARCHIVO CREDIENTIAL.JSON
   GCP_LOG_NAME=SU NOMBRE DE REGISTRO <SU NOMBRE DE PROYECTO LARAVEL>
   ```

### Habilitación

Se debe configurar la variable enviroment `LOG_CHANNEL` de la siguiente forma:
   ```
   LOG_CHANNEL=stackdriver
   ```

### Logs Personalizado

Agregar en `app/Exceptions/Handler.php` 
   ```
   use LaravelStackdriverGcl\StackdriverLogging;
   ```

En la función `report` agregar.
   ```
   $log = new StackdriverLogging();
   $log->customsLogs($exception);
   ```

Mientras se ejecute en entornos de **Google Cloud Platform** como **Google Compute Engine**, **Google App Engine** y **Google Kubernetes Engine**, no se necesita ningún trabajo adicional. El ID del proyecto y las credenciales se descubren automáticamente.

Para mas información consultar [Authentication documentation for the Google Cloud Client Library for PHP](https://github.com/googleapis/google-cloud-php/blob/master/AUTHENTICATION.md) 