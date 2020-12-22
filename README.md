# README #

Este README explica la forma de implementar el proyecto y las variables de configuración
* Se requiere php 7.3

### Agregar archivo para conexión con base de datos ###

* Core/ConfigDB.php

Estructura contenido del archivo.

   `class ConfDB{
   	    public $CONF_DB = array(
   	              'usr_db'=>'XXXXX',
   	              'name_db'=>'XXXX',
   	              'passwd_db'=>'XXXXX',
   	              'host_db'=>'XXXXXXX'
                  );
    `

### Instalación de las librerias necesarias ###

* Dirigirse a la carpeta del proyecto "Core/" y por consola ejecutar el siguiente comando  

`composer install --no-dev`


### Carpeta frontend ###

* Dirigirse al archivo "front/index.html"  
"# sustain_map" 
