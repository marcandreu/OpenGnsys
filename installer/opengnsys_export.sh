#!/bin/bash
#         exportclient str_backupfile
#@file    exportclient
#@brief   Exporta los clientes de un archivo de backup: dhcp, pxe, páginas de inicio y configuración de la consola.
#@param 1 str_backupfile fichero de backup (creado con exportclient)
#@exception 1 Error de formato.
#@exception 2 Sólo ejecutable por usuario root.
#@exception 3 Sin acceso a la configuración de OpenGnsys.
#@exception 4 No existe el directorio de backup.
#@version 1.1.0 - Versión inicial.
#@author  Irina Gómez - ETSII Univ. Sevilla
#@date    2016-10-18
#*/ ##

# Variables globales.
PROG="$(basename $0)"

OPENGNSYS="/opt/opengnsys"
TMPDIR=/tmp
MYSQLFILE="$TMPDIR/ogAdmBD.sql"
MYSQLFILE2="$TMPDIR/usuarios.sql"
BACKUPPREFIX="opengnsys_export"

# Si se solicita, mostrar ayuda.
if [ "$*" == "help" ]; then
    echo -e "$PROG: Exporta los datos de OpenGnsys desde un archivo de backup:" \
           " dhcp, pxe, páginas de inicio y configuración de la consola.\n" \
           "    Formato: $PROG backup_file\n" \
           "    Ejemplo: $PROG backup.tgz"
    exit
fi

# Comprobar parámetros.
# Comprobamos número de parámetros
if [ $# -ne 1 ]; then
    echo "$PROG: ERROR: Error de formato: $PROG backup_file"
    exit 1
fi

if [ "$USER" != "root" ]; then
    echo "$PROG: Error: solo ejecutable por root." >&2
    exit 2
fi

# Comprobamos  acceso a ficheros de configuración
if ! [ -r $OPENGNSYS/etc/ogAdmServer.cfg ]; then
    echo "$PROG: ERROR: Sin acceso a la configuración de OpenGnsys." | tee -a $FILESAL
    exit 3
fi

# Comprobamos que exista el directorio para el archivo de backup
BACKUPDIR=$(realpath $(dirname $1) 2>/dev/null)
[ $? -ne 0 ] && echo "$PROG: Error: No existe el directorio para el archivo de backup" && exit 4
BACKUPFILE="$BACKUPDIR/$(basename $1)"

# DHCP
for DIR in /etc/dhcp /etc/dhcp3; do
    [ -r $DIR/dhcpd.conf ] && DHCPDIR=$DIR
done

# Exportar la base de datos
echo "Exportamos la información de la base de datos."
source $OPENGNSYS/etc/ogAdmServer.cfg
# Crear fichero temporal de acceso a la BD
MYCNF=$(mktemp /tmp/.my.cnf.XXXXX)
chmod 600 $MYCNF
trap "rm -f $MYCNF $MYSQLFILE $TMPDIR/IPSERVER.txt" 1 2 3 6 9 15
cat << EOT > $MYCNF
[client]
user=$USUARIO
password=$PASSWORD
EOT

# MYSQL: Excluimos las tablas del servidor de administración (entornos) y repositorios
mysqldump --defaults-extra-file=$MYCNF --opt $CATALOG \
          --ignore-table=${CATALOG}.entornos \
          --ignore-table=${CATALOG}.repositorios \
          --ignore-table=${CATALOG}.usuarios > $MYSQLFILE
# Tabla usuario
mysqldump --defaults-extra-file=$MYCNF --opt --no-create-info $CATALOG \
          usuarios | sed 's/^INSERT /INSERT IGNORE /g' >> $MYSQLFILE2
# Borrar fichero temporal
rm -f $MYCNF

# IP SERVIDOR
echo $ServidorAdm > $TMPDIR/IPSERVER.txt

# Si existe ya archivo de backup lo renombramos
[ -r $BACKUPFILE ] && mv $BACKUPFILE $BACKUPFILE-$(date +%Y%M%d)

# Empaquetamos los ficheros
echo "Creamos un archivo comprimido con los datos: $BACKUPFILE."
tar -cvzf $BACKUPFILE --transform="s!^!$BACKUPPREFIX/!" \
          -C $(dirname $MYSQLFILE) $(basename $MYSQLFILE) \
          -C $(dirname $MYSQLFILE2) $(basename $MYSQLFILE2) \
          -C $TMPDIR IPSERVER.txt \
          -C $DHCPDIR dhcpd.conf \
          -C $OPENGNSYS/tftpboot menu.lst \
          -C $OPENGNSYS/doc VERSION.txt \
          -C $OPENGNSYS/client/etc engine.cfg \
          -C $OPENGNSYS/www menus \
          -C /etc default/opengnsys &>/dev/null

# Cambio permisos: sólo puede leerlo el root
chmod 600 $BACKUPFILE

# Borrar ficheros temporales
rm -f $MYSQLFILE $TMPDIR/IPSERVER.txt

echo -e "\nNo conveniente situar el fichero de backup dentro de /opt/opengnsys" \
        "\n    ya que se borrará si desinstala OpenGnsys." 
