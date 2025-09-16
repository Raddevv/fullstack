## setup (les 1)

- beoordelings criteria

  - user stories zijn uitgewerkt tot een werkene applicatie
  - wordt gelet op veiligheid
  - wordt gelet op (netheid)
    - best practices
    - consistentie
    - structuur
    - naamgeving
  - ## database

  - proces
    - terugkijken/reflectie
    - presenteren
    - planning scrum taken
      - taken bij user story
      - git commits

- doelen

  - je kan een git repo clonen
  - je kan docker installeren
  - je kan een terminal gebruiken voor commandos
  - je kan docker compose uitvoeren
  - je kan een docker container inspecteren (via docker app/client)
    - je herkent de logs
    - je weet wat bind mounts zijn
    - je kan commando's via exec uitvoeren zoals ls,pwd
    - je kan het filesysteem bekijken in de container
    - je kan de php files vinden
    - ( ENVironment variabelen (path, WW, windos en container) )
  - je kan de juiste php bestanden wijzigen
  - je kan de resultaten in een browser zien

  - toegang tot classroom: je verkent PHP als programmeer taal

### Stappen

- git clone deze repo
- docker compose up
- docker client bekijken:
  - logs
  - bind mounts
  - exec
  - files
- in docker 'exec' tab :
  - ls
  - pwd
  - php
  - php test.php
  - php index.php
- in browser:
  - http://localhost:80/index.php
  - localhost
  - http://localhost:8080

## De taal: Les 2

Laat studenten in 20 minuten (in groepjes) iets voorbereiden wat ze kunnen delen met de klas in 5 minuten
Voorbeelden:

- arrays
- HTML Forms vs PHP
- veelgebruikte PHP functies

## Les 3

- PHP in 100 seconds https://youtube.com/watch?v=a7_WFUlFS94

- Forms
- HTTP
- https://www.khanacademy.org/computing/computers-and-internet/xcae6f4a7ff015e7d:the-internet/xcae6f4a7ff015e7d:web-protocols/a/hypertext-transfer-protocol-http
- Formulier submit: https://www.w3schools.com/html/html_forms.asp
- HTTP anatomy: https://www.youtube.com/watch?v=Nj8pGsBvcmo
  - inspect in browser
    - type (GET/POST)
    - payload
    - response

## Les 4

recap: wat geleerd?
-docker
-taal php
-classroom
-http
-http post/get

Voorbeeld php (games1,games2) met DB connectie (plain en prep.statement en include)

# User stories:

- Als gebruiker wil ik kunnen inloggen zodat ik de applicatie kan gebruiken

- Als gebruiker wil ik kunnen inloggen waarna ik een pagina zie die ik alleen kan zien als ik ingelogd ben

- Als gebruiker wil ik kunnen zien welke gebruikers er zijn zodat ik een overzicht daar van heb en ze vanuit daar kan aanpassen/verwijderen
  - UI exact ?
    - moet 'modern' eruit zijn
    - wat als er geen gebruikers zijn?
    - wat als er iets fout gaat ?
    - wat als er duizenden gebruikers zijn? hoe geef je dat weer?
      - wil je ergens op kunnen zoeken of filteren?
  - uit welke velden bestaat een gebruiker?
  - technisch: moet 'veilig' zijn
  - technisch: moet snel zijn: niet meer dan 1 seconde wachten op resultaat


- Als gebruiker wil ik gebruikers kunnen toevoegen zodat zij ook de applicatie kunnen gebruiken

- Als gebruiker wil ik gebruikers kunnen verwijderen zodat zij niet meer kunnen inloggen

- Als gebruiker wil ik gebruikers kunnen aanpassen zodat ik hun naam en inloggegevens kan veranderen

- Als gebruiker wil ik dat het wachtwoord veilig wordt opgeslagen

-tweetallen: maak wireframes : maak duidelijk waar elke pagina naar navigeert

-DB welke tabellen en welke kolommen
-Wachtwoord ?
-HTTP(S)

Login pagina + CRUD

Discussie vorm:
Wireframes

## Les 5

FLOW: pagina -> POST/GET -> haalt waardes eruit -> validatie/escapen -> maakt een query -> interperteert resultaten -> nieuw scherm

$GET $POST $SESSION $COOKIE

## Security
