<?php 

    include_once '../../../../Core/Conexion.php';	
	require_once '../../../../Core/auth.php';
	require_once '../../../../Core/Utils.php';

	use PhpOffice\PhpSpreadsheet\IOFactory;

    class Maps
	{
		public $conn;
		# almacena todas las consultas ejecutadas con el objetivo de retornar si es necesario
		public $qs;
	    public $utils;
	    # error upload
	    public $error_upload;
	    public $rows_affected;

	    public function __construct()
	    {
	    	$this->conn = new Conexion();
	    	$this->qs = array();
		    $this->utils = new Utils();
		    $this->error_upload = "";
		    $this->rows_affected = array();
	    }


	    # carga masiva del xlsx
	    public function UploadXlsxMaps($file,$year){
	    	$status = true;
            // guardar en disco archivo
            if( isset($file['name']) ){
            	$carpeta = "../../../../uploads";
            	// crear carpeta si no existe
            	if(!file_exists($carpeta)){
	         	    mkdir($carpeta, 0777, true);
	            }
            	$ruta = $carpeta."/".$file['name'];
            	if(move_uploaded_file($file['tmp_name'], $ruta))
            	{
                   $inputFileType = 'Xlsx';
                   $reader = IOFactory::createReader($inputFileType);
                   $spreadsheet = $reader->load($ruta);
                   // cantidad de hojas 
                   $sheetCount = $spreadsheet->getSheetCount();
                   # index -> function
                   # 3=>"Proyects",2=>"Axis",4=>"Location",5=>"Beneficiaries",6=>"Indicators"
                   $sheesAvailables = array(3=>"Proyects",2=>"Axis",4=>"Location",5=>"Beneficiaries",6=>"Indicators");
                   foreach ($sheesAvailables as $index => $fnt ) {
                   	        # ejecucion del metodo que esta en el arreglo
                            $status = $status && $this->$fnt($spreadsheet,$index,$year);
                            # validar que la insercción en tabla temporal se realizo efectiva
                            if(!$status){
                            	$this->error_upload = "Problemas al cargar datos de la hoja $fnt error: ".$this->conn->info_error();
                            	$this->CleanTmps($year);
                            	break;
                            }     	
                   }
                   # verificar sobre escritura de información de año
                   if($status){
                   	 $this->CheckYear($year);
                   	 $status = $this->CleanTmps($year);
                   }
                   $this->conn->execute_single_query("update localizacion l set l.municipio_id = (select id from municipios where lower(l.municipio_sap) = lower(nombre) limit 1)");
            	}

            }

            return $status;
	    }

        # hoja gestión de proyectos
	    public function Proyects($spreadsheet,$index,$year){
	    	$data = array();
	    	$query = "INSERT INTO proyectos_tmp VALUES ";
	    	$query_tmp = "";
	    	$sheet = $spreadsheet->getSheet($index);
	    	$i = 0;
	    	foreach ($sheet->getRowIterator() as $row) {
	    		$j = 0;
	    		// omite la primera fila de la hoja 
	    		if($i >= 1):
		    		$query_tmp = "(NULL,";
		    		foreach ($row->getCellIterator() as $cell):
		    			$data[$i][$j] = $cell->getValue();
	                    # verifica si es la posicion 0 -> dependencia
	                    switch ($j) {
	                    	case 0:
						        $query_tmp .= "(SELECT id FROM dependencias WHERE TRIM(LOWER(dependencia)) = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
						        break;
						    case 1:
						        $query_tmp .= "(SELECT id FROM departamentos WHERE TRIM(LOWER(nombre)) = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
						        break;
						    case 3:
						        $desc = str_replace("'", '"', $cell->getValue());
						        $desc = str_replace(",",'', $desc);
						        $query_tmp .="'".$desc."',";
						        break;
						    case 4:
						        $query_tmp .= (int)$cell->getValue().",";
						        break;
						    default:
						        $query_tmp .="'".$cell->getValue()."',";
						        break;
	                    }
	                    // break en caso de superar 5 columnas 
	                    if($j == 4){
	                    	break;
	                    }
		    			$j++;
		    		endforeach;
		    		$query_tmp .= "'$year'),";
		    		# validar si codigo de proyecto y nombre no son vacios, se concatena con query
		    		if($data[$i][2] != '' && $data[$i][3] != ''){
                        $query .= $query_tmp;
                        $this->rows_affected['proyectos'] = $i;
		    		}else{
		    			# rompe ciclo para evitar recorrer filas no necesarias
		    			break;
		    		}

	    	    endif;
	    		$i++;
	    	}
	    	#borra la ultima coma
	    	$query = substr($query, 0, -1).";";
	    	$this->qs['que_pry_tmp'] = $query;
	    	return $this->conn->execute_single_query($this->qs['que_pry_tmp']);
	    }

        # realiza el tratamiento de datos de la hoja de eje
	    public function Axis($spreadsheet,$index,$year){
	    	$data = array();
	    	$sheet = $spreadsheet->getSheet($index);
	    	$query = "INSERT INTO eje_tmp VALUES ";
	    	$query_tmp = "";
	    	$i = 0;
	    	foreach ($sheet->getRowIterator() as $row) {
	    		$j = 0;
	    		// omite las 2 primeras filas de la hoja 
	    		if($i >= 2):
	    			$query_tmp = "(NULL,";
		    		foreach ($row->getCellIterator() as $cell):
		    			$data[$i][$j] = ($j==30) ? $cell->getCalculatedValue() : ($j==15) ? $cell->getFormattedValue() : $cell->getValue();
                        // validar que solo ingrese en indices necesarios
                        if($j != 1 && $j != 9 && $j < 20):
			    			switch ($j) {
		                    	case 0:
							        $query_tmp .= "(SELECT id FROM dependencias WHERE TRIM(LOWER(dependencia)) = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
							        break;
							    case 2:
							        $query_tmp .= "(SELECT id FROM departamentos WHERE TRIM(LOWER(nombre)) = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
							        break;
							    case 8:
							        $query_tmp .= "(SELECT id FROM proyectos_tmp WHERE CONCAT(codigo_proyecto,'-',departamento_id,'-',dependencia_id) = CONCAT( TRIM(LOWER('".$cell->getValue()."')),'-',(SELECT id FROM departamentos WHERE TRIM(LOWER(nombre)) = TRIM(LOWER('".$data[$i][2]."')) LIMIT 1),'-',(SELECT id FROM dependencias WHERE TRIM(LOWER(dependencia)) = TRIM(LOWER('".$data[$i][0]."')) LIMIT 1)) LIMIT 1),";
							        break;
							    case 12:
							        $desc = str_replace(",",'.', $cell->getValue());
							        $query_tmp .="'".$desc."',";
							        break;
							    case 15:
							        $query_tmp .="'".$cell->getFormattedValue()."',"; // fecha
							        break;
							    case 16:
							        $query_tmp .="'".$cell->getFormattedValue()."',"; // fecha
							        break;
							    case 17:
							        $query_tmp .="'".$cell->getFormattedValue()."',"; // fecha
							        break;    
							    default:
							        $desc = str_replace("'", '"', $cell->getValue());
							        $desc = str_replace(",",'', $desc);
							        $query_tmp .="'".$desc."',";
							        break;
		                    } // cierra swicth
		                endif; // fin validacion de indices de columnas necesarios
		                # validacion de enteros y formulas si la formula no realiza la operacion se coloca 0
		                # de lo contrario se asigna el valor de la formula
		                if($j >= 20 && $j <=30){
		                	#$ttl_str = $cell->getCalculatedValue(); 
		                	$ttl_str = $cell->getValue(); 
							$ttl = ( strrpos($ttl_str, ":") !== false ) ? 0 : trim( str_replace(",",'', $ttl_str) ); 
							$query_tmp .= (int)$ttl.",";
		                }
		    			// break en caso de superar 31 columnas 
	                    if($j == 30){
	                    	break;
	                    }
		    			$j++;
		    		endforeach;
		    		$query_tmp .= "'$year'),";
		    		
		    		# validar que la dependencia no sea null
		    		if($data[$i][0] != '' && $data[$i][0] != 'null' && $data[$i][0] != null){
                        $query .= $query_tmp;
                        $this->rows_affected['eje'] = $i - 1;
		    		}else{
		    			# rompe ciclo para evitar recorrer filas no necesarias
		    			break;
		    		}

	    	    endif; // cierra omitir las dos primeras filas
	    		$i++;
	    	}
	    	#borra la ultima coma
	    	$query = substr($query, 0, -1).";";
	    	$this->qs['que_axio_tmp'] = $query;
	    	return $this->conn->execute_single_query($this->qs['que_axio_tmp']);
	    }

        # Gestiona los datos de la hoja de localización
	    public function Location($spreadsheet,$index,$year){
            $data = array();
	    	$sheet = $spreadsheet->getSheet($index);
	    	$query = "INSERT INTO localizacion_tmp VALUES ";
	    	$query_tmp = "";
	    	$i = 0;
	    	foreach ($sheet->getRowIterator() as $row) {
	    		$j = 0;
	    		// omite la primera fila de la hoja 
	    		if($i >= 1):
	    			$query_tmp = "(NULL,";
		    		foreach ($row->getCellIterator() as $cell):
		    			$data[$i][$j] = $cell->getValue();
		    			// validar que solo ingrese en indices necesarios
                        //if($j != 1 && $j != 9 && $j < 20):
                        /*
                        case 8:
						        $query_tmp .= "(SELECT id FROM municipios WHERE TRIM(LOWER(nombre)) = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
						        break;
                        */
		    			switch ($j) {
	                    	case 0:
						        $query_tmp .= "(SELECT id FROM dependencias WHERE TRIM(LOWER(dependencia)) = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
						        break;
						    case 2:
						        $query_tmp .= "(SELECT id FROM proyectos_tmp WHERE CONCAT(codigo_proyecto,'-',dependencia_id) = CONCAT( TRIM(LOWER('".$data[$i][1]."')),'-',(SELECT id FROM dependencias WHERE TRIM(LOWER(dependencia)) = TRIM(LOWER('".$data[$i][0]."')) LIMIT 1)) LIMIT 1),";
						        break;
						    case 3:
						        $query_tmp .= "(SELECT id FROM paises WHERE alias = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
						        break;
						    case 4:
						        $query_tmp .= "(SELECT id FROM departamentos WHERE TRIM(LOWER(nombre)) = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
						        break;
						    case 7:
						        $desc = str_replace("'", '"', $cell->getValue());
						        $desc = str_replace(",",'', $desc);
						        $query_tmp .="'".trim($desc)."',NULL,";
						        break;
						    case 5:
						        $query_tmp .= (int)$cell->getValue().",";
						        break;
						    default:
						        $desc = str_replace("'", '"', $cell->getValue());
						        $desc = str_replace(",",'', $desc);
						        $query_tmp .="'".trim($desc)."',";
						        break;
	                    }
	                    //}
		    			// break en caso de superar 10 columnas 
	                    if($j == 8){
	                    	break;
	                    }
		    			$j++;
		    		endforeach;
		    		$query_tmp .= "'$year'),";
		    		# validar si codigo de proyecto y nombre no son vacios, se concatena con query
		    		if($data[$i][1] != '' && $data[$i][1] != 'null' && $data[$i][1] != null){
                        $query .= $query_tmp;
                        $this->rows_affected['localizacion'] = $i;
		    		}else{
		    			# rompe ciclo para evitar recorrer filas no necesarias
		    			break;
		    		}

	    		endif; // cierra omitir la primera fila de la hoja
	    		$i++;
	    	}
	    	#borra la ultima coma
	    	$query = substr($query, 0, -1).";";
	    	$this->qs['que_loc_tmp'] = $query;

            return $this->conn->execute_single_query($this->qs['que_loc_tmp']);
	    }

        # Gestión de hoja beneficiarios
	    public function Beneficiaries($spreadsheet,$index,$year){
                $data = array();
		    	$sheet = $spreadsheet->getSheet($index);
		    	$query = "INSERT INTO beneficiarios_tmp VALUES ";
		    	$query_tmp = "";
		    	$i = 0;
		    	foreach ($sheet->getRowIterator() as $row) {
		    		$j = 0;
		    		// omite la primera fila de la hoja 
		    		if($i >= 1):
		    			$query_tmp = "(NULL,";
			    		foreach ($row->getCellIterator() as $cell):
			    			$data[$i][$j] = $cell->getValue();
			    			// validar que solo ingrese en indices necesarios
	                        if( $j < 5 || $j > 15 ):
					    			switch ($j) {
				                    	case 0:
									        $query_tmp .= "(SELECT id FROM dependencias WHERE TRIM(LOWER(dependencia)) = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
									        break;
									    case 2:
									        $query_tmp .= "(SELECT id FROM proyectos_tmp WHERE CONCAT(codigo_proyecto,'-',dependencia_id) = CONCAT( TRIM(LOWER('".$data[$i][1]."')),'-',(SELECT id FROM dependencias WHERE TRIM(LOWER(dependencia)) = TRIM(LOWER('".$data[$i][0]."')) LIMIT 1)) LIMIT 1),";
									        break;
									    case 3:
									        $query_tmp .= "(SELECT id FROM paises WHERE alias = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
									        break;
									    case 4:
									        $query_tmp .= "(SELECT id FROM departamentos WHERE TRIM(LOWER(nombre)) = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
									        break;
									    default:
									        $desc = str_replace("'", '"', $cell->getValue());
									        $desc = str_replace(",",'', $desc);
									        $query_tmp .="'".trim($desc)."',";
									        break;
				                    }
		                    endif;
		                    # validacion de enteros y formulas si la formula no realiza la operacion se coloca 0
			                # de lo contrario se asigna el valor de la formula
			                if( $j >= 5 && $j <=15 && $j != 7){
			                	#$ttl_str = $cell->getCalculatedValue(); 
			                	$ttl_str = $cell->getValue(); 
								$ttl = ( strrpos($ttl_str, ":") !== false ) ? 0 : trim( str_replace(",",'', $ttl_str) ); 
								$query_tmp .= (int)$ttl.",";
			                }
			    			// break en caso de superar 10 columnas 
		                    if($j == 16){
		                    	break;
		                    }
			    			$j++;
			    		endforeach;
			    		$query_tmp .= "'$year'),";
			    		# validar si codigo de proyecto y nombre no son vacios, se concatena con query
			    		if($data[$i][1] != '' && $data[$i][1] != 'null' && $data[$i][1] != null){
	                        $query .= $query_tmp;
	                        $this->rows_affected['beneficiarios'] = $i;
			    		}else{
			    			# rompe ciclo para evitar recorrer filas no necesarias
			    			break;
			    		}

		    		endif; // cierra omitir la primera fila de la hoja
		    		$i++;
		    	}
		    	#borra la ultima coma
		    	$query = substr($query, 0, -1).";";
		    	$this->qs['que_bene_tmp'] = $query;
		    	return $this->conn->execute_single_query($this->qs['que_bene_tmp']);
	    }
        
        # Gestión de hoja de indicadores
        public function Indicators($spreadsheet,$index,$year){
                $data = array();
		    	$sheet = $spreadsheet->getSheet($index);
		    	$query = "INSERT INTO indicadores_tmp VALUES ";
		    	$query_tmp = "";
		    	$i = 0;
		    	foreach ($sheet->getRowIterator() as $row) {
		    		$j = 0;
		    		// omite la primera fila de la hoja 
		    		if($i >= 1):
		    			$query_tmp = "(NULL,";
			    		foreach ($row->getCellIterator() as $cell):
			    			$data[$i][$j] = $cell->getValue();
			    			// validar que solo ingrese en indices necesarios
			    			switch ($j) {
		                    	case 0:
							        $query_tmp .= "(SELECT id FROM dependencias WHERE TRIM(LOWER(dependencia)) = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
							        break;
							    case 2:
							        $query_tmp .= "(SELECT id FROM proyectos_tmp WHERE CONCAT(codigo_proyecto,'-',dependencia_id) = CONCAT( TRIM(LOWER('".$data[$i][1]."')),'-',(SELECT id FROM dependencias WHERE TRIM(LOWER(dependencia)) = TRIM(LOWER('".$data[$i][0]."')) LIMIT 1)) LIMIT 1),";
							        break;
							    case 3:
							        $query_tmp .= "(SELECT id FROM paises WHERE alias = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
							        break;
							    case 4:
							        $query_tmp .= "(SELECT id FROM departamentos WHERE TRIM(LOWER(nombre)) = TRIM(LOWER('".$cell->getValue()."')) LIMIT 1),";
							        break;
							    case 5:
							        $ttl_str = $cell->getValue(); 
								    $ttl = ( strrpos($ttl_str, "/") !== false || $ttl_str == 'null' || $ttl_str == null || !is_numeric($ttl_str) ) ? 0 : $ttl_str; 
								    $query_tmp .= $ttl.",";
							        break;
							    default:
							        $desc = str_replace("'", '"', $cell->getValue());
							        $desc = str_replace(",",'', $desc);
							        $query_tmp .="'".trim($desc)."',";
							        break;
		                    }
			    			// break en caso de superar 8 columnas 
		                    if($j == 7){
		                    	break;
		                    }
			    			$j++;
			    		endforeach;
			    		$query_tmp .= "'$year'),";
			    		# validar si codigo de proyecto y nombre no son vacios, se concatena con query
			    		if($data[$i][1] != '' && $data[$i][1] != 'null' && $data[$i][1] != null){
	                        $query .= $query_tmp;
	                        $this->rows_affected['indicadores'] = $i;
			    		}else{
			    			# rompe ciclo para evitar recorrer filas no necesarias
			    			break;
			    		}

		    		endif; // cierra omitir la primera fila de la hoja
		    		$i++;
		    	}
		    	#borra la ultima coma
		    	$query = substr($query, 0, -1).";";
		    	$this->qs['que_indic_tmp'] = $query;
		    	return $this->conn->execute_single_query($this->qs['que_indic_tmp']);
        }
        
        # Verifica si la información del año existe la sobre escribe
        # Con la tabla temporal luego borra datos
        public function CheckYear($year){
           $tables = array('indicadores','beneficiarios','localizacion','eje','proyectos');
           $status = true;
           # eliminacion de registros del año seleccionado
           $this->conn->begin_trans();
           #iniciar la tx
           foreach ($tables as $tbl_d) {
           	 $this->qs['del_'.$tbl_d.'_tmp'] = "DELETE FROM $tbl_d WHERE ano_carge = '$year';";
           	 $this->conn->execute_single_query_readycon($this->qs['del_'.$tbl_d.'_tmp']);
           }

           $tables = array('proyectos','eje','localizacion','beneficiarios','indicadores');
           $columns = array('eje'=>" NULL,dependencia_id,departamento_id,definicion_proyecto,id_post,eje,vector,segmento,proyecto_id,gestor_proyecto,elemento_pep,asig_portafolio,perfil_proyecto,denominacion_perfil,fecha_creacion,fecha_inicio,fecha_fin,status,denominacion_status,com,mun,gob,org_nal_pri,org_nal_pub,inter,fonc,rp,esp_real_com,esp_real_ter,total_ejecucion,ano_carge ",
                'localizacion'=>" NULL,dependencia_id,codigo_proyecto_sap,proyecto_id,pais_id,departamento_id,vr_ejecutivo,municipio_sap,cobertura,municipio_id,observacion,ano_carge ",
                'beneficiarios'=>" NULL,dependencia_id,cod_proyecto_sap,proyecto_id,pais_id,departamento_id,caficultores,otro_beneficiarios,hombre,mujeres,jovenes,niños,indigenas,afrodecendiente,otro,total_beneficiario,observacion,ano_carge ",
                'indicadores'=>" NULL,dependencia_id,cod_proyecto_sap,proyecto_id,pais_id,departamento_id,indicador_nu,indicador,observacion,ano_carge ");
           # insertar nuevos registros de tablas temporales a tablas principales
           foreach ($tables as $tbl) {
             $pry_columns = ($tbl == "proyectos") ? "*" : $columns[$tbl];
           	 $this->qs['add_'.$tbl.'_tmp'] = "INSERT INTO $tbl SELECT $pry_columns FROM ".$tbl."_tmp WHERE ano_carge = '$year';";
		     $status = $status && $this->conn->execute_single_query_readycon($this->qs['add_'.$tbl.'_tmp']);
		     if(!$status){
                $this->error_upload = "Problemas al cargar datos en la tabla $tbl error: ".$this->conn->info_error();
                break;
             }    
           }
           # validar tx
           if($status){
           	 $this->conn->commit();
           }else{
           	 $this->conn->rollback();
           }
           return $status;
        }

        # limpiar tablas temporales
        public function CleanTmps($year){
          $status = true;
          $tables = array('indicadores_tmp','beneficiarios_tmp','localizacion_tmp','eje_tmp','proyectos_tmp');
          foreach ($tables as $tbl) {
          	 $this->qs['trunc_'.$tbl.'_tmp'] = "DELETE FROM ".$tbl." WHERE ano_carge = '$year';";
		     $status = $status && $this->conn->execute_single_query($this->qs['trunc_'.$tbl.'_tmp']);
          }
          return $status;
        }

        # Obtener error de carga
        public function GetError(){
        	return $this->error_upload;
        }
        
        # retornar arreglo de registros afectados
        public function GetRowsAffeted(){
        	return $this->rows_affected;
        }

	    # recibe el token genera la validacion si es valido
        # retorna el nuevo token si no es correcto emite que esta en DISTRUST
        # quiere decir en desconfianza
        public function verifyToken($token) {
          return $this->conn->KeyGenerate($token);
        }

	} # Cierre de clase
?>