<?php 

 class Utils{

      public function __construct()
      {

      }
      
      // divide un nombre compuesto separado por espcios
      public function getDivAspirantName($nombre)
      {
      	  $nombremd = array();
	      $nombre = explode(" ", strtoupper($nombre));
	      $conectores = array('DE','LA','DEL');
	      $n = 0;

	      foreach ($nombre as $k => $vl) {
	        if($vl!="") {
	          if(in_array($vl, $conectores)) {
	            $nombremd[$n-1] = $nombremd[$n-1]." ".$vl;
	          } else {
	            $nombremd[$n] = $vl;
	            $n++;
	          }
	        }
	      }

	      $name = '';
	      $nameDos = '';
	      $nameTres = '';
	      $lastName = '';
	      $lastNameDos = '';

	      switch (count($nombremd)) {
	        case 5:
	          $name = $nombremd[0];
	          $nameDos = $nombremd[1];
	          $nameTres = $nombremd[2];
	          $lastName = $nombremd[3];
	          $lastNameDos = $nombremd[4];
	          break;
	        case 4:
	          $name = $nombremd[0];
	          $nameDos = $nombremd[1];
	          $nameTres = '';
	          $lastName = $nombremd[2];
	          $lastNameDos = $nombremd[3];
	          break;
	        case 3:
	          $name = $nombremd[0];
	          $nameDos = '';
	          $nameTres = '';
	          $lastName = $nombremd[1];
	          $lastNameDos = $nombremd[2];
	          break;
	        case 2:
	          $name = $nombremd[0];
	          $nameDos = '';
	          $nameTres = '';
	          $lastName = $nombremd[1];
	          $lastNameDos = '';
	          break;
	        default:
	          $name = $nombremd[0];
	          $nameDos = '';
	          $nameTres = '';
	          $lastName = 'NA';
	          $lastNameDos = '';
	          break;
	      }
	      return array('p_n'=>$name,'s_n'=>$nameDos,
                       't_n'=>$nameTres,'p_a'=>$lastName, 
                       's_a'=>$lastNameDos
	                   );
      }

      /*
       * @StevenMorenoCr
       * $resp -> arreglo cursor retornado por la base de datos (arreglo asociativo)
       * $cols -> columnas que les desea colocar a nuevo arreglo ya que vienen en indices numericos (arreglo indices)
       * $cols_convert -> columnas del arreglo principal que desea convertir en anidado (arreglo indices)
       * recibe un arreglo cursor que tiene un group concat lo covierte en array
      */
      public function ArrayAnidado($resp,$cols,$cols_convert)
      {
           // iteracion arreglo principal
           foreach ($resp as $kk => $item) {
              //columnas del arreglo principal que desea convertir a anidado
              // $nm_col_array -> nombre de la columna del arreglo principal que se desea converir de cadena a arreglo
              foreach ($cols_convert as $nm_col_array) {
                # code...
                $array_anid = array();
                $prim_div = explode('|_|', $item[$nm_col_array]);
                foreach ($prim_div as $k => $cnt_dato) {
                  # code...
                  $item_hoja = array();
                  $seg_div = explode('**',$cnt_dato);
                  // asigna los nombres de las columnas sugeridas
                  foreach ($cols as $key => $nm_col):
                    $item_hoja[$nm_col] = $seg_div[$key];
                  endforeach;
                  // se agrega al array anidado sin asignar aun al principal
                  array_push($array_anid, $item_hoja);
                }
                // se reemplaza por la etiqueta que se necesita
                $resp[$kk][$nm_col_array] = $array_anid;
               } 
           }

           return $resp;
      }


      /*
       * @StevenMorenoCr
       * $resp -> arreglo cursor retornado por la base de datos (arreglo asociativo)
       * $cols -> columnas que les desea colocar a nuevo arreglo ya que vienen en indices numericos arreglo asociativo ->(arreglo indices)
       * $cols_convert -> columnas del arreglo principal que desea convertir en anidado (arreglo indices)
       * recibe un arreglo cursor que tiene un group concat lo covierte en array
       * la diferencia con el anterior es que puedes personalizar las columnas 
      */
      public function ArrayAnidadoDiff($resp,$cols,$cols_convert)
      {
           // iteracion arreglo principal
           foreach ($resp as $kk => $item) {
              //columnas del arreglo principal que desea convertir a anidado
              // $nm_col_array -> nombre de la columna del arreglo principal que se desea converir de cadena a arreglo
              foreach ($cols_convert as $nm_col_array) {
                # code...
                $array_anid = array();
                if(strpos($item[$nm_col_array],'**')):
                  $prim_div = explode('|_|', $item[$nm_col_array]);
                  foreach ($prim_div as $k => $cnt_dato) {
                    # code...
                    $item_hoja = array();
                    $seg_div = explode('**',$cnt_dato);
                    // asigna los nombres de las columnas sugeridas
                    foreach ($cols[$nm_col_array] as $key => $nm_col):
                      $item_hoja[$nm_col] = $seg_div[$key];
                    endforeach;
                    // se agrega al array anidado sin asignar aun al principal
                    array_push($array_anid, $item_hoja);
                  }
                endif;
                // se reemplaza por la etiqueta que se necesita
                $resp[$kk][$nm_col_array] = $array_anid;
               } 
           }

           return $resp;
      }


      /*
      * convierte un arreglo asociativo en arreglo de indices
      * $cursor -> los datos a iterar 
      * $cols -> columnas las cuales se van a mostrar
      */
      public function FormatoArrayMui($cursor,$cols){
            $n_arr = array();
            foreach ($cursor as $vl) {
               $arr_index = array();
               foreach ($vl as $key => $item) {
                   // valida si son columnas especificas
                  if(count($cols) > 0){
                     if(in_array($key, $cols)):
                        $arr_index[] = $item;
                     endif;            
                  }else{
                    $arr_index[] = $item;
                  }
               }
              $n_arr[] = $arr_index; 
            }

           return $n_arr;
      }

       /*
       * @StevenMorenoCr
       * $resp -> arreglo cursor retornado por la base de datos (arreglo asociativo)
       * $cols -> columnas que les desea colocar a nuevo arreglo ya que vienen en indices numericos arreglo asociativo ->(arreglo indices)
       * $cols_convert -> columnas del arreglo principal que desea convertir en anidado (arreglo indices)
       * recibe un arreglo cursor que tiene un group concat lo covierte en array
       * la diferencia con el anterior es que puedes personalizar las columnas 
       * este retorna un iterador a cada registro 
      */
      public function ArrayAnidadoDiffWithIterador($resp,$cols,$cols_convert)
      {
           // iteracion arreglo principal
           foreach ($resp as $kk => $item) {
              //columnas del arreglo principal que desea convertir a anidado
              // $nm_col_array -> nombre de la columna del arreglo principal que se desea converir de cadena a arreglo
              foreach ($cols_convert as $nm_col_array) {
                # code...
                $array_anid = array();
                if(strpos($item[$nm_col_array],'**')):
                  $prim_div = explode('|_|', $item[$nm_col_array]);
                  foreach ($prim_div as $k => $cnt_dato) {
                    # code...
                    $item_hoja = array();
                    $seg_div = explode('**',$cnt_dato);
                    // asigna los nombres de las columnas sugeridas
                    foreach ($cols[$nm_col_array] as $key => $nm_col):
                      $item_hoja[$nm_col] = $seg_div[$key];
                    endforeach;
                    $item_hoja['iterator'] = ($k+1);
                    // se agrega al array anidado sin asignar aun al principal
                    array_push($array_anid, $item_hoja);
                  }
                endif;
                // se reemplaza por la etiqueta que se necesita
                $resp[$kk][$nm_col_array] = $array_anid;
               } 
           }

           return $resp;
      }

      // radom de cadena
      public function genGuidStr($length = 10){
        return substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
      }
      
 } // cierra la clase 

?>