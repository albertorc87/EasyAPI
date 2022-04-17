# EasyAPI Framework

EasyAPI es un micro framework pensado en el desarrollo de APIs con arquitectura REST y que no depende de ninguna librería.

## Guía para aprender a trabajar con APIs

Si muchas cosas de las que se comentan en esta documentación no las tienes claras, te dejo está guía donde explico cómo trabajar con APIs y cómo construirlas.

[Guía para aprender a trabajar con APIs](https://cosasdedevs.com/posts/guia-aprende-trabajar-con-apis/)

## Instalación

```
composer require --dev albertorc87/easyapi
```

## Hello world

Crea un directorio llamado public dentro de la carpeta raiz del proyecto y dentro de el un archivo llamado index.php con el siguiente código:

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

$_ENV['DEBUG_MODE'] = true;
$_ENV['ROOT_PROJECT'] = __DIR__ . '/../';

use EasyAPI\Router;

Router::get('/hello-world', function() {
    return view('json', 'Hello world');
});

$app = new EasyAPI\App();
$app->send();
```

Levantamos el server de PHP para verlo en funcionamiento:

```bash
php -S 0.0.0.0:8011 -t public/
```

Para que funcione el proyecto, debemos crear las variables de entorno **DEBUG_MODE** y **ROOT_PROJECT** para que funcione el framework antes de crear la instancia de EasyAPI\App.

**DEBUG_MODE** si está a true las excepciones no controladas se podrán ver en la respuesta, si no, se guardará un log en la carpeta logs en la raiz del proyecto.

**ROOT_PROJECT** aquí debemos añadir la ruta raiz del proyecto. Por el momento solo se utiliza para crear la carpeta logs y ahí guardar los logs que se generen.

En vez añadirlo en el código como he hecho, podemos valernos de librerías como [dotenv](https://github.com/vlucas/phpdotenv) para crear un archivo .env y que esta librería se encargue de cargar las variables de entorno.

## Enrutamiento

Para crear el enrutamiento, debemos importar la clase **EasyAPI\Router** y después añadir las urls que queramos. El método http lo indicamos según el método de Router que utilicemos y los cinco disponibles son get, post, put, patch y delete.

```php
<?php

use EasyAPI\Router;

use App\Controller\UserController;
use App\Middleware\IsAuth;
use App\Middleware\IsAdmin;

Router::get('/users', UserController::class . '@all', IsAdmin::class);
Router::post('/users', UserController::class . '@create');
Router::put('/users/(?<id>\d+)', UserController::class . '@update', IsAuth::class);
Router::patch('/users/(?<id>\d+)', UserController::class . '@partial', IsAuth::class);
Router::delete('/users/(?<id>\d+)', UserController::class . '@delete', IsAuth::class);
```

El primer parámetro que recibe la URI a la que queramos apuntar.

El segundo parámetro puede ser una clase y el método al que queramos llamar separado por la @. Ejemplo:

**App\Controller\UserController@all**

App\Controller\UserController sería la clase.

all sería el método que contiene la clase y al que queremos llamar.

El tercer parámetro es opcional y en el podemos añadir un middleware para por ejemplo comprobar que el usuario está autenticado en los casos en los que lo necesitemos. Explicaremos más adelante su funcionamiento.

### Envío de parámetros de ruta

Para enviar parámetros de ruta debemos utilizar regex, muy importante indicar el nombre del campo para que funcione

```php
<?php

Router::get('/users/(?<id>\d+)', function(int $id) {
    return view('json', 'Usuario con id ' . $id);
});
Router::get('/hello/(?<name>.*?)', function(string $name) {
    return view('json', 'Hello ' . $name);
});
Router::get('/users/(?<user_id>\d+)/tasks/(?<task_id>\d+)', function(int $user_id, int $task_id) {
    return view('json', [
        'user_id' => $user_id,
        'task_id' => $task_id,
    ]);
});
```

## Respuesta

La respuesta se realiza desde la clase **EasyAPI\Response**, todos los métodos y funciones anónimas que añadamos en una ruta deben añadir como respuesta esta clase, si no fallará. Para facilitar el trabajo, hay una función llamada **view** dentro de los helpers que recibe los mismos parámetros que **EasyAPI\Response** y retorna el objecto.

```php
<?php

Router::post('/users', function() {
    return view('json', 'User created succesfully', 201);
});
```

O

```php
<?php

Router::post('/users', function() {
    return new EasyAPI\Response('json', 'User created succesfully', 201);
});
```

Los parámetros que puede recibir **EasyAPI\Response**, son el tipo de dato que vamos a retornar que puede ser **json**, **raw** (para respuestas customizadas) y **html**, el segundo parámetro es la respuesta al usuario, puede ser un string y en el caso del formato **json** podemos enviar un array que el proceso lo convertirá a json. El tercer parámetro es el código de estado http que por defecto es el 200 y por último podemos añadir cabeceras extra en formato clave-valor.

```php
<?php

Router::get('/users', function() {

    $response = '
    <?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel><title>cosasdedevs.com</title><link>https://cosasdedevs.com/feed/</link><description>En cosasdedevs.com encontrarás tutoriales sobre Python, Django, PHP y Laravel</description><atom:link href="https://cosasdedevs.com/feed" rel="self"></atom:link><language>es</language><lastBuildDate>Sun, 17 Apr 2022 06:17:08 +0000</lastBuildDate><item><title>Guía para aprender a trabajar con APIs</title><link>https://cosasdedevs.com/posts/guia-aprende-trabajar-con-apis/</link><description>Con esta guía aprenderás todas las partes implicadas en el funcionamiento de una API, el protocolo HTTP y buenas prácticas para construir una API</description><guid>https://cosasdedevs.com/posts/guia-aprende-trabajar-con-apis/</guid></item></channel></rss>
    ';

    $headers = [
        'content-type' => 'application/xml'
    ];

    return new EasyAPI\Response('raw', $response, 200, $headers);
});
```

En el caso del formato **json** las respuestas están preformateadas. Si enviamos un código de estado menor al 400 mostrará la siguiente respuesta:

```json
{
    "status": "success",
    "data": "<la respuesta que enviemos>"
}
```

En el caso de código de estado errónea la respuesta será:

```json
{
    "status": "error",
    "error": "<la respuesta que enviemos>"
}
```

## Middleware

Las rutas permiten la opción de añadir middlewares para realizar validaciones extra antes de que se ejecute nuestro controlador. Cada middleware que creemos debe extender de la clase **EasyAPI\Middleware** y recibirá y retornará un objecto **EasyAPI\Request**. Este objecto lo podemos utilizar para guardar información que obtengamos desde el middleware y luego enviarla al controlador.

Ejemplo de middleware para verificar una autenticación por token JWT en el que guardamos el id de usuario.

```php
<?php

namespace App\V1\Middlewares;

use EasyAPI\Middleware;
use EasyAPI\Request;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Exception;

use EasyAPI\Exceptions\HttpException;

class BasicAuth extends Middleware
{
    public function handle(Request $request): Request
    {
        if(empty($_SERVER['HTTP_AUTHORIZATION'])) {
            throw new HttpException('You must send Authorization header', 422);
        }

        $token = $_SERVER['HTTP_AUTHORIZATION'];

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_KEY'], 'HS256'));
            $request->setData('user_id', $decoded->data->id);
            return $request;
        }
        catch(ExpiredException $e) {
            throw new HttpException('Your token has expired, please login again', 401);
        }
        catch(Exception $e) {
            throw new HttpException('An error has ocurred, please, make again login, if persists, contact with admin');
        }
    }
}
```

Posteiormente podremos acceder a la información almacenada en **Request** dentro del controlador recibiendo el parámetro **Request** que la clase **App** se encarga de enviar cada vez que ejecutamos una ruta.

```php
// Rutas, BasicAuth sería nuestro middleware el cual enviamos como tercer parámetro en la ruta.
.
.
.
Router::get('/v1/tasks/(?<id>\d+)', TaskController::class . '@show', BasicAuth::class);
.
.
.
// Controlador TaskController
public function show(int $id, Request $request)
{
    $user_id = $request->getData('user_id');
    $ddbb = new DBTask();
    $task = $ddbb->getTaskByUserId($id, $user_id);

    if(empty($task)) {
        throw new HttpException('Task not found', 404);
    }

    return view('json', $task);
}
```

Fijaos en que si enviamos parámetros de ruta, **Request** siempre será el último parámetro.

### Request

La clase **Request** tiene dos métodos, setData para guardar información en formato clave-valor y getData para obtener la información almacenada en una clave:

```php
<?php

namespace EasyAPI;

/**
 * Set data from middleware to later access it from a controller
 */
class Request
{
    private $data = [];

    public function setData(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    public function getData(string $key)
    {
        return $this->data[$key] ?? null;
    }
}
```

## HttpException

Si queremos lanzar una excepción que directamente envíe un mensaje al usuario, podemos usar **EAsyAPI\HttpException** el cual recibe un mensaje de error y el código de estado http. EasyAPI se encarga de traducirlo y convertirlo en una respuesta en formato JSON para el usuario.

En el controlador que utilizamos anteriormente:

```php

public function show(int $id, Request $request)
{
    $user_id = $request->getData('user_id');
    $ddbb = new DBTask();
    $task = $ddbb->getTaskByUserId($id, $user_id);

    if(empty($task)) {
        throw new HttpException('Task not found', 404);
    }

    return view('json', $task);
}
```

Si no encontramos la tarea, lanzamos HttpException con el mensaje y el código de estado 404 not found y el usuario recibirá esto:

```json
{
    "status": "error",
    "error": "Task not found"
}
```

Código de estado HTTP: 404

[Pronto aquí más info sobre este framework](https://cosasdedevs.com/)