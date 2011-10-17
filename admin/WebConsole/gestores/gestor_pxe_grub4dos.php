<?php
include_once("../includes/ctrlacc.php");
include_once("../clases/AdoPhp.php");
include_once("../clases/XmlPhp.php");
include_once("../clases/MenuContextual.php");
include_once("../clases/SockHidra.php");
include_once("../includes/constantes.php");
include_once("../includes/comunes.php");
include_once("../includes/CreaComando.php");
include_once("../idiomas/php/".$idioma."/aulas_".$idioma.".php");

$cmd=CreaComando($cadenaconexion);
if (!$cmd)
	Header('Location: '.$pagerror.'?herror=2'); // Error de conexiÃ³n con servidor B.D.
//________________________________________________________________________________________________________


echo "<html>";
echo "<head>";
echo "<meta http-equiv='Refresh' content='1;URL=../principal/boot.php?idambito=". $_GET['idaula'] ."&nombreambito=" . $_GET['nombreambito'] . "&litambito=" . $_GET['litambito'] . "'>";
echo "<title> gestion de equipos </title>";
echo "<base target='principal'>";
echo "</head>";
echo "<body>";



#echo('litambito con valor:     '. $_GET['litambito']);
#echo ('idambito con valor:      ' . $_GET['idaula']);
#echo ('nombreambito con valor:      ' . $_GET['nombreambito']);

$lista = explode(";",$_POST['listOfItems']);
foreach ($lista as $sublista) {
	$elementos = explode("|",$sublista);
	$hostname=$elementos[1];
	$optboot=$elementos[0];
	ogBootServer($cmd,$optboot,$hostname);
}
echo " </body>";
echo " </html> ";

function ogBootServer($cmd,$optboot,$hostname) 
{	
global $cmd;
global $hostname;
global $optboot;
global $retrun;
$return="\n";
$cmd->CreaParametro("@optboot",$optboot,0);
$cmd->CreaParametro("@hostname",$hostname,0);
$cmd->texto="update ordenadores set arranque=@optboot where nombreordenador=@hostname";
$cmd->Ejecutar();

$cmd->texto="SELECT ordenadores.ip AS ip, ordenadores.mac AS mac, 
			ordenadores.netiface AS netiface, aulas.netmask AS netmask,
			aulas.router AS router, repositorios.ip AS iprepo,
			aulas.nombreaula AS grupo
			FROM ordenadores 
			JOIN aulas ON ordenadores.idaula=aulas.idaula 
			JOIN repositorios ON ordenadores.idrepositorio=repositorios.idrepositorio 
			WHERE ordenadores.nombreordenador='". $hostname ."'"; 
$rs=new Recordset; 
$rs->Comando=&$cmd; 
if (!$rs->Abrir()) echo "error";
$rs->Primero(); 
	$mac=$rs->campos["mac"];
	$netiface=$rs->campos["netiface"];
	$ip=$rs->campos["ip"];
	$router=$rs->campos["router"];
	$netmask=$rs->campos["netmask"]; 
	$repo=$rs->campos["iprepo"];   			
	$group=cleanString($rs->campos["grupo"]);
$rs->Cerrar();

$cmd->texto="SELECT ipserveradm FROM entornos";
$rs=new Recordset;
$rs->Comando=&$cmd;
if (!$rs->Abrir()) echo "error";

$rs->Primero();
        $server=$rs->campos["ipserveradm"];
$rs->Cerrar();

$infohost="'ip=$ip:$server:$router:$netmask:$hostname:$netiface:none" .
	  " group=$group" .
	  " ogrepo=$repo" .
	  " oglive=$repo" .
	  " oglog=$server" .
	  " ogshare=$server'";


###################obtenemos las variables de red del aula.

	#02.1 obtenemos nombre fichero mac
	$mac=  substr($mac,0,2) . ":" . substr($mac,2,2) . ":" . substr($mac,4,2) . ":" . substr($mac,6,2) . ":" . substr($mac,8,2) . ":" . substr($mac,10,2);
	$macfile="01-" . str_replace(":","-",strtoupper($mac));	
	$nombre_archivo="/var/lib/tftpboot/menu.lst/" . $macfile;

#controlar optboot

	#exec("cp /var/lib/tftpboot/menu.lst/templates/". $optboot . " /var/lib/tftpboot/menu.lst/". $macfile);
	exec("sed s/INFOHOST/".$infohost."/g /var/lib/tftpboot/menu.lst/templates/" . $optboot . " > /var/lib/tftpboot/menu.lst/" . $macfile);
	exec("chown www-data:www-data /var/lib/tftpboot/menu.lst/". $macfile);
	exec("chmod 777 /var/lib/tftpboot/menu.lst/". $macfile);
	



}

function netmask2cidr($netmask) {
          $cidr = 0;
          foreach (explode('.', $netmask) as $number) {
              for (;$number> 0; $number = ($number <<1) % 256) {
                  $cidr++;
               }
           }
           return $cidr;
 }

// Sustituye espacio por "_" y quita acentos y tildes.
function cleanString ($cadena) {
	$patron = array ('/ /','/á/','/é/','/í/','/ó/','/ú/','/ñ/','/Á/','/É/','/Í/','/Ó/','/Ú/','/Ñ/');
	$reemplazo = array ('_','a','e','i','o','u','n','A','E','I','O','U','N');
	return  preg_replace($patron,$reemplazo,$cadena);
}

?>
