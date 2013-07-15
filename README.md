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

<h2>Hinweise zur Benutzung</h2>
<ul>
	<li>User mit Typ "ldap" können sich über LDAP authentifizieren(LDAP-Konfiguration unter config/ldap-config.ini)</li>
	<li>Es existiert ein vordefinierter User mit Login "admin" und Passwort "admin"</li>
</ul>

<h2>Konfiguration</h2>
<h3>Hinzufügen neuer Berechtigungen</h3>
<ol>
	<li>In Tabelle der Datenbank eine neue Spalte mit dem Namen des Rechts hinzufügen</li>
	<li>In Klasse module/Application/src/Model/Role.php eine neue Eigenschaft(Property) mit dem selben Namen wie der Spalte hinzufügen(siehe zb. admin-Recht)</li>
	<li>In der View module/Application/view/application/admin/role.php in der jqGrid-Funktion muss in den Settings bei ColNames die Bezeichnung der Spalte hinzugefügt werden</li>
	<li>In der View module/Application/view/application/admin/role.php in der jqGrid-Funktion muss auch eine neue Zeile für das ColModel hinzugefügt werden. Zeile von admin kann übernommen werden.(mit Änderungen von name und index auf Name der neuen Spalte) Für Beschreibung der Optionen <a href="http://www.trirand.com/jqgridwiki/doku.php?id=wiki:colmodel_options">siehe Dokumention jqGrid</a></li>
</ol>

<h3>Abfragen von Berechtigungen</h3>
<h4>Navigation</h4>
In der Navigationskonfiguration kann eine neue Eintrag "ressource" mit dem Wert "mvc:rechtxy"hinzugefügt werden. Wenn der User das Recht hat, sieht er den Menüpunkt ansonsten nicht.
Also zB.: 'resource' => "mvc:admin",

<h4>Im Code</h4>
Um im Code ein Recht abzufragen, muss die Klasse RoleTable instanziert werden und anschließend die Methode isAllowed($role_id) aufgerufen werden.




