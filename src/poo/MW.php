<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;

require_once "AccesoDatos.php";
require_once "autentificadora.php";
require_once "Usuario.php";



class MW{

    /*1.- (método de clase) Si alguno de los campos correo o clave están vacíos (o los dos) retorne un
JSON con el mensaje de error correspondiente (y status 409).
Caso contrario, pasar al siguiente Middleware que:
2.- (método de instancia) Verifique que el correo y clave existan en la base de datos. Si NO
existen, retornar un JSON con el mensaje de error correspondiente (y status 403). Caso
contrario, acceder al verbo de la API.
Los Middlewares 1 y 2 aplicarlos al verbo POST de /login.*/

public function verificarCamposUser(Request $request, RequestHandler $handler) : ResponseMW {

    $datosEncode = $request->getParsedBody();
    $datos = json_decode($datosEncode['user']);

    if(!isset($datos->correo) || $datos->correo == NULL || $datos->correo == ""){

        $responseMW = new ResponseMW();
        $responseMW->withStatus(409);
        $responseMW->getBody()->write(json_encode(array("mensaje" => "correo invalido")));
    }else if(!isset($datos->clave) || $datos->clave == NULL || $datos->clave == "") {

        $responseMW = new ResponseMW();
        $responseMW->withStatus(409);
        $responseMW->getBody()->write(json_encode(array("mensaje" => "clave invalida")));
    }else{

        $response = $handler->handle($request);
        return $response;
    }

    return $responseMW;

}

public function verificarUserBD(Request $request, RequestHandler $handler) : ResponseMW {
    $flag=false;
    $datosEncode = $request->getParsedBody();
    $datos = json_decode($datosEncode['user']);
    $correo = $datos->correo;
    $clave = $datos->clave;

    $usuariosBD= Usuario::traerTodos();

    foreach($usuariosBD as $user){
    
        if($user['correo'] == $correo && $user['clave']== $clave ){

            $response = $handler->handle($request);
            return $response;

        }
    }

    $response= new ResponseMw();
    
    $response->withStatus(403);
    $response->getBody()->write(json_encode(array("mensaje"=>"El usuario no se encontro en la base de datos")));
    
    return $response;

}

public function verificarTokenHeader(Request $request, RequestHandler $handler) : ResponseMW {

    $flag=false;
    $token = $request->getHeader("token")[0];

    $rta = Autentificadora::verificarJWT($token);

    if(!$rta->verificado){
        $newResponse = new ResponseMW;    
        $newResponse->withStatus(403);
        $newResponse->getBody()->write(json_encode(array("mensaje"=>$rta->mensaje)));
        return $newResponse;
        
    }else{

        $response = $handler->handle($request);
        return $response;
    }
    

}

public function mostrarTablaUsuarios(Request $request, RequestHandler $handler) : ResponseMW {

    $response = $handler->handle($request);
    $usuariosEncode = $response->getBody();

    $usuarios = json_decode($usuariosEncode);
    $usuarios = $usuarios->dato;
   
    $tabla = MW::crearTablaUsuarios($usuarios);
   
        $responseMW = new ResponseMW();
        $responseMW->getBody()->write($tabla);
        return $responseMW;
    
}

public function crearTablaUsuarios($usuarios) {
    $tabla = '<table>';
    $tabla .= '<thead>';
    $tabla .= '<tr>';
    foreach (array_keys((array) $usuarios[0]) as $atributo) {
        $tabla .= '<th>' . $atributo . '</th>';
    }
    $tabla .= '</tr>';
    $tabla .= '</thead>';
    $tabla .= '<tbody>';
    foreach ($usuarios as $usuario) {
        $tabla .= '<tr>';
        foreach ((array) $usuario as $valor) {
            $tabla .= '<td>' . $valor . '</td>';
        }
        $tabla .= '</tr>';
    }
    $tabla .= '</tbody>';
    $tabla .= '</table>';

    return $tabla;
}

public function crearTablaUsuariosReducida($usuarios) {
    $tabla = '<table>';
    $tabla .= '<thead>';
    $tabla .= '<tr>';
    $camposDeseados = array("nombre", "correo", "apellido"); // Campos deseados
    foreach ($camposDeseados as $campo) {
        $tabla .= '<th>' . $campo . '</th>';
    }
    $tabla .= '</tr>';
    $tabla .= '</thead>';
    $tabla .= '<tbody>';
    foreach ($usuarios as $usuario) {
        $tabla .= '<tr>';
        foreach ($camposDeseados as $campo) {
            $valor = $usuario->$campo;
            $tabla .= '<td>' . $valor . '</td>';
        }
        $tabla .= '</tr>';
    }
    $tabla .= '</tbody>';
    $tabla .= '</table>';

    return $tabla;
}

public function verificarPropietario(Request $request, RequestHandler $handler) : ResponseMW {

    $token = $request->getHeader("token")[0];

    
        $payload = Autentificadora::obtenerPayLoad($token);
        $user= $payload->payload->data->usuario->user;
        $userDecode= json_decode($user);
        $mail= $userDecode->correo;
        $clave= $userDecode->clave;

        $response = $handler->handle($request);
        $usuariosEncode = $response->getBody();
        $usuarios= json_decode($usuariosEncode);
        $usuarios= $usuarios->dato;

        foreach($usuarios as $u){
            if($u->correo == $mail && $u->clave == $clave){
                if($u->perfil == "propietario"){
                
                    $tabla= MW::crearTablaUsuariosReducida($usuarios);
                    $newResponse = new ResponseMW();
                    $newResponse->getBody()->write($tabla);
                    return $newResponse;
      

                }
            }
        }

        $exito=false;
        $status = 403;
        $mensaje = "No verifica como propietario";
        $newResponse = new ResponseMW;        
        $newResponse->getBody()->write(json_encode(array("exito"=>$exito,"mensaje"=>$mensaje, "status" => $status)));
        return $newResponse;
        
    
}



public function mostrarTablaJuguetes(Request $request, RequestHandler $handler): ResponseMW {

$response = $handler->handle($request);
$juguetesEncode = $response->getBody();
$juguetes = json_decode($juguetesEncode);
$juguetes = $juguetes->dato;

$tabla = '<table>';
$tabla .= '<thead>';
$tabla .= '<tr>';
foreach (array_keys((array)$juguetes[0]) as $atributo) {
    $tabla .= '<th>' . $atributo . '</th>';
}
$tabla .= '</tr>';
$tabla .= '</thead>';
$tabla .= '<tbody>';
foreach ($juguetes as $jug) {
    if ($jug->id % 2 !== 0) { // Verificar si el ID es impar
        $tabla .= '<tr>';
        foreach ((array)$jug as $valor) {
            $tabla .= '<td>' . $valor . '</td>';
        }
        $tabla .= '</tr>';
    }
}
$tabla .= '</tbody>';
$tabla .= '</table>';

$responseMW = new ResponseMW();
$responseMW->getBody()->write($tabla);
return $responseMW;
}














}
?>