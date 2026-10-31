<?php
/**
 * Einstellungen für das Anfrageformular (kontakt.php).
 * Diese Datei wird nur eingebunden, direkt aufgerufen gibt sie nichts aus.
 */
return [
    // An diese Adresse gehen die Anfragen aus dem Kontaktformular.
    'empfaenger' => 'schuster@indinger.email',

    // Absender der Mail. Sollte eine Adresse der eigenen Domain sein,
    // sonst landen die Mails bei vielen Anbietern im Spam.
    'absender'      => 'website@muehlviertler-speckstueberl.at',
    'absender_name' => 'Speckstüberl Website',

    // Betreff-Präfix, z. B. "[Website] Anfrage: Feier / Buffet"
    'betreff_prefix' => '[Website]',

    // Spam-Schutz: höchstens so viele Anfragen pro Besucher in 10 Minuten.
    'max_pro_10min' => 5,
];
