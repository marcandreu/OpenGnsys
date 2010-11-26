#!/bin/bash
# listclientmode.sh: Lista la plantilla de arranque PXE para los clientes, 
#	ya sea un equipo o un aula.
# Nota: Si no existe un enlace entre el fichero PXE con la Ethernet del equipo y su
#	 archivo plantilla, se considera que la plantilla por omisión es "default".
# Uso: listclienmode.sh NombrePC | NombreAula
# Autores: Irina Gomez y Ramon Gomez - Univ. Sevilla, noviembre 2010


# Variables.
PROG=$(basename $0)
OPENGNSYS=${OPENGNSYS:-"/opt/opengnsys"}
SERVERCONF=$OPENGNSYS/etc/ogAdmServer.cfg
PXEDIR=$OPENGNSYS/tftpboot/pxelinux.cfg

# Control básico de errores.
if [ $# -ne 1 ]; then
	echo "$PROG: Error de ejecución"
	echo "Formato: $PROG [NOMBRE_PC|NOMBRE_AULA]"
	exit 1
fi
if [ ! -r $SERVERCONF ]; then
	echo "$PROG: Sin acceso a fichero de configuración"
	exit 2
fi

# Obtener datos de acceso a la Base de datos.
source $SERVERCONF
# Comprobar si se recibe nombre de aula o de equipo.
IDAULA=$(mysql -u "$USUARIO" -p"$PASSWORD" -D "$CATALOG" -N -e \
		"SELECT idaula FROM aulas WHERE nombreaula='$1';")

if [ -n "$IDAULA" ]; then
	# Aula encontrada
	ETHERNET=$(mysql -u "$USUARIO" -p"$PASSWORD" -D "$CATALOG" -N -e \
        	"SELECT mac FROM ordenadores WHERE idaula='$IDAULA';")
else
	# Buscar ordenador
	ETHERNET=$(mysql -u "$USUARIO" -p"$PASSWORD" -D "$CATALOG" -N -e \
        	"SELECT mac FROM ordenadores WHERE nombreordenador='$1';")
fi
if [ -z "$ETHERNET" ]; then
	echo "$PROG: No existe ningun aula o equipo con el nombre \"$1\""
	exit 1
fi

for ETH in $ETHERNET; do
	AUX=$(echo $ETH | awk '{print tolower($0)}')
	AUX="01-${AUX:0:2}-${AUX:2:2}-${AUX:4:2}-${AUX:6:2}-${AUX:8:2}-${AUX:10:2}"
	PCNAME=$(mysql -u "$USUARIO" -p"$PASSWORD" -D "$CATALOG" -N -e \
        	"SELECT nombreordenador FROM ordenadores WHERE mac='$ETH';")
	if [ -f $PXEDIR/$AUX ]; then
		INODE=$(ls -i $PXEDIR/$AUX | cut -f1 -d" ")
		TMPL=$(ls -i $PXEDIR | grep $INODE | grep -v "01-" | cut -f2 -d" ")
		[ -z "$TMPL" ] && TMPL="default"
	else
		TMPL="default"
	fi
	echo "Equipo $PCNAME ($ETH) asociado a plantilla \"$TMPL\""
done

