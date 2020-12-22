<?php

   # agrega acceso a los metodos token
   //require_once 'vendor/autoload.php';
   require_once 'auth.php';
   require_once 'ConfigDB.php';

   #Conexion para WEB APIS APLICATION @StevenMorenoCr
   class Conexion{

   	  private $ConfigDB;
	  private $tp_db = "MYSQL";
      public $conn;


	  public function __construct()
	  {
	  	$cnf_db = new ConfDB();
		  switch($this->tp_db)
		    {
		     case "PGSQL":
			  $this->ConfigDB = array('dbhost'=>'192.168.4.57','dbname'=>'bestelltdb','dbuser'=>'postgres','dbpass'=>'xx');
			 break;
			 case "MYSQL":
			   $this->ConfigDB = array('dbhost'=> $cnf_db->CONF_DB['host_db'],'dbname'=>$cnf_db->CONF_DB['name_db'],'dbuser'=>$cnf_db->CONF_DB['usr_db'],'dbpass'=>$cnf_db->CONF_DB['passwd_db'], 'dbport'=>'3306');
			 break;
			}
	  }

      #establecer la conexion con la base de datos
	  # validando el tipo de base de motor a conectar @StevenMorenoCr
      private function open_connection()
	  {
		switch($this->tp_db)
		{
			case "PGSQL":
			  #echo ConfigDB;
			  $this->conn = pg_connect("host=".$this->ConfigDB['dbhost']." dbname=".$this->ConfigDB['dbname']." user=".$this->ConfigDB['dbuser']." password=".$this->ConfigDB['dbpass'])
			  or die('No se ha podido conectar: ' . pg_last_error());
			  pg_set_client_encoding($this->conn, 'utf8');

			break;
			case "MYSQL":
			   $this->conn = new mysqli($this->ConfigDB['dbhost'], $this->ConfigDB['dbuser'], $this->ConfigDB['dbpass'], $this->ConfigDB['dbname'],$this->ConfigDB['dbport']);
				//verifica si hubo algun error
				if ($this->conn->connect_error) {
					echo 'Error de conexión con la base de datos: ( error #' . $this->conn->connect_error . ') '
							. $this->conn->connect_error;
					exit();
				}
				mysqli_set_charset($this->conn,"utf8");
			break;
		}
	  }

      #desconectar la base de datos @StevenMorenoCr
	   private function close_connection()
	   {
			switch($this->tp_db)
		    {
		     case "PGSQL":
			   pg_close($this->conn);
			 break;
			 case "MYSQL":
			   $this->conn->close();
			 break;
			}
	   }

	  #retorna el cursor @StevenMorenoCr
	  public function get_result_from_query($query)
	  {
	  	 $this->open_connection();
	  	 $rows = array();
		 switch($this->tp_db)
		    {
		     case "PGSQL":
			   $result = pg_query($query) or die('La consulta fallo: ' . pg_last_error());
			   while ($rows[] = pg_fetch_assoc($result));
		       pg_free_result($result);
		       $this->close_connection();
			 break;
			 case "MYSQL":
			   $result = $this->conn->query($query);
			   while ($rows[] = $result->fetch_assoc());
			   $result->close();
			   $this->close_connection();
			 break;
			}
		 #$rows['results']= $rows['cadena_connect'];

		 if($rows[count($rows)-1]== false || $rows[count($rows)-1]== null):
		   unset($rows[count($rows)-1]);
		 endif;

		 return $rows;
		 #return $query;
	  }


	  #realiza un evento simple @StevenMorenoCr
	  public function execute_single_query($query)
	  {
		  switch($this->tp_db)
		    {
		     case "PGSQL":
			   $this->open_connection();
		       return pg_query($query) or die('La consulta fallo'. pg_last_error());
		       $this->close_connection();
			 break;
			 case "MYSQL":
			   $this->open_connection();
		       return $this->conn->query($query);
		       $this->close_connection();
			 break;
			}

	  }
       
      # retornar el error
	  public function info_error(){
	  	return $this->conn->error;
	  }

	#realiza un evento simple @StevenMorenoCr retornando el id
	  public function execute_single_query_return($query)
	  {
		  switch($this->tp_db)
		    {
		     case "PGSQL":
			   $this->open_connection();
		       return pg_query($query) or die('La consulta fallo'. pg_last_error());
		       $this->close_connection();
			 break;
			 case "MYSQL":
			   $this->open_connection();
		       $this->conn->query($query);
			   return $this->conn->insert_id;
		       $this->close_connection();
			 break;
			}

	  }
    #realiza una evento simple con la conexion ya establecida
  public function execute_single_query_readycon($query)
  {
	return $this->conn->query($query);
  }

  public function get_result_from_query_readycon($query)
  {
  	 $rows = array();
  	 $result = $this->conn->query($query);
	 while ($rows[] = $result->fetch_assoc());
	 $result->close();

	 #$rows['results']= $rows['cadena_connect'];
	 if($rows[count($rows)-1]== false || $rows[count($rows)-1]== null):
	   unset($rows[count($rows)-1]);
	 endif;

	 return $rows;
  }

  # retorna el id de la inserccion se usa en caso de que este en TRANSACTION
  public function execute_single_query_return_readycon($query)
  {
  	        switch($this->tp_db)
		    {
		     case "PGSQL":
			   $this->open_connection();
		       return pg_query($query) or die('La consulta fallo'. pg_last_error());
			 break;
			 case "MYSQL":
			   $this->open_connection();
		       $this->conn->query($query);
			   return $this->conn->insert_id;
			 break;
			}
  }

	public function begin_trans()
	{
		$query = "START TRANSACTION;";
		$this->open_connection();
		$execute = $this->conn->query($query);
	}

	public function rollback()
	{
		$query = "ROLLBACK;";
		$execute = $this->conn->query($query);
		$this->close_connection();
	}

	public function commit()
	{
		$query = "COMMIT;";
		$execute = $this->conn->query($query);
		$this->close_connection();
	}

	public function validateKey($key,$user_id)
	{
        $query = "SELECT * FROM system_usuarios WHERE id_usuario = '$user_id'";
		$dt = $this->get_result_from_query($query);
		$hash = md5(sha1($dt[0]['passwd'])) . md5(sha1($dt[0]['fecha_registro']));
		#$hash = 'll';
		$state = false;
		if($hash == $key):
		 $state = true;
		else:
		 $state = false;
		endif;
		return $state;
	}

    #---------------------------- manejo de jwt --------------------------------------------

    #Generacion de token con jwt para la autenticación
	public function KeyGen($user,$clave)
	{
		$passwd = sha1($clave);
		$query = "SELECT u.*, l.fecha_tk
		          FROM system_usuarios u
				  LEFT JOIN log_token_user l
				  ON (u.id_usuario = l.id_usuario)
				  WHERE u.usuario = '$user'
				  AND u.passwd = '$passwd'";
		$dt = $this->get_result_from_query($query);

		$hash = AuthJWT::SignIn([
				 'id' => $dt[0]['id_usuario'],
                 'name' => $user,
				 'vld_id'=> $dt[0]['fecha_registro'],
				 'vld_tk'=>$dt[0]['fecha_tk']
                ]);
		return $hash;
	}

    # agrega el guid enviado por el cliente y genera log para jwt
	public function AddLogTk($user,$pwd,$guid)
	{
		$query = "INSERT INTO log_token_user
			               SELECT idUsuario, '$guid',now()
					  		FROM Usuario
					  		WHERE loginUsuario = '$user'
					  		AND claveUsuario = sha1('$pwd')
					  		ON DUPLICATE KEY UPDATE guid = VALUES(guid), fecha_tk = VALUES(fecha_tk);";
		#return $query;
		return $this->execute_single_query($query);
	}

    //# valida si el token es valido
	public function VerifyToken($dt,$isClient=false)
	{
		$decode = array();
		try {
			$decode = AuthJWT::GetData($dt,$isClient);
		} catch (Exception $e) {
			$decode = array('id'=>null,'vld_tk'=>null);
		}

		return $decode;

	}

    //# validacion despues de autenticar
    # recibe el token por parametro
    # retorna un arreglo con status -> boolean que define si es valido o no el token
    # tambien el nuevo token n_tk -> si el token validado es correcto se devuelve uno nuevo
    public function KeyGenerate($tk,$isClient=false)
	{
		  //$decript = get_object_vars($this->VerifyToken($tk));
		  $header = apache_request_headers();
		  if(isset($header['Authorization']))
		  {
		  	 $vr = $this->VerifyToken($header['Authorization'],$isClient);
             //$decript =  get_object_vars();
             $decript =  (is_array($vr)) ? $vr : get_object_vars($vr); 
		  }
          
		  return $decript;
		//return $a_rsp;
	}

	#---------------------------- cierra manejo de jwt --------------------------------------------

   } // cierra la clase

?>