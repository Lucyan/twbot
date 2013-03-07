<?php
/*
*   unfollow.php
*   Realiza el trabajo de verificar si un usuario sigue de vuelta, dejar de seguir en caso de que no sea así y actualizar la base de datos
*
*/

// Archivos de configuración y api
require('config.php');
require('ambiente.php');
include('twitter.php');

// Si el ambiente está seteado en 1, guarda log
if ($ambiente != 0) {
    $log = "------------------------------------------------------------------------\n";
    $log .= "Inicio Log - ";
    $log .= date("D, d/m/Y, H:i:s")."\n";
}

// Genera un nuevo objeto de la librería de twitter
$twitter = new Twitter($key, $secret);

// genera un array con todos los bots activos de la base de datos
$bots = mysql_query("SELECT * FROM bots WHERE estado = 1");

if ($ambiente != 0) {
    $log .= "----------------------------\n";
    $log .= "Query inicial: SELECT * FROM bots WHERE estado = 1\n";
}

// Recorre el array de bots activos
while ($bot = mysql_fetch_assoc($bots)) {

    // Setea las llaves del bot que se está leyendo
    $twitter->setOAuthToken($bot['tw_token']);
    $twitter->setOAuthTokenSecret($bot['tw_secret']);

    // Add: funcionalidad mensajes
    $mensaje_bot = $bot['frase_cuando_siguen'];

    // Add: Versión Plus
    $plus = $bot['plus'];

    // obtiene array de los tweets que el bot ha encontrado, según la fecha seteada en el bot (2 por defecto) y los estados
    // estado 2: ya se verificó
    // estado 3: no se pudo verificar por limitantes de la api
    $query = "SELECT * FROM tweets WHERE created_at <  '" . date('Y-m-d', strtotime('-'.$bot['verificar_seguido'].' days')) . " 00:00:00' AND bot_id = '" . $bot['id'] . "' AND estado <> 2 AND estado <> 3;";

    if ($ambiente != 0) {
        $log .= "----------------------------\n";
        $log .= "Query bot " . $bot['nombre'] . ": " . $query . "\n";
    }

    // ejecuta query
    $qry = mysql_query($query);

    // Recorre array de tweets
    while($row = mysql_fetch_assoc($qry)) {

        // Cambia el estado del tweet que se está leyendo a 0 (sin verificar)
        mysql_query("UPDATE tweets SET estado = 0 WHERE id = {$row['id']}");

        if ($ambiente != 0) {
            $log .= "----------------------------\n";
            $log .= "Tweet ID: " . $row['id'] . "\nSe cambia a estado 0\n";
        }
        
        // maneja errores de la api
        try {
            // Verifica si el usuario sigue a la cuenta del bot
            if ($twitter->friendshipsExists($row['tw_usuario_id'], $bot['tw_cuenta'])) {
                // si el usuario sigue a la cuenta del bot se cambia el estado de l tweet a 2 (verificado)
                mysql_query("UPDATE tweets SET estado = 2 WHERE id = '{$row['id']}'");
                $qry_cont = mysql_query("SELECT * FROM bots WHERE id = '{$bot['id']}'");

                // obtiene la cantidad de usuarios que han seguido al bot (estadisticas)
                while ($bot_cont = mysql_fetch_assoc($qry_cont)) {
                    $contador = $bot_cont['seguidores'];
                }

                // aumenta el contador de los que han seguido al bot (estadisticas)
                $contador++;
                mysql_query("UPDATE bots SET seguidores = '$contador' WHERE id = '{$bot['id']}'");


                // Add: funcionalidad mensajes - Envía mensaje al usuario que siguio, si está seteado en el bot
                if ($row['mensaje_enviado'] == 0) {
                    if ($plus == true) {
                        if ($mensaje_bot != '') {
                            $twitter->statusesUpdate('@' . $row['tw_usuario'] . ' ' . $mensaje_bot);
                        }
                    }
                    
                    // Setea el mensaje ya enviado
                    mysql_query("UPDATE tweets SET mensaje_enviado = 1 WHERE id = '{$row['id']}'");
                }


                if ($ambiente != 0) {
                    $log .= "Usuario: @" . $row['tw_usuario'] . " sigue a la cuenta, se cambia estado a 2\n";
                    $log .= "Seguidores: " . $contador . "\n";
                }
            } else {
                // Si el usuario no sigue al bot...
                if ($ambiente != 0) {
                    $log .= "Usuario: @" . $row['tw_usuario'] . " no sigue a la cuenta, verificando...\n";
                }

                // Se verifica si el bot sigue al usuario
                if ($twitter->friendshipsExists($bot['tw_cuenta'], $row['tw_usuario_id'])) {
                    if ($ambiente != 0) {
                        $log .= "La cuenta sigue al usuario @" . $row['tw_usuario'] . ", destruyendo amistad...\n";
                    }
                    // maneja errores de la api de twitter
                    try {
                        // realiza unfollow del usuario
                        $twitter->friendshipsDestroy($row['tw_usuario_id']);

                        // marca el estado del tweet en 4 (no se sigue)
                        mysql_query("UPDATE tweets SET estado = 4 WHERE id = {$row['id']}");

                        // obtiene la cantidad de personas que ha seguido el bot (estadisticas)
                        $qry_cont = mysql_query("SELECT * FROM bots WHERE id = '{$bot['id']}'");
                        while ($bot_cont = mysql_fetch_assoc($qry_cont)) {
                            $contador = $bot_cont['siguiendo'];
                        }

                        // se descuenta la persona que se dejó de seguir
                        $contador--;
                        mysql_query("UPDATE bots SET siguiendo = '$contador' WHERE id = '{$bot['id']}'");
                        //mysql_query("DELETE FROM twitter WHERE id = {$row['id']}");
                        if ($ambiente != 0) {
                            $log .= "Amistad destruida, se cambia estado a 4\n";
                            $log .= "Seguidores: " . $contador . "\n";
                        }
                    } catch (Exception $e) {
                        echo $e;
                    }

                } else {

                    // Si el usuario no sigue al bot, y el bot no sigue al usuario, se marca el tweet en estado 4 (no se sigue), esto para que el bot no vuelva a seguir a esta persona
                    mysql_query("UPDATE tweets SET estado = 4 WHERE id = {$row['id']}");
                    if ($ambiente != 0) {
                        $log .= "La cuenta no sigue al usuario, se elimina de la base\n";
                    }
                }
            }
        } catch (Exception $e) {
            // Se captura error en la apo de twitter
            if ($ambiente != 0) {
                $log .= "ERROR: ".$e->getMessage()."\n";
            }

            // Si el error es por que el usuario no tiene permitido verificar si sigue al bot
            if ($e->getMessage() == 'You do not have permission to retrieve following status for both specified users.') {
                // Se marca tweet en estado 3 (desconocido) para que el bot no lo vuelva a seguir o verificar
                mysql_query("UPDATE tweets SET estado = 3 WHERE id = {$row['id']}");
                if ($ambiente != 0) {
                    $log .= "Continua el proceso...\n";
                }
            } else {
                // Si no es el error, se detiene proceso, por limite de la api (máximo 300 request por hora)
                if ($ambiente != 0) {
                    $log .= "Proceso detenido...\n";
                }
                //exit();
                break;
            }
        }
    }
}

// GUARDANDO LOG

if ($ambiente != 0) {
    if ($log != '') {
        $log .= "-----------------------------------------------\n";
        $log .= "\n\nFin del proceso: ";
        $log .= date("D, d/m/Y, H:i:s")."\n";

        $log = $log."\nGuardando log...\n\n\n------------------------------------------------------------------------------------------------\n------------------------------------------------------------------------------------------------\n\n\n";

        //exec("rm " . $directorio . "logfollow.txt");

        $fp=fopen($directorio . "logunfollow.txt","a");

        fwrite($fp,$log);
        fclose($fp) ;

    }
}
?>
