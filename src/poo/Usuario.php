<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once "AccesoDatos.php";
require_once "autentificadora.php";


class Usuario{

    public function traerUsuarios(Request $request, Response $response, array $args) : Response {
        
        $datos = Usuario::traerTodos();
        if($datos != false){
            $exito=true;
            $status= 200;
            $mensaje= "Usuarios recuperados correctamente";
            $dato= $datos;
        }else{
            $exito=false;
            $status= 424;
            $mensaje= "Ocurrio un error al recuperar los usuarios";
            $dato= NULL;

        }

        $response->getBody()->write(json_encode(array("exito" => $exito, "mensaje" => $mensaje, "dato" =>$dato, "status" => $status)));
        return $response;
    }
    
    public static function traerTodos(){
        $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();
            
        $consulta = $objetoAccesoDato->retornarConsulta("SELECT id,clave,correo,nombre,apellido,foto, perfil FROM usuarios");

        if($consulta->execute()){
            $datos = $consulta->fetchAll(PDO::FETCH_ASSOC);
            return $datos;

        }else{
            return false;
        }
    }

    public function crearToken(Request $request, Response $response, array $args) : Response {

        $arrayDeParametros = $request->getParsedBody();
        $datos = new stdClass();
        $datos->usuario = $arrayDeParametros;
        $datos->alumno = "Agustina Bilotta";
        $datos->dni_alumno = 41392927;

        $token = Autentificadora::crearJWT($datos, 1000);

        $newResponse = $response->withStatus(200);

        $newResponse->getBody()->write(json_encode($token));
    
        return $newResponse;
    }

    public function verificarPorHeader(Request $request, Response $response, array $args) : Response {

        $token = $request->getHeader("token")[0];

        $obj_rta = Autentificadora::verificarJWT($token);

        $status = $obj_rta->verificado ? 200 : 403;
        $exito = $obj_rta->verificado ? true : false;


        $response->getBody()->write(json_encode(array("exito" => $exito, "status" => $status)));
    
        return $response;
    }


}

?>