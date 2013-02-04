<?php
require('config.php');
require('ambiente.php');
include('twitter.php');

if ($ambiente != 0) {
    $log = "------------------------------------------------------------------------\n";
    $log .= "Inicio Log - ";
    $log .= date("D, d/m/Y, H:i:s")."\n";
}

$twitter = new Twitter($key, $secret);

$bots = mysql_query("SELECT * FROM bots WHERE estado = 1");
if ($ambiente != 0) {
    $log .= "----------------------------\n";
    $log .= "Query inicial: SELECT * FROM bots WHERE estado = 1\n";
}

while ($bot = mysql_fetch_assoc($bots)) {
    $twitter->setOAuthToken($bot['tw_token']);
    $twitter->setOAuthTokenSecret($bot['tw_secret']);

    // Add: funcionalidad mensajes
    $mensaje_bot = $bot['frase_cuando_siguen'];

    $query = "SELECT * FROM tweets WHERE created_at <  '" . date('Y-m-d', strtotime('-'.$bot['verificar_seguido'].' days')) . " 00:00:00' AND bot_id = '" . $bot['id'] . "' AND estado <> 2 AND estado <> 3;";
    if ($ambiente != 0) {
        $log .= "----------------------------\n";
        $log .= "Query bot " . $bot['nombre'] . ": " . $query . "\n";
    }

    $qry = mysql_query($query);

    while($row = mysql_fetch_assoc($qry)) {
        mysql_query("UPDATE tweets SET estado = 0 WHERE id = {$row['id']}");
        if ($ambiente != 0) {
            $log .= "----------------------------\n";
            $log .= "Tweet ID: " . $row['id'] . "\nSe cambia a estado 0\n";
        }
        
        try {
            if ($twitter->friendshipsExists($row['tw_usuario_id'], $bot['tw_cuenta'])) {
                mysql_query("UPDATE tweets SET estado = 2 WHERE id = '{$row['id']}'");
                $qry_cont = mysql_query("SELECT * FROM bots WHERE id = '{$bot['id']}'");
                while ($bot_cont = mysql_fetch_assoc($qry_cont)) {
                    $contador = $bot_cont['seguidores'];
                }
                $contador++;
                mysql_query("UPDATE bots SET seguidores = '$contador' WHERE id = '{$bot['id']}'");


                // Add: funcionalidad mensajes
                if ($row['mensaje_enviado'] == 0) {
                    if ($mensaje_bot != '') {
                        $twitter->statusesUpdate('@' . $row['tw_usuario'] . ' ' . $mensaje_bot);
                    }
                    
                    mysql_query("UPDATE tweets SET mensaje_enviado = 1 WHERE id = '{$row['id']}'");
                }


                if ($ambiente != 0) {
                    $log .= "Usuario: @" . $row['tw_usuario'] . " sigue a la cuenta, se cambia estado a 2\n";
                    $log .= "Seguidores: " . $contador . "\n";
                }
            } else {
                if ($ambiente != 0) {
                    $log .= "Usuario: @" . $row['tw_usuario'] . " no sigue a la cuenta, verificando...\n";
                }
                if ($twitter->friendshipsExists($bot['tw_cuenta'], $row['tw_usuario_id'])) {
                    //mysql_query("UPDATE twitter SET estado = 1 WHERE id = {$row['id']}");
                    if ($ambiente != 0) {
                        $log .= "La cuenta sigue al usuario @" . $row['tw_usuario'] . ", destruyendo amistad...\n";
                    }
                    try {
                        $twitter->friendshipsDestroy($row['tw_usuario_id']);
                        mysql_query("UPDATE tweets SET estado = 4 WHERE id = {$row['id']}");
                        $qry_cont = mysql_query("SELECT * FROM bots WHERE id = '{$bot['id']}'");
                        while ($bot_cont = mysql_fetch_assoc($qry_cont)) {
                            $contador = $bot_cont['siguiendo'];
                        }
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
                    mysql_query("DELETE FROM tweets WHERE id = {$row['id']}");
                    if ($ambiente != 0) {
                        $log .= "La cuenta no sigue al usuario, se elimina de la base\n";
                    }
                }
            }
        } catch (Exception $e) {
            if ($ambiente != 0) {
                $log .= "ERROR: ".$e->getMessage()."\n";
            }
            if ($e->getMessage() == 'You do not have permission to retrieve following status for both specified users.') {
                mysql_query("UPDATE tweets SET estado = 3 WHERE id = {$row['id']}");
                //mysql_query("DELETE FROM twitter WHERE id = {$row['id']}");
                if ($ambiente != 0) {
                    $log .= "Continua el proceso...\n";
                }
            } else {
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
