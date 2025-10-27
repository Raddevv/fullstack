<?php
require_once '4everToolsDB.php';

$voornaam = 'aiden';
$achternaam = 'admin';
$wachtwoord = 'admin';
$hash = password_hash($wachtwoord, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO klant (voornaam, achternaam, wachtwoord, admin) 
                      SELECT ?, ?, ?, 1 
                      WHERE NOT EXISTS (
                          SELECT 1 FROM klant WHERE voornaam=? AND achternaam=?
                      )");
$stmt->execute([$voornaam, $achternaam, $hash, $voornaam, $achternaam]);

echo "Admin user created: $voornaam $achternaam";
