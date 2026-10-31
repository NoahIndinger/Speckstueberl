<?php
/**
 * VORLAGE: Diese Datei auf dem Webserver nach "config.local.php" kopieren
 * und die Zugangsdaten des Postfachs schuster@indinger.email eintragen.
 *
 * config.local.php wird NICHT auf GitHub hochgeladen (steht in .gitignore),
 * damit das Passwort geheim bleibt.
 *
 * Die SMTP-Daten (Server, Port) findest du beim Mail-Anbieter von indinger.email
 * unter "Postausgangsserver" / "SMTP" bzw. in der Anleitung für Outlook/Thunderbird.
 */
return [
    'smtp' => [
        'host'     => 'smtp.DEIN-MAILANBIETER.at',
        'port'     => 587,
        'verschluesselung' => 'tls',
        'benutzer' => 'schuster@indinger.email',
        'passwort' => 'HIER-DAS-PASSWORT',
    ],
];
