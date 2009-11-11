#!/bin/bash
# Scirpt de ejemplo para arancar un sistema operativo instalado.
# (puede usarse como base para el programa de arranque usado por OpenGNSys Admin).

PROG="$(basename $0)"
if [ $# -ne 2 ]; then
    ogRaiseError $OG_ERR_FORMAT "Formato: $PROG ndisco nparticion"
    exit $?
fi

# Iniciación de porcentaje de la barra de progreso del Browser.
echo "[0,100]"
echo "[0] Inicio del proceso de arranque."

# Procesos previos.
PART=$(ogDiskToDev "$1" "$2") || exit $?
NAME=$(ogGetHostname)
NAME=${NAME:-"pc"}

# Arrancar.
echo "[5] Desmontar todos los sistemas operativos del disco."
ogUnmountAll $1 | exit $?
case "$(ogGetOsType $1 $2)" in
    Windows)
        echo "20 Activar partición de Windows $PART."
        ogSetPartitionActive $1 $2
        ogEcho info "$PROG: Comprobar sistema de archivos."
        ogCheckFs $1 $2
        NAME=$(ogGetHostname)
        echo "[30] Asignar nombre Windows \"$NAME\"."
        ogSetWindowsName $1 $2 "$NAME"
        ;;
    Linux)
        echo "[20] Asignar nombre Linux \"$NAME\"."
        ETC=$(ogGetPath $1 $2 /etc)
        [ -d "$ETC" ] && echo "$NAME" >$ETC/hostname 2>/dev/null
        if [ -f "$ETC/fstab" ]; then
            echo "[30] Actaualizar fstab con partición raíz \"$PART\"."
            awk -v P="$PART " '{ if ($2=="/") {sub(/^.*$/, P, $1)}
                                 print } ' $ETC/fstab >/tmp/fstab
            mv /tmp/fstab $ETC/fstab
        fi
        ;;
esac
echo "[90] Arrancar sistema operativo."
ogBoot $1 $2

