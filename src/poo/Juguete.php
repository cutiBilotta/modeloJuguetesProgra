<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once "accesoDatos.php";

class Juguete{

    public int $id;
    public string $marca;
    public float $precio;
    public string $path_foto;

    public function traerTodos(Request $request, Response $response, array $args): Response 
	{
		$juguetes = Juguete::traerTodoLosJuguetes();
  
		if($juguetes != NULL){
            $exito=true;
            $status= 200;
            $mensaje= "Juguetes recuperados correctamente";
            $dato= $juguetes;
        }else{
            $exito=false;
            $status= 424;
            $mensaje= "Ocurrio un error al recuperar los juguetes";
            $dato= NULL;

        }

        $response->getBody()->write(json_encode(array("exito" => $exito, "mensaje" => $mensaje, "dato" =>$dato, "status" => $status)));
        return $response;
    
	}


    public function agregarJuguete(Request $request, Response $response, array $args): Response 
	{
        $arrayDeParametros = $request->getParsedBody();
        $arrayDeParametros = json_decode($arrayDeParametros['juguete_json']);

        $marca= $arrayDeParametros->marca;
        $precio= $arrayDeParametros->precio;

        $archivos = $request->getUploadedFiles();
        $destino = __DIR__ . "/../fotos/";

        $nombreAnterior = $archivos['foto']->getClientFilename();
        $extension = explode(".", $nombreAnterior);

        $extension = array_reverse($extension);

		$archivos['foto']->moveTo($destino . $marca . "." . $extension[0]);

        $jug = new Juguete();
        $jug->marca = $marca;
        $jug->precio = $precio;
        $jug->path_foto =  "./src/fotos/$marca.$extension[0]";
                                                                                                                                                                                                                                                             
        if($jug->agregarJugueteBD()){
			$exito = true;
			$mensaje= "Juguete agregado con exito";
			$status= 200;
		}else{
			$exito = false;
			$mensaje= "Ocurrio un error al agregar el juguete";
			$status= 418;
		}

       
        $response->getBody()->write(json_encode(array("exito" => $exito , "mensaje" => $mensaje, "status" =>$status )));

      	return $response;
    }
   
    public function agregarJugueteBD(){
        $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();
        
        $consulta =$objetoAccesoDato->retornarConsulta("INSERT into juguetes (marca,precio,path_foto)values(:marca,:precio,:path_foto)");
        
        $consulta->bindValue(':marca', $this->marca, PDO::PARAM_STR);
        $consulta->bindValue(':precio', $this->precio, PDO::PARAM_STR);
        $consulta->bindValue(':path_foto', $this->path_foto, PDO::PARAM_STR);
       
        if($consulta->execute()){
			return $consulta->rowCount();
		}else{
			return false;
		}	
    }

    public static function traerTodoLosJuguetes()
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta = $objetoAccesoDato->retornarConsulta("select id, marca, precio, path_foto from juguetes");
			
		if($consulta->execute()){
			return $consulta->fetchAll(PDO::FETCH_CLASS, "Juguete");
		}else{
			return false;
		}
	}
 

    public function modificarJuguete(Request $request, Response $response, array $args): Response
	{
		$arrayDeParametros = $request->getParsedBody();
        $datos = json_decode($arrayDeParametros['juguete']);        

        $jug = new Juguete();
	    $jug->id = $datos->id_juguete;
	    $jug->marca = $datos->marca;
	    $jug->precio = $datos->precio;

        $archivos = $request->getUploadedFiles();
        $destino = __DIR__ . "/../fotos/";

        $nombreAnterior = $archivos['foto']->getClientFilename();
        $extension = explode(".", $nombreAnterior);

        $extension = array_reverse($extension);

		$archivos['foto']->moveTo($destino . $jug->marca . "_modificacion" . ".". $extension[0]);

	   
	    $jug->path_foto = "./src/fotos/" . $jug->marca . "_modificacion" . $extension[0];

		$cant = $jug->modificarJugueteBD();
		if($cant >=1){
			$exito = true;
			$mensaje= "Juguete modificado correctamente";
			$status = 200;
		}else{
			$exito = false;
			$mensaje= "Ocurrio un error al modificar el juguete";
			$status = 418;
		}
		   
		$response->getBody()->write(json_encode(array("exito" => $exito , "mensaje" => $mensaje, "status" =>$status )));
      	return $response;	
	}
	
    public function modificarJugueteBD()
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta = $objetoAccesoDato->retornarConsulta("
				update juguetes
				set marca=:marca,
				precio=:precio,
				path_foto=:path_foto
				WHERE id=:id");
		$consulta->bindValue(':id',$this->id, PDO::PARAM_INT);
		$consulta->bindValue(':marca',$this->marca, PDO::PARAM_INT);
		$consulta->bindValue(':precio', $this->precio, PDO::PARAM_STR);
		$consulta->bindValue(':path_foto', $this->path_foto, PDO::PARAM_STR);

		if($consulta->execute()){
			return $consulta->rowCount();
		}else{
			return false;
		}
	 }

     public function eliminarJuguete(Request $request, Response $response, array $args): Response 
	{		 
        $id = isset($args['id_juguete']) ? $args['id_juguete'] : "---";
		$status=418;
	

		$jug = new Juguete();
		$jug->id = $id;
		$cantBorrados = $jug->eliminarJugueteBD();
		 
	
	    if($cantBorrados>=1){
	    	$mensaje = "Juguete eliminado exitosamente";
            $status=200;
            $exito=true;
			
		}else{
	    	$mensaje = "Ocurrio un error al eliminar Juguete";
            $status=418;
            $exito=false;
		}

		$response->getBody()->write(json_encode(array("exito"=>$exito,"mensaje"=>$mensaje, "status" => $status)));
		
        return $response;
    }

    public function eliminarJugueteBD()
	{
	 	$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta = $objetoAccesoDato->RetornarConsulta("delete from juguetes WHERE id=:id");	
		$consulta->bindValue(':id',$this->id, PDO::PARAM_INT);		
		if($consulta->execute()){
			return $consulta->rowCount();
		}else{
			return false;
		}
	}

}

?>