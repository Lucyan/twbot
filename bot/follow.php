#!/usr/bin/php  
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


while ($bot = mysql_fetch_assoc($bots)) {

    $fecha_renovacion = date("d/m/Y",strtotime($bot['fecha_renovacion']));
    $fecha_actual = date("d/m/Y",time());

    if (compararFechas($fecha_actual, $fecha_renovacion) > 30) {
        mysql_query("UPDATE bots SET estado = 0 WHERE id = '{$bot['id']}';");
    } else {
        $twitter->setOAuthToken($bot['tw_token']);
        $twitter->setOAuthTokenSecret($bot['tw_secret']);
        $ciudad_indice = $bot['ciudad_indice'];
        $palabra_indice = $bot['palabra_indice'];
        $cantidad_seguidos = $bot['siguiendo'];
        $max_seguir = $bot['cantidad_seguir'];
        $cantidad_seguir = 0;

        // Add: funcionalidad mensajes
        $mensaje_bot = $bot['frase_al_seguir'];

        // Add: Versión Plus
        $plus = $bot['plus'];

        $palabras = mysql_query("SELECT * FROM palabras WHERE bot_id = '{$bot['id']}';");

        $arrayPalabras = array();
        $index = 1;
        while ($palabra = mysql_fetch_assoc($palabras)) {
            $arrayPalabras[$index]['palabra'] = $palabra['palabra'];
            // Add: funcionalidad mensajes
            $arrayPalabras[$index]['mensaje'] = $palabra['frase'];
            $index ++;
        }
        $cantidad_palabras = count($arrayPalabras);
        mysql_query("UPDATE bots SET palabra_maximo = $cantidad_palabras WHERE id = '{$bot['id']}';");
        if ($ambiente != 0) {
            $log .= "---------------------------\nPalabras\n";
            $log .= "Query: SELECT * FROM palabras WHERE bot_id = '" . $bot['id'] . "';\n";
            $log .= print_r($arrayPalabras, true);
            $log .= "\n";
        }

        $ciudades = mysql_query("SELECT * FROM ciudads AS ciu JOIN bot_ciudads AS bot ON ciu.id = bot.ciudad_id WHERE bot.bot_id = '{$bot['id']}';");

        $arrayCiudades = array();
        $index = 1;
        while ($ciudad = mysql_fetch_assoc($ciudades)) {
            $arrayCiudades[$index]['nombre'] = $ciudad['nombre'];
            $arrayCiudades[$index]['longitud'] = $ciudad['longitud'];
            $arrayCiudades[$index]['latitud'] = $ciudad['latitud'];
            $arrayCiudades[$index]['km'] = $ciudad['km'];
            $index ++;
        }
        if ($ambiente != 0) {
            $log .= "---------------------------\Ciudades:\n";
            $log .= print_r($arrayCiudades, true);
            $log .= "\n";
        }
        $cantidad_ciudades = count($arrayCiudades);

        if ($ciudad_indice >= $cantidad_ciudades) {
            $ciudad_indice = 1;
        } else {
            $ciudad_indice++;
        }

        mysql_query("UPDATE bots SET ciudad_indice = $ciudad_indice WHERE id = '{$bot['id']}'");

        $geo = $arrayCiudades[$ciudad_indice]['longitud']. ',' .$arrayCiudades[$ciudad_indice]['latitud']. ',' .$arrayCiudades[$ciudad_indice]['km'] . 'km';
        if ($ambiente != 0) {
            $log .= "--------------------\nGeo Punto: ";
            $log .= $geo . "\n";
        }

        $encontrados = array();
        $seguir = true;
        $recorrido = true;

        while ($recorrido == true) {

            if ($palabra_indice > $cantidad_palabras) {
                $palabra_indice = 1;
                $recorrido = false;
            }
        
            $query_tw_palabra = $arrayPalabras[$palabra_indice]['palabra'];
            // Add: funcionalidad mensajes
            $mensaje_palabra = $arrayPalabras[$palabra_indice]['mensaje'];
            
            $pagina = 1;
            $seguir = true;
            
            while ($seguir == true) {
                try {
                    if ($ambiente != 0) {
                        $log .= "-----------------\nPagina: " . $pagina . "\n";
                    }
                    $buscados = $twitter->search($query_tw_palabra, null, null, 100, $pagina, null, null, $geo, true, null);

                    if ($ambiente != 0) {
                        $log .= "Palabra Indice: " . $palabra_indice . "\n";
                        $log .=  "Query TW: " . $query_tw_palabra . "\n";
                    }

                    if (!empty($buscados['results'])) {
                        foreach ($buscados['results'] as $usuarios) {
                            try {
                                $qry = mysql_query("SELECT * FROM tweets WHERE bot_id = '{$bot['id']}' AND tw_usuario_id = '{$usuarios['from_user_id']}'");

                                $continuar = true;
                                while($row = mysql_fetch_assoc($qry)) {
                                    $continuar = false;
                                    mysql_query("UPDATE tweets SET tw_tweet_id = '{$usuarios['id']}' WHERE id = '{$row['id']}';");
                                }

                                if ($continuar) {
                                    $qry = mysql_query("SELECT * FROM tweets WHERE tw_tweet_id = '{$usuarios['id']}'");

                                    while($row = mysql_fetch_assoc($qry)) {
                                        $continuar = false;
                                    }
                                }

                                if ($continuar) {


                                    $coincidencia = true;

        //                            foreach ($secundarias as $palabra) {
        //                                if (strpos($usuarios['text'], $palabra) != false) {
        //                                    $coincidencia = true;
        //                                }
        //                            }

                                    if ($coincidencia) {

                                        if (!isset($usuarios['location'])) {
                                            $usuarios['location'] = "";
                                        }

                                        $query_insert = "INSERT INTO tweets (bot_id, tw_usuario_id, estado, tw_tweet_id, tw_location, tw_text, tw_created_at, tw_usuario, created_at, updated_at, palabra, ciudad, mensaje_enviado) VALUES ('" . $bot['id'] . "', '" . $usuarios['from_user_id'] . "', '0', '" . $usuarios['id'] . "', '" . $usuarios['location'] . "', '" . $usuarios['text'] . "', '" . $usuarios['created_at'] . "', '" . $usuarios['from_user'] . "', '" .  date("Y-m-d H:i:s") . "', '" . date("Y-m-d H:i:s") . "', '". $query_tw_palabra ."', '". $arrayCiudades[$ciudad_indice]['nombre'] ."', 0);";
                                        if ($ambiente != 0) {
                                            $log .= "--------------------\nQuery Insert: " . $query_insert . "\n";
                                        }

                                        mysql_query($query_insert);
                                        $id = mysql_insert_id();

                                        try {
                                            $seguido = $twitter->friendshipsExists($usuarios['from_user'], $bot['tw_cuenta']);
                                            if ($ambiente != 0) {
                                                $log .= "Seguido: " . $seguido . "\n";
                                            }
                                            if ($seguido != 1) {
                                                
                                                $siguiendo = $twitter->friendshipsExists($bot['tw_cuenta'], $usuarios['from_user']);

                                                // Add: Versión Plus

                                                if ($plus == true) {
                                                     // Add: funcionalidad mensajes
                                                    if ($mensaje_palabra != "") {
                                                        $twitter->statusesUpdate('@' . $usuarios['from_user'] . ' ' . $mensaje_palabra);
                                                    } elseif ($mensaje_bot != "") {
                                                        $twitter->statusesUpdate('@' . $usuarios['from_user'] . ' ' . $mensaje_bot);
                                                    }
                                                }

                                                if ($siguiendo != 1) {
                                                    $twitter->friendshipsCreate($usuarios['from_user_id']);
                                                    mysql_query("UPDATE tweets SET estado = 1 WHERE id = $id");
                                                    $qry_cont = mysql_query("SELECT * FROM bots WHERE id = '{$bot['id']}'");
                                                    while ($bot_cont = mysql_fetch_assoc($qry_cont)) {
                                                        $contador = $bot_cont['siguiendo'];
                                                    }
                                                    $contador++;
                                                    mysql_query("UPDATE bots SET siguiendo = '$contador' WHERE id = '{$bot['id']}'");
                                                    $cantidad_seguidos++;
                                                    $encontrados[] = $usuarios;

                                                    $cantidad_seguir++;
                                                    if ($ambiente != 0) {
                                                        $log .= "---------------------------------\n";
                                                        $log .= "Cantidad a seguir: " . $cantidad_seguir . "\n";
                                                        $log .= "Maximo a seguir: " . $max_seguir . "\n";
                                                        $log .= "---------------------------------\n";
                                                    }
                                                    if ($cantidad_seguir >= $max_seguir) {
                                                        $seguir = false;
                                                        $recorrido = false;
                                                        if ($ambiente != 0) {
                                                            $log .= "FINALIZA POR MAXIMO\n---------------------------------\n";
                                                        }
                                                        break;
                                                    }
                                                }

                                            } else {
                                                mysql_query("UPDATE tweets SET estado = 2 WHERE id = $id");
                                                if ($ambiente != 0) {
                                                    $log .= "---------------------------------\nYa está siguiendo la cuenta\n";
                                                    $log .= "---------------------------------\n";
                                                    $log .= "Cantidad a seguir: " . $cantidad_seguir . "\n";
                                                    $log .= "Maximo a seguir: " . $max_seguir . "\n";
                                                    $log .= "---------------------------------\n";
                                                }
                                            }
                                        } catch (Exception $e) {
                                            if ($e->getMessage() == 'You do not have permission to retrieve following status for both specified users.') {
                                                mysql_query("UPDATE tweets SET estado = 3 WHERE id = $id");
                                            } else if ($e->getMessage() == 'Rate limit exceeded. Clients may not make more than 350 requests per hour.') {
                                                mysql_query("DELETE FROM tweets WHERE id = $id;");
                                                if ($ambiente != 0) {
                                                    $log .= "\n ERROR: " . $e . "\n\n";
                                                }
                                                $seguir = false;
                                                $recorrido = false;
                                                break;
                                            }else {
                                                if ($ambiente != 0) {
                                                    $log .= "\n ERROR: " . $e . "\n\n";
                                                }
                                                $seguir = false;
                                                $recorrido = false;
                                                break;
                                            }
                                        }
                                    }
                                }
                            } catch (Exception $e) {
                                if ($ambiente != 0) {
                                    $log .= "\n ERROR: " . $e . "\n\n";
                                }
                                $seguir = false;
                                $recorrido = false;
                                break;
                            }
                        }
                    } else {
                        if ($ambiente != 0) {
                            $log .= "Resultado Vacio\n";
                        }
                        $seguir = false;
                        break;
                    }
        
                    if ($ambiente != 0) {
                        $log .= "\n";
                    }

                    $pagina++;

                    if ($pagina == 11) {
                        $seguir = false;
                    }

                } catch (Exception $e) {
                    if ($ambiente != 0) {
                        $log .= "\n ERROR: " . $e . "\n\n";
                    }
                    $seguir = false;
                    $recorrido = false;
                    break;
                }
            }

            $palabra_indice++;
        }

        if ($ambiente != 0) {
            $log .= "-------------------\nCantidad Seguidos: " . $cantidad_seguidos . "\n";
        }
        //mysql_query("UPDATE bots SET siguiendo = $cantidad_seguidos WHERE id = '{$bot['id']}';");
        mysql_query("UPDATE bots SET palabra_indice = $palabra_indice WHERE id = '{$bot['id']}';");
    }
}

if ($ambiente != 0) {
    if ($log != '') {
        $log .= "-----------------------------------------------\n";
        //$log .= "\n\nEncontrados:\n" . print_r($encontrados, true);

        $log .= "\n\nFin del proceso: ";
        $log .= date("D, d/m/Y, H:i:s")."\n";

        $log = $log."\nGuardando log...\n\n\n------------------------------------------------------------------------------------------------\n------------------------------------------------------------------------------------------------\n\n\n";

        //exec("rm " . $directorio . "logfollow.txt");

        $fp=fopen($directorio . "logfollow.txt","a");

        fwrite($fp,$log);
        fclose($fp) ;

    }
}


function compararFechas($primera, $segunda)
 {
  $valoresPrimera = explode ("/", $primera);   
  $valoresSegunda = explode ("/", $segunda); 

  $diaPrimera    = $valoresPrimera[0];  
  $mesPrimera  = $valoresPrimera[1];  
  $anyoPrimera   = $valoresPrimera[2]; 

  $diaSegunda   = $valoresSegunda[0];  
  $mesSegunda = $valoresSegunda[1];  
  $anyoSegunda  = $valoresSegunda[2];

  $diasPrimeraJuliano = gregoriantojd($mesPrimera, $diaPrimera, $anyoPrimera);  
  $diasSegundaJuliano = gregoriantojd($mesSegunda, $diaSegunda, $anyoSegunda);     

  if(!checkdate($mesPrimera, $diaPrimera, $anyoPrimera)){
    // "La fecha ".$primera." no es v&aacute;lida";
    return 0;
  }elseif(!checkdate($mesSegunda, $diaSegunda, $anyoSegunda)){
    // "La fecha ".$segunda." no es v&aacute;lida";
    return 0;
  }else{
    return  $diasPrimeraJuliano - $diasSegundaJuliano;
  } 

}

?> 
