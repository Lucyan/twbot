<?php

// Configuración Base de Datos
$con = mysql_connect('localhost', 'root', 'root');
mysql_select_db('projects_twbot', $con);
mysql_set_charset('utf8', $con);

// Configuración Bot
$key = 'rzXrHNkh5KnEWdW6VuDwhA';
$secret = 'Ge7BpLLw3rh0JlXSWHaIklmfy4WhIYKaoyhPIF0XU';

// Timezone
//date_default_timezone_set('Etc/GMT-4');