<h1>Zend Framework2 Starter</h1>

Zend Framework 2 Skeleton Applikation + LDAP/DB Auth + Optionen/Rechte-Management + Layout

<h2>Funktionen</h2>
<ul>
	<li>Authentifizierung mittels LDAP oder Datenbank</li>
	<li>fertiges Layout zum losstarten</li>
	<li>Optionen lassen sich über Oberfläche verändern, im Code leicht abrufen und hinzufügen(gespeichert in Datenbank)</li>
	<li>Rechtemanagement über Rollen: Oberfläche zur Verwaltung, neue "Rechte" über neue Spalte in Datenbank</li>
	<li>Navigation lässt sich über die Zend Navigation Config leicht erweitern</li>
</ul>

<h2>Installation</h2>
<ol>
	<li>Code herunterladen und auf Webserver kopieren</li>
	<li>Vhost einrichten</li>
	<li>MySQL Datenbank erstellen</li>
	<li>MySQL Datei data/database.sql in Datenbank importieren</li>
	<li>Datenbank-Daten in Konfigurationsdatei config/autolaod/local.php eintragen(siehe local.php.dist)</li>
</ol>

User mit Typ "ldap" können sich über LDAP authentifizieren(LDAP-Konfiguration unter config/ldap-config.ini)


