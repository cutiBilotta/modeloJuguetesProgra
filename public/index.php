<?php

use Slim\Factory\AppFactory;


require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/poo/Usuario.php';
require __DIR__ . '/../src/poo/Juguete.php';
require __DIR__ . '/../src/poo/MW.php';
use \Slim\Routing\RouteCollectorProxy;

$app = AppFactory::create();


$app->get('/', \Usuario::class . ':traerUsuarios');
$app->post('/', \Juguete::class . ':agregarJuguete')->add(\MW::class . ':verificarTokenHeader');
$app->get('/juguetes', \Juguete::class . ':traerTodos');
$app->post('/login', \Usuario::class . ':crearToken')->add(\MW::class . ':verificarUserBD')->add(\MW::class . ':verificarCamposUser');
$app->get('/login', \Usuario::class . ':verificarPorHeader');

$app->group('/toys', function (RouteCollectorProxy $grupo) {  

    $grupo->post('/', \Juguete::class . ':modificarJuguete');
    $grupo->delete('[/[{id_juguete}]]', \Juguete::class . ':eliminarJuguete');


})->add(\MW::class . ':verificarTokenHeader');

$app->group('/tablas', function (RouteCollectorProxy $grupo) {  

    $grupo->get('/usuarios', \Usuario::class . ':traerUsuarios')->add(\MW::class . ':mostrarTablaUsuarios');
    $grupo->post('/usuarios', \Usuario::class . ':traerUsuarios')->add(\MW::class . ':verificarPropietario')->add(\MW::class . ':verificarTokenHeader');
    $grupo->get('/juguetes', \Juguete::class . ':traerTodos')->add(\MW::class . ':mostrarTablaJuguetes');


});








$app->run();


?>