<?php
/*________________________________________________________________________________________________________
	La clave de configuración está formada por una serie de valores separados por ";" 
		Ejemplo:1;7;30000000;3;3;0;11
		
		Parámetros:
			1) Número de partición
			2) Código de la partición
			3) Tamaño
			4) Identificador del sistema de ficheros instalado en la partición
			5) Identificador del nombre del sistema operativo instalado en la partición
			6) Identificador de la imagen restaurada en la partición
			7) Identificador del perfil software que contiene el S.O. instalado en la partición
			
		Además de este campo, la consulta almacena la descripción de los identificadores que forman parte
		 de esta clave compuesta de manera que el tiempo de acceso para recuperlarlos sean corto
		 ya que están en memoria y no en tablas.
		  
		En el ejempo anterior podríamos tener datos	
			1 	 NTFS 	30000000 	Windows NTFS 	Windows XP profesional  	NULL 	Perfil Software (CUR-8, Part:1) 
		Que indica:
			1) Número de partición
			2) Código de la partición
			3) Tamaño
			4) Descripción del sistema de ficheros instalado en la partición
			5) Descripción del nombre del sistema operativo instalado en la partición
			6) Descripción de la imagen restaurada en la partición
			7) Descripción del perfil software que contiene el S.O. instalado en la partición
			
			Estos datos se guardan en la misma tabla de claves que será una matriz asociativa.
			
			Parámetros de la función:
				$cmd: Objeto comando (Operativo)
				$idambito: Identificador del ámbito (identificador del Aula, grupo de ordenador u ordenador)			
				$ambito: Tipo de ambito (Aulas, grupos de ordenadores u ordenadores)
				$sws: Switchs que indican el nivel de agrupamiento de los ordenadores para ser tratados
							Se trata de un octeto de manera que si tiene un "1" en la posición determinada
							indica que se requiere desplegar por ese parámetro:
								00000001- No agrupar por Sistema de ficheros
								00000010- No agrupar por Nombre de sistema Operativo
								00000100- No agrupar por Tamaño de partición
								00001000- No agrupar por Imagen instalada
								00010000- No agrupar por Perfil software contenido
				$swr: Indica  si se se tiene en cuenta las particiones no clonables (si:true o no:false)	
________________________________________________________________________________________________________*/
function cargaCaves($cmd,$idambito,$ambito,$sws,$swr)
{
	global $tbKeys; // Tabla contenedora de claves de configuración
	global $conKeys; // Contador de claves de configuración
	global $AMBITO_AULAS;
	global $AMBITO_GRUPOSORDENADORES;
	global $AMBITO_ORDENADORES;
	
	global $msk_sysFi;
	global $msk_nombreSO;
	global $msk_tamano;
	global $msk_imagen;
	global $msk_perfil;	
				
	$cmd->texto="SELECT 			
				concat_WS( ';', 
							ordenadores_particiones.numpar, ";
							
	if($sws & $msk_tamano)						
		$cmd->texto.="	ordenadores_particiones.tamano,";
	

	if($sws & $msk_sysFi)						
		$cmd->texto.="	ordenadores_particiones.idsistemafichero, ";	
		
	if($sws & $msk_nombreSO)						
		$cmd->texto.="	ordenadores_particiones.idnombreso, ";								
							
	if($sws & $msk_imagen)						
		$cmd->texto.="	ordenadores_particiones.idimagen, ";	
		
	if($sws & $msk_perfil)						
		$cmd->texto.="	ordenadores_particiones.idperfilsoft, ";										

	$cmd->texto.=" ordenadores_particiones.codpar) as configuracion,	
				ordenadores_particiones.numpar ,
				ordenadores_particiones.codpar ,				
				tipospar.tipopar,
				tipospar.clonable,
				ordenadores_particiones.tamano,
				sistemasficheros.descripcion as sistemafichero,
				ordenadores_particiones.idnombreso,
				nombresos.nombreso,
				imagenes.idimagen, 
				imagenes.descripcion as imagen,
				imagenes.nombreca as nombreca,				
				imagenes.idrepositorio as repositorio,				
				ordenadores_particiones.idperfilsoft,
				perfilessoft.descripcion as perfilsoft

				FROM ordenadores
					INNER JOIN ordenadores_particiones ON ordenadores_particiones.idordenador=ordenadores.idordenador
					LEFT OUTER JOIN nombresos ON nombresos.idnombreso=ordenadores_particiones.idnombreso
					INNER JOIN tipospar ON tipospar.codpar=ordenadores_particiones.codpar
					LEFT OUTER JOIN imagenes ON imagenes.idimagen=ordenadores_particiones.idimagen
					LEFT OUTER JOIN perfilessoft ON perfilessoft.idperfilsoft=ordenadores_particiones.idperfilsoft
					LEFT OUTER JOIN sistemasficheros ON sistemasficheros.idsistemafichero=ordenadores_particiones.idsistemafichero";
					
	switch($ambito){
			case $AMBITO_AULAS :
					$cmd->texto.=" INNER JOIN aulas ON aulas.idaula = ordenadores.idaula
													WHERE aulas.idaula =".$idambito;
					break;
			case $AMBITO_GRUPOSORDENADORES :
					$cmd->texto.=" INNER JOIN gruposordenadores ON gruposordenadores.idgrupo = ordenadores.grupoid
													WHERE gruposordenadores.idgrupo =".$idambito;
					break;
			case $AMBITO_ORDENADORES :
					$cmd->texto.=" WHERE ordenadores.idordenador =".$idambito;
				break;
	}		
	if($swr) // Si se trata de restauración no se tiene en cuenta las partciones no clonables
		$cmd->texto.=" AND tipospar.clonable=1 ";
					
	$cmd->texto.=" GROUP by configuracion";
	//echo "carga claves:".$cmd->texto;
	$rs=new Recordset; 
	$rs->Comando=&$cmd; 
	if (!$rs->Abrir()) return($tablaHtml); // Error al abrir recordset
	$rs->Primero();
	$idx=0; 
	//echo $cmd->texto;
	while (!$rs->EOF){
			$tbKeys[$idx]["cfg"]=$rs->campos["configuracion"];
			$tbKeys[$idx]["numpar"]=$rs->campos["numpar"];
			$tbKeys[$idx]["codpar"]=$rs->campos["codpar"];			
			$tbKeys[$idx]["tipopar"]=$rs->campos["tipopar"];
			$tbKeys[$idx]["clonable"]=$rs->campos["clonable"];			
			$tbKeys[$idx]["tamano"]=$rs->campos["tamano"];
			$tbKeys[$idx]["sistemafichero"]=$rs->campos["sistemafichero"];
			$tbKeys[$idx]["idnombreso"]=$rs->campos["idnombreso"];
			$tbKeys[$idx]["nombreso"]=$rs->campos["nombreso"];			
			$tbKeys[$idx]["idimagen"]=$rs->campos["idimagen"];
			$tbKeys[$idx]["imagen"]=$rs->campos["imagen"];
			$tbKeys[$idx]["nombreca"]=$rs->campos["nombreca"];
			$tbKeys[$idx]["repositorio"]=$rs->campos["repositorio"];
			$tbKeys[$idx]["idperfilsoft"]=$rs->campos["idperfilsoft"];
			$tbKeys[$idx]["perfilsoft"]=$rs->campos["perfilsoft"];			
			$idx++;
		$rs->Siguiente();
	}
	$conKeys=$idx; // Guarda contador
	$rs->Cerrar();
}
/*________________________________________________________________________________________________________
			Dibuja la tabla de configuración de las particiones de un grupo de ordenadores
			
			Parámetros de la función:
				$cmd: Objeto comando (Operativo)
				$idambito: Identificador del ámbito (identificador del Aula, grupo de ordenador u ordenador)			
				$ambito: Tipo de ambito (Aulas, grupos de ordenadores u ordenadores)
				$sws: Switchs que indican el nivel de agrupamiento (ver comentarios de la función(cargaCaves)
				$swr: Indica  si se se tiene en cuenta las particiones no clonables (true:sólo conables , false:todas)
				
			Especificaciones:
				Esta función llama a pintaParticiones() que es realmente la encargada de mostrar o bien la
				configuración de los ordenadores o la pantalla de los comandos "Configurar" o "RestaurarImagen" 
				para permitir introducir los	datos necesarios.		
________________________________________________________________________________________________________*/
function pintaConfiguraciones($cmd,$idambito,$ambito,$colums,$sws,$swr)
{
	global $AMBITO_AULAS;
	global $AMBITO_GRUPOSORDENADORES;
	global $AMBITO_ORDENADORES;

	global $msk_sysFi;
	global $msk_nombreSO;
	global $msk_tamano;
	global $msk_imagen;
	global $msk_perfil;	
		
	cargaCaves($cmd,$idambito,$ambito,$sws,$swr);
	cargaSistemasFicheros($cmd,$idambito,$ambito);
	cargaPerfiles($cmd,$idambito,$ambito);
	cargaImagenes($cmd,$idambito,$ambito);
	cargaNombresSO($cmd,$idambito,$ambito);
	cargaTamano($cmd,$idambito,$ambito);
	
	$cmd->texto="SELECT 
							count(*) as con,
							group_concat(cast( temp2.idordenador AS char( 11 ) )  ORDER BY temp2.idordenador SEPARATOR ',' ) AS idordenadores,
							temp2.configuraciones
							FROM
							(SELECT 
								temp1.idordenador as idordenador,
								group_concat(cast( temp1.configuracion AS char( 250) )  ORDER BY temp1.configuracion SEPARATOR '@' ) AS configuraciones
								FROM
								(SELECT ordenadores_particiones.idordenador,
											ordenadores_particiones.numpar,
											concat_WS( ';', 
															ordenadores_particiones.numpar, ";
															
	if($sws & $msk_tamano)						
		$cmd->texto.="	ordenadores_particiones.tamano,";
				
	if($sws & $msk_sysFi)						
		$cmd->texto.="	ordenadores_particiones.idsistemafichero, ";	
		
	if($sws & $msk_nombreSO)						
		$cmd->texto.="	ordenadores_particiones.idnombreso, ";								
							
	if($sws & $msk_imagen)						
		$cmd->texto.="	ordenadores_particiones.idimagen, ";	
		
	if($sws & $msk_perfil)						
		$cmd->texto.="	ordenadores_particiones.idperfilsoft, ";																
			
	$cmd->texto.=" ordenadores_particiones.codpar) as configuracion
											FROM ordenadores
														INNER JOIN ordenadores_particiones ON ordenadores_particiones.idordenador=ordenadores.idordenador
														LEFT OUTER JOIN nombresos ON nombresos.idnombreso=ordenadores_particiones.idnombreso
														INNER JOIN tipospar ON tipospar.codpar=ordenadores_particiones.codpar
														LEFT OUTER JOIN imagenes ON imagenes.idimagen=ordenadores_particiones.idimagen
														LEFT OUTER JOIN perfilessoft ON perfilessoft.idperfilsoft=ordenadores_particiones.idperfilsoft
														LEFT OUTER JOIN sistemasficheros ON sistemasficheros.idsistemafichero=ordenadores_particiones.idsistemafichero";
														
	switch($ambito){
		case $AMBITO_AULAS :
				$cmd->texto.=" INNER JOIN aulas ON aulas.idaula = ordenadores.idaula
												WHERE aulas.idaula =".$idambito;
		break;
		case $AMBITO_GRUPOSORDENADORES :
					$cmd->texto.=" INNER JOIN gruposordenadores ON gruposordenadores.idgrupo = ordenadores.grupoid
													WHERE gruposordenadores.idgrupo =".$idambito;
			break;
		case $AMBITO_ORDENADORES :
				$cmd->texto.=" WHERE ordenadores.idordenador=".$idambito;
			break;
	}															
	if($swr) // Si se trata de restauración no se tiene en cuenta las particiones no clonables
		$cmd->texto.=" AND tipospar.clonable=1 ";
	
	$cmd->texto.=" ORDER BY ordenadores_particiones.idordenador,ordenadores_particiones.numpar) as temp1
					GROUP BY temp1.idordenador) as temp2
					GROUP BY temp2.configuraciones
					ORDER BY con desc,idordenadores";
								
	//echo 	$cmd->texto;
	$rs=new Recordset; 
	$rs->Comando=&$cmd; 
	if (!$rs->Abrir()) return; // Error al abrir recordset
	$rs->Primero();
	$cc=0; // Contador de configuraciones
	echo '<TABLE  id="tabla_conf" width="95%" class="tabla_listados_sin" align=center border=0 cellPadding=0 cellSpacing=1>';
	while (!$rs->EOF){
		$cc++;
		//Muestra ordenadores
		echo '<TR><TD colspan='.$colums.' style="BACKGROUND-COLOR: #FFFFFF;">';
		echo pintaOrdenadores($cmd,$rs->campos["idordenadores"],10,$cc);
		echo '</TD></TR>';
		//Muestra particiones y configuración

		echo pintaParticiones($cmd,$rs->campos["configuraciones"],$rs->campos["idordenadores"],$cc);
		$rs->Siguiente();
	}
	echo "</TABLE>";
	$rs->Cerrar();
}
//________________________________________________________________________________________________________
//	Descripción:
//		Muestra una taba html con el icono de ordenadores
//	Parametros:
//		$cmd: Objeto comando (operativo)		
//		$idordenadores: Cadena con los identificadores de los ordenadores separados por ","
//		$maxcontor: Número máximo de ordenadores por fila
//		$cc: Identificador del bloque de configuración
//________________________________________________________________________________________________________
function pintaOrdenadores($cmd,$idordenadores,$maxcontor,$cc)
{
	$tablaHtml.="";
	$contor=0;
	$maxcontor=10; // Número máximo de prodenadores por fila
	$cmd->texto=" SELECT idordenador,nombreordenador,ip FROM ordenadores WHERE  idordenador in (".$idordenadores." ) ORDER BY nombreordenador";
	$rs=new Recordset; 
	$rs->Comando=&$cmd; 
	if (!$rs->Abrir()) return(""); // Error al abrir recordset
	$tablaHtml.='<TABLE align=left border="0" id="tbOrd_'.$cc.'" value="'.$idordenadores.'"><TR>';
	while (!$rs->EOF){
		$contor++;
		$tablaHtml.= '<TD align=center  style="BACKGROUND-COLOR: #FFFFFF;">
									<IMG src="../images/iconos/ordenador.gif" >
									<br><span style="FONT-SIZE:9px;	COLOR: #4f4f4f;" >'.$rs->campos["nombreordenador"].'</span>
								</TD>';
		if($contor>$maxcontor){
			$contor=0;
			$tablaHtml.='</TR><TR>';
		}
		$rs->Siguiente();
	}
	$tablaHtml.='</TR>';
	$tablaHtml.= '</TABLE>';
	return($tablaHtml);
}
/*________________________________________________________________________________________________________
	
	Selecciona los ordenadores que tienen el mismo sistema de ficheros del ámbito elegido
________________________________________________________________________________________________________*/
function cargaSistemasFicheros($cmd,$idambito,$ambito)
{
	global $tbSysFi; // Tabla contenedora de ordenadores incluidos en la consulta
	global $conSysFi; // Contador de elementos anteriores
	global $AMBITO_AULAS;
	global $AMBITO_GRUPOSORDENADORES;
	global $AMBITO_ORDENADORES;
	
	$cmd->texto="SELECT count(*) as con,ordenadores_particiones.idsistemafichero,ordenadores_particiones.numpar,
											sistemasficheros.descripcion as sistemafichero,
											group_concat(cast(ordenadores_particiones.idordenador AS char( 11) ) 
											 ORDER BY ordenadores_particiones.idordenador SEPARATOR ',' ) AS ordenadores
							FROM	ordenadores
							INNER JOIN ordenadores_particiones ON ordenadores_particiones.idordenador=ordenadores.idordenador
							INNER JOIN sistemasficheros ON sistemasficheros.idsistemafichero=ordenadores_particiones.idsistemafichero";

	switch($ambito){
		case $AMBITO_AULAS :
				$cmd->texto.=" INNER JOIN aulas ON aulas.idaula = ordenadores.idaula
												WHERE aulas.idaula =".$idambito;
				break;
		case $AMBITO_GRUPOSORDENADORES :
				$cmd->texto.=" INNER JOIN gruposordenadores ON gruposordenadores.idgrupo = ordenadores.grupoid
												WHERE gruposordenadores.idgrupo =".$idambito;
			break;
		case $AMBITO_ORDENADORES :
				$cmd->texto.=" WHERE ordenadores.idordenador =".$idambito;
			break;
	}	
		$cmd->texto.=" GROUP BY ordenadores_particiones.numpar,ordenadores_particiones.idsistemafichero";
	//echo "carga sistemas de ficheros:".$cmd->texto;
	$rs=new Recordset; 
	$rs->Comando=&$cmd; 
	if (!$rs->Abrir()) return; // Error al abrir recordset
	$rs->Primero();
	$idx=0; 
	//echo $cmd->texto;
	while (!$rs->EOF){
			$tbSysFi[$idx]["idsistemafichero"]=$rs->campos["idsistemafichero"];
			$tbSysFi[$idx]["numpar"]=$rs->campos["numpar"];			
			$tbSysFi[$idx]["sistemafichero"]=$rs->campos["sistemafichero"];
			$tbSysFi[$idx]["ordenadores"]=$rs->campos["ordenadores"];			
			$idx++;
		$rs->Siguiente();
	}
	$conSysFi=$idx; // Guarda contador
	$rs->Cerrar();
}
/*________________________________________________________________________________________________________
	
	Toma sistema de ficheros común a los ordenadores pasados como parámetros
________________________________________________________________________________________________________*/
function tomaSistemasFicheros($numpar,$ordenadores,$sw=false)
{
	global $tbSysFi; // Tabla contenedora de ordenadores incluidos en la consulta
	global $conSysFi; // Contador de elementos anteriores

	for($k=0;$k<$conSysFi;$k++){
		$pos = strpos($tbSysFi[$k]["ordenadores"], $ordenadores);
		if ($pos !== false) { // Cadena encontrada
			if($tbSysFi[$k]["numpar"]==$numpar){
				if($sw) // Retonar identificador
					return($tbSysFi[$k]["idsistemafichero"]);
				else
					return($tbSysFi[$k]["sistemafichero"]);
			}
		}
	}
}
/*________________________________________________________________________________________________________
	
	Selecciona los ordenadores que tienen el mismo perfil software en la misma partición
________________________________________________________________________________________________________*/
function cargaPerfiles($cmd,$idambito,$ambito)
{
	global $tbPerfil; // Tabla contenedora de ordenadores incluidos en la consulta
	global $conPerfil; // Contador de elementos anteriores
	global $AMBITO_AULAS;
	global $AMBITO_GRUPOSORDENADORES;
	global $AMBITO_ORDENADORES;
	
	$cmd->texto="SELECT count(*) as con,ordenadores_particiones.idperfilsoft,ordenadores_particiones.numpar,perfilessoft.descripcion as perfilsoft,
											group_concat(cast(ordenadores_particiones.idordenador AS char( 11) ) 
											 ORDER BY ordenadores_particiones.idordenador SEPARATOR ',' ) AS ordenadores
							FROM	ordenadores
							INNER JOIN ordenadores_particiones ON ordenadores_particiones.idordenador=ordenadores.idordenador
							INNER JOIN perfilessoft ON perfilessoft.idperfilsoft=ordenadores_particiones.idperfilsoft";

	switch($ambito){
		case $AMBITO_AULAS :
				$cmd->texto.=" INNER JOIN aulas ON aulas.idaula = ordenadores.idaula
												WHERE aulas.idaula =".$idambito;
				break;
		case $AMBITO_GRUPOSORDENADORES :
				$cmd->texto.=" INNER JOIN gruposordenadores ON gruposordenadores.idgrupo = ordenadores.grupoid
												WHERE gruposordenadores.idgrupo =".$idambito;
			break;
		case $AMBITO_ORDENADORES :
				$cmd->texto.=" WHERE ordenadores.idordenador =".$idambito;
			break;
	}	
		$cmd->texto.=" GROUP BY ordenadores_particiones.numpar,ordenadores_particiones.idperfilsoft";
	//echo "carga perfiles:".$cmd->texto;
	$rs=new Recordset; 
	$rs->Comando=&$cmd; 
	if (!$rs->Abrir()) return; // Error al abrir recordset
	$rs->Primero();
	$idx=0; 
	while (!$rs->EOF){
			$tbPerfil[$idx]["idperfilsoft"]=$rs->campos["idperfilsoft"];
			$tbPerfil[$idx]["perfilsoft"]=$rs->campos["perfilsoft"];
			$tbPerfil[$idx]["numpar"]=$rs->campos["numpar"];					
			$tbPerfil[$idx]["ordenadores"]=$rs->campos["ordenadores"];			
			$idx++;
		$rs->Siguiente();
	}
	$conPerfil=$idx; // Guarda contador
	$rs->Cerrar();
}
/*________________________________________________________________________________________________________
	
		Toma perfilsoft común a los ordenadores pasados como parámetros
________________________________________________________________________________________________________*/
function tomaPerfiles($numpar,$ordenadores)
{
	global $tbPerfil; // Tabla contenedora de ordenadores incluidos en la consulta
	global $conPerfil; // Contador de elementos anteriores

	for($k=0;$k<$conPerfil;$k++){
		$pos = strpos($tbPerfil[$k]["ordenadores"], $ordenadores);
		if ($pos !== false) { // Cadena encontrada
			if($tbPerfil[$k]["numpar"]==$numpar)
				return($tbPerfil[$k]["perfilsoft"]);
		}
	}
}
/*________________________________________________________________________________________________________
	
	Selecciona los ordenadores que tienen la misma imagen en la misma partición
________________________________________________________________________________________________________*/
function cargaImagenes($cmd,$idambito,$ambito)
{
	global $tbImg; // Tabla contenedora de ordenadores incluidos en la consulta
	global $conImg; // Contador de elementos anteriores
	global $AMBITO_AULAS;
	global $AMBITO_GRUPOSORDENADORES;
	global $AMBITO_ORDENADORES;
	
	$cmd->texto="SELECT count(*) as con,ordenadores_particiones.idimagen,ordenadores_particiones.numpar,imagenes.descripcion as imagen,
											group_concat(cast(ordenadores_particiones.idordenador AS char( 11) ) 
											 ORDER BY ordenadores_particiones.idordenador SEPARATOR ',' ) AS ordenadores
							FROM	ordenadores
							INNER JOIN ordenadores_particiones ON ordenadores_particiones.idordenador=ordenadores.idordenador
							INNER JOIN imagenes ON imagenes.idimagen=ordenadores_particiones.idimagen";

	switch($ambito){
		case $AMBITO_AULAS :
				$cmd->texto.=" INNER JOIN aulas ON aulas.idaula = ordenadores.idaula
												WHERE aulas.idaula =".$idambito;
				break;
		case $AMBITO_GRUPOSORDENADORES :
				$cmd->texto.=" INNER JOIN gruposordenadores ON gruposordenadores.idgrupo = ordenadores.grupoid
												WHERE gruposordenadores.idgrupo =".$idambito;
			break;
		case $AMBITO_ORDENADORES :
				$cmd->texto.=" WHERE ordenadores.idordenador =".$idambito;
			break;
	}	
		$cmd->texto.=" GROUP BY ordenadores_particiones.numpar,ordenadores_particiones.idimagen";
	//echo "carga imagenes:".$cmd->texto;
	$rs=new Recordset; 
	$rs->Comando=&$cmd; 
	if (!$rs->Abrir()) return; // Error al abrir recordset
	$rs->Primero();
	$idx=0; 
	while (!$rs->EOF){
			$tbImg[$idx]["idimagen"]=$rs->campos["idimagen"];
			$tbImg[$idx]["imagen"]=$rs->campos["imagen"];
			$tbImg[$idx]["numpar"]=$rs->campos["numpar"];			
			$tbImg[$idx]["ordenadores"]=$rs->campos["ordenadores"];			
			$idx++;
		$rs->Siguiente();
	}
	$conImg=$idx; // Guarda contador
	$rs->Cerrar();
}
/*________________________________________________________________________________________________________
	
		Toma sistema operativo común a los ordenadores pasados como parámetros
________________________________________________________________________________________________________*/
function tomaImagenes($numpar,$ordenadores)
{
	global $tbImg; // Tabla contenedora de ordenadores incluidos en la consulta
	global $conImg; // Contador de elementos anteriores

	for($k=0;$k<$conImg;$k++){
		$pos = strpos($tbImg[$k]["ordenadores"], $ordenadores);
		if ($pos !== false) { // Cadena encontrada
			if($tbImg[$k]["numpar"]==$numpar)
				return($tbImg[$k]["imagen"]);
		}
	}
}
/*________________________________________________________________________________________________________
	
	Selecciona los ordenadores que tienen el mismo sistema de ficheros en la misma partición
________________________________________________________________________________________________________*/
function cargaNombresSO($cmd,$idambito,$ambito)
{
	global $tbSO; // Tabla contenedora de ordenadores incluidos en la consulta
	global $conSO; // Contador de elementos anteriores
	global $AMBITO_AULAS;
	global $AMBITO_GRUPOSORDENADORES;
	global $AMBITO_ORDENADORES;
	
	$cmd->texto="SELECT count(*) as con,ordenadores_particiones.idnombreso,ordenadores_particiones.numpar,nombresos.nombreso,
											group_concat(cast(ordenadores_particiones.idordenador AS char( 11) ) 
											 ORDER BY ordenadores_particiones.idordenador SEPARATOR ',' ) AS ordenadores
							FROM	ordenadores
							INNER JOIN ordenadores_particiones ON ordenadores_particiones.idordenador=ordenadores.idordenador
							INNER JOIN nombresos ON nombresos.idnombreso=ordenadores_particiones.idnombreso";

	switch($ambito){
		case $AMBITO_AULAS :
				$cmd->texto.=" INNER JOIN aulas ON aulas.idaula = ordenadores.idaula
												WHERE aulas.idaula =".$idambito;
				break;
		case $AMBITO_GRUPOSORDENADORES :
				$cmd->texto.=" INNER JOIN gruposordenadores ON gruposordenadores.idgrupo = ordenadores.grupoid
												WHERE gruposordenadores.idgrupo =".$idambito;
			break;
		case $AMBITO_ORDENADORES :
				$cmd->texto.=" WHERE ordenadores.idordenador =".$idambito;
			break;
	}	
		$cmd->texto.=" GROUP BY ordenadores_particiones.numpar,ordenadores_particiones.idnombreso";
	//echo "carga nombresos:".$cmd->texto;
	$rs=new Recordset; 
	$rs->Comando=&$cmd; 
	if (!$rs->Abrir()) return; // Error al abrir recordset
	$rs->Primero();
	$idx=0; 
	while (!$rs->EOF){
			$tbSO[$idx]["idnombreso"]=$rs->campos["idnombreso"];
			$tbSO[$idx]["nombreso"]=$rs->campos["nombreso"];
			$tbSO[$idx]["numpar"]=$rs->campos["numpar"];			
			$tbSO[$idx]["ordenadores"]=$rs->campos["ordenadores"];			
			$idx++;
		$rs->Siguiente();
	}
	$conSO=$idx; // Guarda contador
	$rs->Cerrar();
}
/*________________________________________________________________________________________________________
	
		Toma sistema operativo común a los ordenadores pasados como parámetros
________________________________________________________________________________________________________*/
function tomaNombresSO($numpar,$ordenadores)
{
	global $tbSO; // Tabla contenedora de ordenadores incluidos en la consulta
	global $conSO; // Contador de elementos anteriores

	for($k=0;$k<$conSO;$k++){
		$pos = strpos($tbSO[$k]["ordenadores"], $ordenadores);
		if ($pos !== false) { // Cadena encontrada
			if($tbSO[$k]["numpar"]==$numpar)
				return($tbSO[$k]["nombreso"]);
		}
	}
}
/*________________________________________________________________________________________________________
	
	Selecciona los ordenadores que tienen el mismo tamaño para la misma partición
________________________________________________________________________________________________________*/
function cargaTamano($cmd,$idambito,$ambito)
{
	global $tbTam; // Tabla contenedora de ordenadores incluidos en la consulta
	global $conTam; // Contador de elementos anteriores
	global $AMBITO_AULAS;
	global $AMBITO_GRUPOSORDENADORES;
	global $AMBITO_ORDENADORES;
	
	$cmd->texto="SELECT count(*) as con,ordenadores_particiones.tamano,ordenadores_particiones.numpar,
											group_concat(cast(ordenadores_particiones.idordenador AS char( 11) ) 
											 ORDER BY ordenadores_particiones.idordenador SEPARATOR ',' ) AS ordenadores
							FROM	ordenadores
							INNER JOIN ordenadores_particiones ON ordenadores_particiones.idordenador=ordenadores.idordenador";

	switch($ambito){
		case $AMBITO_AULAS :
				$cmd->texto.=" INNER JOIN aulas ON aulas.idaula = ordenadores.idaula
												WHERE aulas.idaula =".$idambito;
				break;
		case $AMBITO_GRUPOSORDENADORES :
				$cmd->texto.=" INNER JOIN gruposordenadores ON gruposordenadores.idgrupo = ordenadores.grupoid
												WHERE gruposordenadores.idgrupo =".$idambito;
			break;
		case $AMBITO_ORDENADORES :
				$cmd->texto.=" WHERE ordenadores.idordenador =".$idambito;
			break;
	}	
		$cmd->texto.=" GROUP BY ordenadores_particiones.numpar,ordenadores_particiones.tamano";
	//echo "carga tamaños:".$cmd->texto;
	$rs=new Recordset; 
	$rs->Comando=&$cmd; 
	if (!$rs->Abrir()) return; // Error al abrir recordset
	$rs->Primero();
	$idx=0; 
	while (!$rs->EOF){
			$tbTam[$idx]["tamano"]=$rs->campos["tamano"];
			$tbTam[$idx]["numpar"]=$rs->campos["numpar"];			
			$tbTam[$idx]["ordenadores"]=$rs->campos["ordenadores"];			
			$idx++;
		$rs->Siguiente();
	}
	$conTam=$idx; // Guarda contador
	$rs->Cerrar();
}
/*________________________________________________________________________________________________________
	
		Toma tamaño de partición común a los ordenadores pasados como parámetros
________________________________________________________________________________________________________*/
function tomaTamano($numpar,$ordenadores)
{
	global $tbTam; // Tabla contenedora de ordenadores incluidos en la consulta
	global $conTam; // Contador de elementos anteriores

	for($k=0;$k<$conTam;$k++){
		$pos = strpos($tbTam[$k]["ordenadores"], $ordenadores);
		if ($pos !== false) { // Cadena encontrada
			if($tbTam[$k]["numpar"]==$numpar)
				return($tbTam[$k]["tamano"]);
		}
	}
}

?>
