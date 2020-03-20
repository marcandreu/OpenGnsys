<?php
/** @file includes/arbol.php
 *  Funciones auxiliares para pintar árboles de nodos en el frame izquierdo
 */

/**
 * Devuelve un array de grupos de nodos. Ordenados por tipos de nodos y grupo
 * @param[tipo_nodo] str tipo de elemento a mostrar en el árbol (aulas, imagenes, ...)
 * @return array de grupos.
 * @note  usamos la variable tipo por compatibilidad con grupos de imágenes.
 */
function grupos_arbol($tipo_nodo) {
        global $cmd;
        global $idcentro;

        $grupos_hijos=Array();
	$sql="";
	$ambito="";

	switch ($tipo_nodo){
	    case 'aulas':
		global $AMBITO_GRUPOSAULAS;
		$ambito=$AMBITO_GRUPOSAULAS;
                break; 
	    case 'componenteshardware':
		global $AMBITO_GRUPOSCOMPONENTESHARD;
		$ambito=$AMBITO_GRUPOSCOMPONENTESHARD;
                break; 
	    case 'componentessoftware':
		global $AMBITO_GRUPOSCOMPONENTESSOFT;
		$ambito=$AMBITO_GRUPOSCOMPONENTESSOFT;
                break; 
	    case 'imagenes':
		$ambito="70, 71, 72";
                break; 
	    case 'menus':
		global $AMBITO_GRUPOSMENUS;
		$ambito=$AMBITO_GRUPOSMENUS;
                break; 
	    case 'ordenadores':
		$sql="SELECT gruposordenadores.idgrupo AS id, nombregrupoordenador AS nombre,
                            gruposordenadores.idaula AS conjuntoid, gruposordenadores.grupoid AS grupopadre
                       FROM gruposordenadores
                 INNER JOIN aulas
                      WHERE gruposordenadores.idaula=aulas.idaula AND idcentro=$idcentro
                   ORDER BY conjuntoid, gruposordenadores.grupoid;";
                break; 
	    case 'perfileshardware':
		global $AMBITO_GRUPOSPERFILESHARD;
		$ambito=$AMBITO_GRUPOSPERFILESHARD;
                break; 
	    case 'perfilessoftware':
		global $AMBITO_GRUPOSPERFILESSOFT;
		$ambito=$AMBITO_GRUPOSPERFILESSOFT;
                break; 
	    case 'perfilessoftwareincremental':
		global $AMBITO_GRUPOSSOFTINCREMENTAL;
		$ambito=$AMBITO_GRUPOSSOFTINCREMENTAL;
                break; 
	    case 'procedimientos':
		global $AMBITO_GRUPOSPROCEDIMIENTOS;
		$ambito=$AMBITO_GRUPOSPROCEDIMIENTOS;
                break; 
	    case 'repositorios':
		global $AMBITO_GRUPOSREPOSITORIOS;
		$ambito=$AMBITO_GRUPOSREPOSITORIOS;
                break; 
	    case 'tareas':
		global $AMBITO_GRUPOSTAREAS;
		$ambito=$AMBITO_GRUPOSTAREAS;
                break; 
	}
        if ($sql == "" && $ambito != "") {
		$sql="SELECT idgrupo AS id, nombregrupo AS nombre, grupos.grupoid AS grupopadre, tipo AS conjuntoid
                       FROM grupos
                      WHERE idcentro=$idcentro AND tipo IN ($ambito)
                   ORDER BY tipo, grupopadre, grupoid;";
	}
	if ($sql == "")	return ($grupos_hijos);

        $rs=new Recordset;
        $cmd->texto=$sql;
 //echo "text: ".$cmd->texto;
 //echo "<br><br>";
        $rs->Comando=&$cmd;
        if (!$rs->Abrir()) return($grupos_hijos);

        $rs->Primero();
        $oldgrupopadre=0;
        $num=-1;
        while (!$rs->EOF){
                $grupopadre=$rs->campos["grupopadre"];
                $nombre=$rs->campos["nombre"];

                $id=$rs->campos["id"];
                $conjuntoid=$rs->campos["conjuntoid"];
		// El tipo de grupo de imagenes son 70, 71 y 72 correspondiendo al tipo de imagen 1, 2 y 3
		if ($tipo_nodo == "imagenes") $conjuntoid-=69;

                if ($oldgrupopadre != $grupopadre) {
                        $oldgrupopadre=$grupopadre;
                        // Cuando cambio de grupo pongo el orden del array a cero
                        $num=0;
                } else {
                        $num++;
                }
                $grupos_hijos[$conjuntoid][$grupopadre][$num]["id"] = $id;
                $grupos_hijos[$conjuntoid][$grupopadre][$num]["nombre"] = $nombre;

                $rs->Siguiente();
        }
        $rs->Cerrar();
        return ($grupos_hijos);

}

/**
 * Construye la parte del árbol correspondiente a un grupo de nodos: lista sus nodos y sus grupos hijos.
 *
 * @param[tipo_nodo] str tipo de elemento que muestra el árbol
 * @param[idgrupo]   int identificados del grupo que se está listando
 * @param[nivel]     int nivel de profundidad del árbol
 * @param[orden]     int orden dentro del nivel del árbol
 * @param[nodos]     array nodos
 * @param[grupos]    array grupos de nodos
 * @return           orden de la lista del último elemento listado
 */
function lista_grupo_arbol($tipo_nodo, $tipo, $idgrupo, $nivel, $orden, $nodos, $grupos){
	/* Al cargar la página sólo se muestra el primer nivel del árbol. */
	$class=($nivel==1)? "": 'class="interior"';
        $nivel=$nivel+1;
	if ($tipo_nodo == "aulas") {
	    global $ordenadores;
	    global $grp_ordenadores;
	    global $idaulas;
	}
        echo "\n".'    <ul '.$class." >\n";
        // si existen grupos hijos del actual creo la lista con la función listaGrupo.
        if (isset ($grupos[$tipo][$idgrupo])){
            foreach ($grupos[$tipo][$idgrupo] as $hijo) {
                $orden=$orden+1;
                echo '      <li id="grupo_'.$hijo["id"].'" oncontextmenu="mostrar_menu(event,'. $tipo.', '.$hijo["id"].', \'menu-groups-'.$tipo_nodo.'\');return false;"><input type="checkbox" name="list" id="nivel'.$nivel.'-'.$orden.'"><label for="nivel'.$nivel.'-'.$orden.'"><img class="menu_icono" src="../images/iconos/carpeta.gif">'.$hijo["nombre"].'</label>'."\n";
                //echo '      <li oncontextmenu="mostrar_menu(event,'. $tipo.', '.$hijo["id"].', \'menu-groups\');return false;"><input type="checkbox" name="list" id="nivel'.$nivel.'-'.$hijo["id"].'"><label for="nivel'.$nivel.'-'.$hijo["id"].'"><img class="menu_icono" src="../images/iconos/carpeta.gif">'.$hijo["nombre"].'</label>'."\n";

                $orden=lista_grupo_arbol($tipo_nodo, $tipo, $hijo["id"], $nivel, $orden, $nodos, $grupos);
            }
            echo "      </li>"."\n";
        }
        // creo la lista de las imágenes dentro del grupo (si existen).
        if (isset ($nodos[$tipo][$idgrupo])){
            foreach ($nodos[$tipo][$idgrupo] as $nodo){
		if ($tipo_nodo == "aulas"){
		    // Incluyo input para que se pueda abrir el nodo
		    echo '      <li id="'.$tipo_nodo.'_'.$nodo["id"].'" oncontextmenu="ocultar_menu(); mostrar_menu(event,'. $tipo.', '.$nodo["id"].', \'menu-'.$tipo_nodo.'\');return false;">';
		    echo '<input type="checkbox" name="list" id="nivel'.$nivel.'-'.$orden.'"><label for="nivel'.$nivel.'-'.$orden.'">';
		    echo '<img class="menu_icono" src="../images/iconos/imagen.gif"> '.$nodo["descripcion"];
		    echo '</label>'."\n" ;

		    // Listo grupo de ordenadores
		    lista_grupo_arbol("ordenadores",$nodo["id"],0, $nivel, $orden,$ordenadores,$grp_ordenadores);
		} else {
		    echo '      <li id="'.$tipo_nodo.'_'.$nodo["id"].'" oncontextmenu="ocultar_menu(); mostrar_menu(event,'. $tipo.', '.$nodo["id"].', \'menu-'.$tipo_nodo.'\');return false;">';
		    echo '<a href="#r">';
		    echo '<img class="menu_icono" src="../images/iconos/imagen.gif"> '.$nodo["descripcion"];
		    echo '</a>';
		}
                echo "      </li>"."\n";
            }
        }
        echo "    </ul>"."\n";
        return($orden);
}


/**
 * Devuelve un array de grupos de nodos. Ordenados por tipos de nodos y grupo
 * @param[tipo_nodo] str tipo de elemento a mostrar en el árbol (aulas, imagenes, ...)
 * @param[nodos] array nodos a mostrar
 * @param[grupos] array grupos de nodos a mostrar
 * @return Escribe la raíz del árbol de nodos y el primer nivel.
 */
function lista_raiz_arbol ($tipo_nodo, $nodos, $grupos){
        global $TbMsg;
        global $NUM_TIPOS_IMAGENES;
        $orden=0;
	if ($tipo_nodo == "aulas") {
	    // Definimos variables para mostrar ordenadores
	    global $ordenadores;
	    global $grp_ordenadores;
	    $ordenadores=nodos_arbol("ordenadores");
	    $grp_ordenadores=grupos_arbol("ordenadores");
	}
        echo '<ul id="menu_arbol">'."\n";
        echo '  <li><input type="checkbox" name="list" id="nivel1-1"><label for="nivel1-1"><img class="menu-icono" src="../images/iconos/imagenes.gif">'.str_replace ('"','',$TbMsg[9]).'</label>'."\n";
	$keys=array_keys($nodos);
	if (count($keys) == 1) {
	    lista_grupo_arbol($tipo_nodo, $keys[0], 0, 1, $orden, $nodos, $grupos);
	} else {
	    foreach ($keys as $tipo) {
                // Recorremos los grupos hijos desde el cero
                echo '    <ul>'."\n";
                echo '       <li id="grupo_'.$tipo.'_0" oncontextmenu="mostrar_menu(event, '. $tipo.', 0, \'menu-tipes\');return false;">'."\n";
                //echo '          <input type="checkbox" name="list" id="nivel2-'.$tipo.'"><label for="nivel2-'.$tipo.'"><img class="menu-icono" src="../images/iconos/carpeta.gif"> '.$tipo.' '.str_replace ('"','',$TbMsg[10+$tipo]).'</label>'."\n";
                echo '          <input type="checkbox" name="list" id="nivel2-'.$tipo.'"><label for="nivel2-'.$tipo.'"><img class="menu-icono" src="../images/iconos/carpeta.gif"> Falta nombre del tipo: '.$tipo.'</label>'."\n";
                $orden=lista_grupo_arbol($tipo_nodo, $tipo, 0, 2, $orden, $nodos, $grupos);
                $orden=$orden+1;
                echo '       </li>'."\n";
                echo '    </ul>'."\n";
	    }
        }
        echo "  </li>"."\n";
        echo "</ul>"."\n";
}



/*
 * Descripción: Devuelve un array de nodos ordenados por el grupo al que pertenecen.
 * Parámetros:
 *     tipo_nodo: str tipo de elemento a mostrar en el árbol (aulas, imagenes, ...)
 *     Devuelve: array de nodos
 * Nota: Existen nodos clasificados por tipos. Para los no tienen tipos se usa un único tipo = 1
 */
function nodos_arbol($tipo_nodo){
        global $TbMsg;
        global $cmd;
        global $idcentro;
	//$nodos= Array();

	switch ($tipo_nodo){
	    case 'aulas':
		global $AMBITO_GRUPOSAULAS;
		$sql="SELECT idaula AS id, nombreaula AS nombre, grupoid, '$AMBITO_GRUPOSAULAS' AS conjuntoid
			FROM  aulas
                      WHERE idcentro=$idcentro ORDER BY conjuntoid, grupoid;";
                break; 
	    case 'comandos':
		$sql="SELECT idcomando AS id, descripcion AS nombre, urlimg,
			    '1' AS conjuntoid, '0' AS grupoid
			FROM comandos
		       WHERE activo=1 ORDER BY conjuntoid, grupoid;";
                break; 
	    case 'componenteshardware':
		global $AMBITO_GRUPOSCOMPONENTESHARD;
		$sql="SELECT hardwares.idhardware AS id, hardwares.descripcion AS nombre,
			     tipohardwares.urlimg, '$AMBITO_GRUPOSCOMPONENTESHARD' AS conjuntoid, grupoid
		        FROM hardwares INNER JOIN tipohardwares
		       WHERE hardwares.idtipohardware=tipohardwares.idtipohardware AND idcentro=$idcentro
                    ORDER BY conjuntoid, grupoid;";
                break; 
	    case 'componentessoftware':
		global $AMBITO_GRUPOSCOMPONENTESSOFT;
		$sql="SELECT softwares.idsoftware AS id, softwares.descripcion AS nombre,
			     tiposoftwares.urlimg, '$AMBITO_GRUPOSCOMPONENTESSOFT' AS conjuntoid, grupoid
                        FROM softwares INNER JOIN tiposoftwares
		       WHERE softwares.idtiposoftware=tiposoftwares.idtiposoftware AND idcentro=$idcentro
		     ORDER BY conjuntoid, grupoid;";
                break; 
	    case 'imagenes':
		$sql="SELECT DISTINCT imagenes.idimagen AS id ,imagenes.descripcion AS nombre,
			              imagenes.tipo AS conjuntoid, imagenes.grupoid,
                             IF(imagenes.idrepositorio=0,basica.idrepositorio,imagenes.idrepositorio)  AS repo
                        FROM imagenes
                   LEFT JOIN imagenes AS basica  ON imagenes.imagenid=basica.idimagen
                       WHERE imagenes.idcentro=$idcentro ORDER BY conjuntoid, grupoid;";
                break; 
	    case 'menus':
		global $AMBITO_GRUPOSMENUS;
		$sql="SELECT idmenu AS id, descripcion AS nombre, '$AMBITO_GRUPOSMENUS' AS conjuntoid, grupoid
			FROM menus  
		       WHERE idcentro=$idcentro ORDER BY conjuntoid, grupoid;";
                break; 
	    case 'ordenadores':
		$sql="SELECT idordenador AS id, nombreordenador AS nombre,
                             ordenadores.idaula AS conjuntoid, ordenadores.grupoid
                        FROM ordenadores
                  INNER JOIN aulas
                       WHERE ordenadores.idaula=aulas.idaula AND idcentro=$idcentro
                    ORDER BY conjuntoid, grupoid;";
                break; 
	    case 'perfileshardware':
		global $AMBITO_GRUPOSPERFILESHARD;
		$sql="SELECT idperfilhard AS id, descripcion AS nombre,
			     '$AMBITO_GRUPOSPERFILESHARD' AS conjuntoid, grupoid
			FROM perfileshard
		       WHERE idcentro=$idcentro
                   ORDER BY conjuntoid, grupoid;";
                break; 
	    case 'perfilessoftware':
		global $AMBITO_GRUPOSPERFILESSOFT;
		$sql="SELECT idperfilsoft AS id, descripcion AS nombre,
			     '$AMBITO_GRUPOSPERFILESSOFT' AS conjuntoid, grupoid
			FROM perfilessoft
		       WHERE idcentro=$idcentro
                   ORDER BY conjuntoid, grupoid;";
                break; 
	    case 'procedimientos':
		global $AMBITO_GRUPOSPROCEDIMIENTOS;
		$sql="SELECT idprocedimiento AS id, descripcion AS nombre, '$AMBITO_GRUPOSPROCEDIMIENTOS' AS conjuntoid, grupoid
		        FROM procedimientos
		       WHERE idcentro=$idcentro ORDER BY conjuntoid, grupoid;";
                break; 
	    case 'repositorios':
		global $AMBITO_GRUPOSREPOSITORIOS;
		$sql="SELECT idrepositorio AS id, nombrerepositorio AS nombre, '$AMBITO_GRUPOSREPOSITORIOS' AS conjuntoid, grupoid
	    	        FROM repositorios
		       WHERE idcentro=$idcentro ORDER BY conjuntoid, grupoid;";
                break; 
	    case 'tareas':
		global $AMBITO_GRUPOSTAREAS;
		$sql="SELECT idtarea AS id, descripcion AS nombre, ambito, '$AMBITO_GRUPOSTAREAS' AS conjuntoid, grupoid
                        FROM tareas 
		       WHERE idcentro=$idcentro ORDER BY conjuntoid, grupoid;";
                break; 
	    case 'tiposhardware':
		$sql="SELECT idtipohardware AS id, descripcion AS nombre, urlimg, nemonico,
			     '1' AS conjuntoid, '0' AS grupoid
                        FROM tipohardwares
		    ORDER BY conjuntoid, grupoid;";
                break; 
	    case 'tipossoftware':
		$sql="SELECT idtiposoftware AS id, descripcion AS nombre, urlimg,
			     '1' AS conjuntoid, '0' AS grupoid
                        FROM tiposoftwares
		    ORDER BY conjuntoid, grupoid;";
                break; 
	}
	
//echo "sql: ".$sql;
//echo "<br><br>";
        $nodos=Array();
        $grupos_hijos=Array();
        $rs=new Recordset;
        $cmd->texto=$sql;

        $rs->Comando=&$cmd;
        if (!$rs->Abrir()) return(Array($nodos));

        $rs->Primero();
        $ordenNodo=-1;
	$oldgrupoid=(isset($rs->campos["grupoid"]))? $rs->campos["grupoid"] : 0;
	while (!$rs->EOF){
		$conjuntoid=$rs->campos["conjuntoid"];
                $id=$rs->campos["id"];
                $descripcion=$rs->campos["nombre"];
                // Las nodos de un grupo son un array. Cuando cambio de grupo pongo el orden a cero:
                $grupoid=(isset($rs->campos["grupoid"]))? $rs->campos["grupoid"] : 0;
                if ($oldgrupoid != $grupoid) {
                        $oldgrupoid=$grupoid;
                        $ordenNodo=0;
                } else {
                        $ordenNodo=$ordenNodo+1;
                }

                $nodos[$conjuntoid][$grupoid][$ordenNodo]["descripcion"]=$descripcion;
                $nodos[$conjuntoid][$grupoid][$ordenNodo]["id"]=$id;
                $rs->Siguiente();
        }

        $rs->Cerrar();
        return($nodos);
}



?>
