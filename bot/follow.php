#!/usr/bin/php  
<?php 
/*
*   follow.php
*   Realiza el trabajo de seguir personas vía la api de twitter a los bots
*
*/

// Archivos de configuración y api
require('config.php');
require('ambiente.php');
include('twitter.php');

// Obtención de limite de mensajes por hora (definido por admin)
$query = mysql_query("SELECT * FROM variables WHERE variables.key = 'limite_seguir';");
$variable = mysql_fetch_assoc($query);

// Limite de mensajes por hora
$max_por_hora = $variable['value'];

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

// Recorre el array de bots activos
while ($bot = mysql_fetch_assoc($bots)) {
    // Se obtiene la última fecha de renovación del bot
    $fecha_renovacion = date("d/m/Y",strtotime($bot['fecha_renovacion']));
    // Se obtiene la fecha actual
    $fecha_actual = date("d/m/Y",time());

    // Seteo el número de mensajes enviados por el bot en 0
    $mensajes_enviados = 0;

    // Compara las fechas
    if (compararFechas($fecha_actual, $fecha_renovacion) > 30) {
        // Si el bot ya paso mas de 30 días sin renovar, se marca como inactivo y no se realiza proceso para este
        mysql_query("UPDATE bots SET estado = 0 WHERE id = '{$bot['id']}';");
    } else {
        // Si el bot tiene menos de 30 días de renovación, se ejecuta proceso

        // Se setean keys de twitter
        $twitter->setOAuthToken($bot['tw_token']);
        $twitter->setOAuthTokenSecret($bot['tw_secret']);

        // Se obtiene el indice de las ciudades (última ciudad en la que se buscó)
        $ciudad_indice = $bot['ciudad_indice'];

        // Se obtiene el indice de las palabras (última palabra en la que se buscó)
        $palabra_indice = $bot['palabra_indice'];

        // Se obtiene la cantidad que el bot ha seguido
        $cantidad_seguidos = $bot['siguiendo'];

        // Se obtiene la cantidad de máximos a seguir por hora
        $max_seguir = $bot['cantidad_seguir'];

        // Se setea indice de seguir en 0
        $cantidad_seguir = 0;

        // Add: funcionalidad mensajes
        $mensaje_bot = $bot['frase_al_seguir'];

        // Add: Versión Plus
        $plus = $bot['plus'];

        // Se obtiene arrays de palabras asociadas al bot
        $palabras = mysql_query("SELECT * FROM palabras WHERE bot_id = '{$bot['id']}';");

        // se genera array con las palabras y los mensajes de cada palabra
        $arrayPalabras = array(); 
        $index = 1;
        while ($palabra = mysql_fetch_assoc($palabras)) {
            $arrayPalabras[$index]['palabra'] = $palabra['palabra'];
            // Add: funcionalidad mensajes
            $arrayPalabras[$index]['mensaje'] = $palabra['frase'];
            $index ++;
        }

        // Se verifica la cantidad de palabras en el array
        $cantidad_palabras = count($arrayPalabras);

        // Se setea la cantidad de palabras asociadas al bot
        mysql_query("UPDATE bots SET palabra_maximo = $cantidad_palabras WHERE id = '{$bot['id']}';");
        if ($ambiente != 0) {
            $log .= "---------------------------\nPalabras\n";
            $log .= "Query: SELECT * FROM palabras WHERE bot_id = '" . $bot['id'] . "';\n";
            $log .= print_r($arrayPalabras, true);
            $log .= "\n";
        }

        // Se obtiene array de ciudades asociadas al bot
        $ciudades = mysql_query("SELECT * FROM ciudads AS ciu JOIN bot_ciudads AS bot ON ciu.id = bot.ciudad_id WHERE bot.bot_id = '{$bot['id']}';");


        // Se genera array con las ciudades asociadas al bot y su configuración
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

        // Setea la cantidad de ciudades del bot
        $cantidad_ciudades = count($arrayCiudades);

        // verifica si la última ciudad en la que se buscó es igual a la cantidad de ciudades asociadas al bot
        if ($ciudad_indice >= $cantidad_ciudades) {
            // si es mayor o igual, se setea el indice en 1 (primera ciudad)
            $ciudad_indice = 1;
        } else {
            // sinó, se setea indice a la ciudad siguiente en la que buscar
            $ciudad_indice++;
        }


        // Se setea última ciudad en la que se buscó en la base de datos
        mysql_query("UPDATE bots SET ciudad_indice = $ciudad_indice WHERE id = '{$bot['id']}'");

        // se genera punto georeferencial de la ciudad en la que b uscar
        $geo = $arrayCiudades[$ciudad_indice]['longitud']. ',' .$arrayCiudades[$ciudad_indice]['latitud']. ',' .$arrayCiudades[$ciudad_indice]['km'] . 'km';
        if ($ambiente != 0) {
            $log .= "--------------------\nGeo Punto: ";
            $log .= $geo . "\n";
        }

        // Se inicia array de tweets encontrados
        $encontrados = array();

        // se inician controladoes del proceso, para manjerar errores donde se tenga que deneter todo el proceso
        $seguir = true;
        $recorrido = true;

        // Se inicia proceso de recorrido por las palabras
        while ($recorrido == true) {

            // verifica si la última palabra en la que se buscó es la última palabra asociada al bot
            if ($palabra_indice > $cantidad_palabras) {
                // Si es la última palabra, se setea el indice en 1 y se detiene el proceso
                $palabra_indice = 1;
                $recorrido = false;
            }
            
            // se ontiene la palabra en la cual buscar
            $query_tw_palabra = $arrayPalabras[$palabra_indice]['palabra'];
            // Add: funcionalidad mensajes
            $mensaje_palabra = $arrayPalabras[$palabra_indice]['mensaje'];
            
            // Se setea paginador de la api de twitter
            $pagina = 1;
            // se setea controlador para seguir proceso
            $seguir = true;
            
            while ($seguir == true) {
                // controla errores de la api
                try {
                    if ($ambiente != 0) {
                        $log .= "-----------------\nPagina: " . $pagina . "\n";
                    }

                    // utiliza la api de twitter para buscar la palabra seteada, en la ciudad seteada y el paginador correspondiente
                    // estos resultados quedan en el array $buscados
                    $buscados = $twitter->search($query_tw_palabra, null, null, 100, $pagina, null, null, $geo, true, null);

                    if ($ambiente != 0) {
                        $log .= "Palabra Indice: " . $palabra_indice . "\n";
                        $log .=  "Query TW: " . $query_tw_palabra . "\n";
                    }

                    // Se verifica si se obtenieron datos de twitter
                    if (!empty($buscados['results'])) {
                        // Si el array no está vacio, se inicia proceso

                        // Se recorre array obtenido de twitter
                        foreach ($buscados['results'] as $usuarios) {
                            try {

                                // Verifica si el usuario del tweet consultado ya está registrado el bot
                                $qry = mysql_query("SELECT * FROM tweets WHERE bot_id = '{$bot['id']}' AND tw_usuario_id = '{$usuarios['from_user_id']}'");

                                // Se setea controlador de proceso en verdadero
                                $continuar = true;

                                // Se verifica si se encontraron datos del usuario en la base de datos
                                while($row = mysql_fetch_assoc($qry)) {
                                    // Si el usuario ya existe en la base de datos y está asociado al bot, se setea controlador de proceso en false
                                    $continuar = false;

                                    // Se setea el correcto id del tweet
                                    mysql_query("UPDATE tweets SET tw_tweet_id = '{$usuarios['id']}' WHERE id = '{$row['id']}';");
                                }

                                // Se verifica el controlador de recorrido
                                if ($continuar) {
                                    // Si el controlador es verdadero

                                    // se verifica si este tweet ya había sido registrado
                                    $qry = mysql_query("SELECT * FROM tweets WHERE tw_tweet_id = '{$usuarios['id']}'");

                                    while($row = mysql_fetch_assoc($qry)) {
                                        // Si se encuentra el tweet en la base de datos, se setea controlador de proceso en false
                                        $continuar = false;
                                    }
                                }

                                // Se verifica controlador de proceso
                                if ($continuar) {
                                    // Si el controlador es verdadero, se inicia proceso para seguir al usuario

                                    // si la localización del usuario obtenido en el tweet es nula, se setea en blanco para no provocar error en base de datos
                                    if (!isset($usuarios['location'])) {
                                        $usuarios['location'] = "";
                                    }

                                    // Se inserta el tweet encontrado en la abse de datos
                                    $query_insert = "INSERT INTO tweets (bot_id, tw_usuario_id, estado, tw_tweet_id, tw_location, tw_text, tw_created_at, tw_usuario, created_at, updated_at, palabra, ciudad, mensaje_enviado) VALUES ('" . $bot['id'] . "', '" . $usuarios['from_user_id'] . "', '0', '" . $usuarios['id'] . "', '" . $usuarios['location'] . "', '" . $usuarios['text'] . "', '" . $usuarios['created_at'] . "', '" . $usuarios['from_user'] . "', '" .  date("Y-m-d H:i:s") . "', '" . date("Y-m-d H:i:s") . "', '". $query_tw_palabra ."', '". $arrayCiudades[$ciudad_indice]['nombre'] ."', 0);";
                                    if ($ambiente != 0) {
                                        $log .= "--------------------\nQuery Insert: " . $query_insert . "\n";
                                    }

                                    mysql_query($query_insert);

                                    // se obtiene el id del nuevo registro
                                    $id = mysql_insert_id();


                                    // controla errores en la api
                                    try {

                                        // Se verifica si el usuario sigue al bot
                                        $seguido = $twitter->friendshipsExists($usuarios['from_user'], $bot['tw_cuenta']);
                                        if ($ambiente != 0) {
                                            $log .= "Seguido: " . $seguido . "\n";
                                        }

                                        // Si el usuario no sigue al bot, se continua proceso
                                        if ($seguido != 1) {
                                            
                                            // Se verifica si el bot ya sigue al usuario
                                            $siguiendo = $twitter->friendshipsExists($bot['tw_cuenta'], $usuarios['from_user']);

                                            // Si el bot no sigue al usuario, se continua proceso
                                            if ($siguiendo != 1) {

                                                // Se crea amistad con usuario (follow)
                                                $twitter->friendshipsCreate($usuarios['from_user_id']);

                                                // Se setea estado del tweet en la base de datos en 1 (se sigue)
                                                mysql_query("UPDATE tweets SET estado = 1 WHERE id = $id");

                                                // Se obtiene la cantidad de personas que el bot sigue
                                                $qry_cont = mysql_query("SELECT * FROM bots WHERE id = '{$bot['id']}'");
                                                while ($bot_cont = mysql_fetch_assoc($qry_cont)) {
                                                    $contador = $bot_cont['siguiendo'];
                                                }

                                                // Se suma el usuario srecien seguido a la estadistica
                                                $contador++;
                                                mysql_query("UPDATE bots SET siguiendo = '$contador' WHERE id = '{$bot['id']}'");

                                                // Se aumenta el indice de seguidos en el proceso
                                                $cantidad_seguidos++;

                                                // Se suma el tweet al array de encontrados
                                                $encontrados[] = $usuarios;

                                                 // Add: Versión Plus -- Envía mensaje al usuario recien seguido, dependiendo si el bot es plus y si tiene mensaje en la palabra o en el bot (palabra tiene prioridad sobre el bot)
                                                if ($plus == true) {
                                                    // Se verifica si se ha enviado el maximo de mensajes por hora
                                                    if ($mensajes_enviados < $max_por_hora) {
                                                        // Si no se han enviado los mensajes, se procede a enviar
                                                        // Add: funcionalidad mensajes
                                                        if ($mensaje_palabra != "") {
                                                            $twitter->statusesUpdate('@' . $usuarios['from_user'] . ' ' . $mensaje_palabra);
                                                            // Se aumenta el contador de mensajes enviados
                                                            $mensajes_enviados++;
                                                        } elseif ($mensaje_bot != "") {
                                                            $twitter->statusesUpdate('@' . $usuarios['from_user'] . ' ' . $mensaje_bot);
                                                            // Se aumenta el contador de mensajes enviados
                                                            $mensajes_enviados++;
                                                        }
                                                    }
                                                }

                                                // Se aumenta la cantidad que ha seguido el bot en este proceso
                                                $cantidad_seguir++;
                                                if ($ambiente != 0) {
                                                    $log .= "---------------------------------\n";
                                                    $log .= "Cantidad a seguir: " . $cantidad_seguir . "\n";
                                                    $log .= "Maximo a seguir: " . $max_seguir . "\n";
                                                    $log .= "---------------------------------\n";
                                                }

                                                // Si el bot ha seguido al maximo que puede segui por hora, se detiene proceso
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

                                            // Si el usuario ya sigue al bot, se marca el tweet en estado 2 (sigue al bot)
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
                                        // Captura error en la api de twitter
                                        if ($e->getMessage() == 'You do not have permission to retrieve following status for both specified users.') {
                                            // Si el error es por que el usuario no tiene permitido verificar si es amigo del bot, se marca estado del tweet en 3 (desconocido)
                                            mysql_query("UPDATE tweets SET estado = 3 WHERE id = $id");
                                        } else if ($e->getMessage() == 'Rate limit exceeded. Clients may not make more than 350 requests per hour.') {
                                            // Si el error es por que se llegó al máximo de request por hora, se elimina tweet de la base de datos y se detiene el proceso
                                            mysql_query("DELETE FROM tweets WHERE id = $id;");
                                            if ($ambiente != 0) {
                                                $log .= "\n ERROR: " . $e . "\n\n";
                                            }
                                            $seguir = false;
                                            $recorrido = false;
                                            break;
                                        }else {
                                            // Cualquier otro error de la api, se detiene el proceso por seguridad
                                            if ($ambiente != 0) {
                                                $log .= "\n ERROR: " . $e . "\n\n";
                                            }
                                            $seguir = false;
                                            $recorrido = false;
                                            break;
                                        }
                                    }
                                }
                            } catch (Exception $e) {
                                // Cualquier otro error de la api, se detiene el proceso por seguridad
                                if ($ambiente != 0) {
                                    $log .= "\n ERROR: " . $e . "\n\n";
                                }
                                $seguir = false;
                                $recorrido = false;
                                break;
                            }
                        }
                    } else {
                        // Si no se encontraron resultados en la busqueda en tweeter, se detiene proceso
                        if ($ambiente != 0) {
                            $log .= "Resultado Vacio\n";
                        }
                        $seguir = false;
                        break;
                    }
        
                    if ($ambiente != 0) {
                        $log .= "\n";
                    }

                    // Se aumenta paginador de la api
                    $pagina++;

                    // por seguridad, solo se busca hasta la página 11
                    if ($pagina == 11) {
                        $seguir = false;
                    }

                } catch (Exception $e) {
                    // Cualquier otro error de la api, se detiene el proceso por seguridad
                    if ($ambiente != 0) {
                        $log .= "\n ERROR: " . $e . "\n\n";
                    }
                    $seguir = false;
                    $recorrido = false;
                    break;
                }
            }

            // Se aumenta el indice de la palabra en cual buscar
            $palabra_indice++;
        }

        if ($ambiente != 0) {
            $log .= "-------------------\nCantidad Seguidos: " . $cantidad_seguidos . "\n";
        }

        // se guarda el indice de la última palabra en la cual se buscó
        mysql_query("UPDATE bots SET palabra_indice = $palabra_indice WHERE id = '{$bot['id']}';");
    }
}

// Guarda log
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


// Funcion que compara dos fechas e indica cuantos días de diferencia tienen.
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
