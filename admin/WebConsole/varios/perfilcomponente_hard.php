<?php
// *************************************************************************************************************************************************
// Aplicación WEB: ogAdmWebCon
// Autor: José Manuel Alonso (E.T.S.I.I.) Universidad de Sevilla
// Fecha Creación: Año 2009-2010
// Fecha Última modificación: Agosto-2010
// Nombre del fichero: perfilcomponente_hard.php
// Descripción : 
//		Administra los componentes hardware incluidos en un perfil harware
// *************************************************************************************************************************************************
include_once("../includes/ctrlacc.php");
include_once("../clases/AdoPhp.php");
include_once("../includes/CreaComando.php");
include_once("../idiomas/php/".$idioma."/perfilcomponente_hard_".$idioma.".php");
//________________________________________________________________________________________________________
$idperfilhard=0; 
$descripcionperfil=""; 
if (isset($_GET["idperfilhard"])) $idperfilhard=$_GET["idperfilhard"]; // Recoge parametros
if (isset($_GET["descripcionperfil"])) $descripcionperfil=$_GET["descripcionperfil"]; // Recoge parametros

$cmd=CreaComando($cadenaconexion);
if (!$cmd)
	Header('Location: '.$pagerror.'?herror=2'); // Error de conexióncon servidor B.D.
//________________________________________________________________________________________________________
?>
<HTML>
<HEAD>
<TITLE>Administración web de aulas</TITLE>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<LINK rel="stylesheet" type="text/css" href="../estilos.css">
<SCRIPT language="javascript" src="../jscripts/perfilcomponente_hard.js"></SCRIPT>
<SCRIPT language="javascript" src="../jscripts/opciones.js"></SCRIPT>
<SCRIPT language="javascript" src="../clases/jscripts/HttpLib.js"></SCRIPT>
<?php echo '<SCRIPT language="javascript" src="../idiomas/javascripts/'.$idioma.'/perfilcomponente_hard_'.$idioma.'.js"></SCRIPT>'?>
</HEAD>
<BODY>
<FORM  name="fdatos"> 
	<INPUT type=hidden value="<?php echo $idcentro?>" id=idcentro>	 
	<INPUT type=hidden value="<?php echo $idperfilhard?>" id=idperfilhard>	 
	<P align=center class=cabeceras><?php echo $TbMsg[0]?><BR>
	<SPAN align=center class=subcabeceras><?php echo $TbMsg[1]?></SPAN>&nbsp;<IMG src="../images/iconos/confihard.gif"></P>
	<BR>
	<DIV align=center id="Layer_componentes">
		<SPAN align=center class=presentaciones><B><U><?php echo $TbMsg[2]?></U>:&nbsp;<?php echo $descripcionperfil?></B></SPAN></P>
		<TABLE width="100%" class="tabla_listados" cellspacing=1 cellpadding=0 >
			 <TR>
				<TH>&nbsp</TH>
				<TH>T</TH>
				<TH><?php echo $TbMsg[3]?></TH>
			</TR>
		<?php
			$rs=new Recordset; 
			$cmd->texto='SELECT hardwares.idhardware, hardwares.descripcion,'.
				    '       tipohardwares.descripcion AS hdescripcion, tipohardwares.urlimg'.
				    '  FROM hardwares'.
				    ' INNER JOIN perfileshard_hardwares ON hardwares.idhardware=perfileshard_hardwares.idhardware'.
				    ' INNER JOIN tipohardwares ON hardwares.idtipohardware=tipohardwares.idtipohardware'.
				    ' WHERE perfileshard_hardwares.idperfilhard='.$idperfilhard.
				    ' ORDER BY tipohardwares.idtipohardware, hardwares.descripcion';
			$rs->Comando=&$cmd; 

			if ($rs->Abrir()){ 
				$rs->Primero();
				$A_W=" WHERE ";
				$strex="";
				while (!$rs->EOF){
						 echo '<TR>';
						 echo '<TD align=center width="10%" ><INPUT type=checkbox onclick="gestion_componente('.$rs->campos["idhardware"].',this)" checked ></INPUT></TD>';
						 echo '<TD align=center width="10%" ><IMG alt="'. $rs->campos["hdescripcion"].'"src="'.$rs->campos["urlimg"].'"></TD>';
						 echo '<TD  width="80%" >&nbsp;'.$rs->campos["descripcion"].'</TD>';
						 echo '</TR>';
						 $strex.= $A_W."hardwares.idhardware<>".$rs->campos["idhardware"];
						$A_W=" AND ";
						$rs->Siguiente();
				}
			}
			$rs->Cerrar();
			$cmd->texto='SELECT hardwares.idhardware, hardwares.descripcion,'.
				    '       tipohardwares.descripcion AS hdescripcion, tipohardwares.urlimg,'.
				    '  FROM hardwares'.
				    ' INNER JOIN tipohardwares ON hardwares.idtipohardware=tipohardwares.idtipohardware '.
				    $strex.' AND hardwares.idcentro='.$idcentro.
				    ' ORDER BY tipohardwares.idtipohardware, hardwares.descripcion';
			$rs->Comando=&$cmd; 
			if ($rs->Abrir()){
				$rs->Primero();
				while (!$rs->EOF){
						 echo '<TR>';
						 echo '<TD align=center width="10%" ><INPUT type=checkbox onclick="gestion_componente('.$rs->campos["idhardware"].',this)"  ></INPUT></TD>';
						 echo '<TD align=center width="10%" ><IMG alt="'. $rs->campos["hdescripcion"].'"src="'.$rs->campos["urlimg"].'"></TD>';

						 echo '<TD width="80%" >&nbsp;'.$rs->campos["descripcion"].'</TD>';
						 echo '</TR>';
						$rs->Siguiente();
				}
			}
			$rs->Cerrar();
		?>
		</TABLE>
	</DIV>		
	<DIV id="Layer_nota" align=center >
		<BR>
		<SPAN align=center class=notas><I><?php echo $TbMsg[4]?></I></SPAN>
	</DIV>
</FORM>
</BODY>
</HTML>
