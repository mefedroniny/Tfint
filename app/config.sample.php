<?php
/*
 * Konfigurace webu TF International.
 * build.py z tohoto souboru vytvoří app/config.php s náhodnými klíči (jen pokud ještě neexistuje).
 * config.php NIKDY nesdílejte ani nenahrávejte do veřejného repozitáře.
 */
return [
    // Veřejná adresa webu (pro odkazy v e-mailech)
    'base_url'     => 'https://www.tfint.cz',

    // Kam posílat upozornění na novou poptávku. Prázdné = e-mail se neposílá (poptávky jsou jen v administraci).
    'notify_email' => '',
    // Odesílatel upozornění — musí být adresa na vaší doméně, jinak e-maily skončí ve spamu.
    'mail_from'    => 'web@tfint.cz',

    // Jednorázový klíč pro založení prvního administrátora na /admin/ (po založení už není potřeba).
    'setup_key'    => '__SETUP_KEY__',

    // Tajný klíč pro podpisy (formulář, anonymizace IP). Neměňte po spuštění.
    'secret'       => '__SECRET__',

    // Umístění databáze. Pokud to hosting dovolí, dejte ji MIMO veřejnou složku, např. __DIR__ . '/../../data/tfint.sqlite'
    'db_path'      => __DIR__ . '/../data/tfint.sqlite',
];
