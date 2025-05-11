# Laboration i Webbprogrammering

## Inledning

![Cyberspace](dall-e-cyberspace.jpg)
*Figur 1: Cyberspace, bilden är genererad med hjälp av DALL-E 3.*

### Översikt
Denna laboration fokuserar på att utveckla en webbapplikation för att skapa och hantera en inköpslista för hushållet. Applikationen ska interagera med användaren via webbläsarna Firefox eller Chrome.

### Uppgiftsbeskrivning
I denna laboration är det din och en eventuell labbpartners uppgift att:
- Välja lämpliga datatyper för datalagring.
- Namnge och utforma hjälpfunktioner.
- Utveckla lösningsmetoder och implementera dem.

**Viktigt**: Det är ett brott mot hederskodexen att söka hjälp från personer utanför labbteamet eller använda förbjudna artificiella intelligenser, såsom ChatGPT och GitHub Copilot, för att lösa uppgiften.

**Notera**: Denna uppgift testas inte på Kattis.

---

## Förberedelse och Installation

### Översikt
I denna laboration används PHP och SQLite för datalagring. Alternativt kan erfarna användare välja andra databaser, såsom PostgreSQL. PHP-version 7.4.3 är installerad på skolans datorer och förkonfigurerad med SQLite. För att minimera installationsproblem rekommenderas att använda skolans datorer.

### Steg för att konfigurera skolans datorer
Följ dessa steg för att sätta upp din arbetsmiljö på skolans Ubuntu-datorer:

1. **Logga in**: Använd en Ubuntu-dator i labbsalarna eller fjärrlogga in via SSH på servern `student-shell.sys.kth.se`.
2. **Skapa mapp**: Skapa en mapp i din hemkatalog för PHP-filer:
   ```bash
   mkdir ~/public_php
   ```
3. **Sätt rättigheter**: Begränsa åtkomst och ge webbservern läsrättigheter:
   ```bash
   fs sa ~/public_php system:anyuser none httpd-course read
   ```
   Om du har en labbpartner, ge hen läs- och skrivrättigheter:
   ```bash
   fs sa ~/public_php [labbpartner_användarnamn] rlidw
   ```
4. **Skapa databas-mapp**: Skapa en mapp för databasfiler:
   ```bash
   mkdir ~/public_php/database_files
   ```
5. **Sätt databasrättigheter**: Ge PHP-skript åtkomst till databasfilerna:
   ```bash
   fs sa ~/public_php/database_files httpd-course rlid
   ```

### Placering av filer
- **PHP-filer**: Placera i `~/public_php`.
- **Databasfiler**: Placera i `~/public_php/database_files`.

### Köra skript
Dina PHP-program kan köras via URL:en:
```
https://course-dd1366.eecs.kth.se/[ditt_användarnamn]/[filnamn].php
```
Exempel: För användaren `vahid` och filen `welcome.php`:
```
https://course-dd1366.eecs.kth.se/vahid/welcome.php
```

### Hämta och testa skript
1. Hämta filerna `welcome.php` och `shopping_list.php` och placera dem i `~/public_php`.
2. Hämta databasfilen `products.db` och placera den i `~/public_php/database_files`.
3. Testa skripten via följande URL:er:
   ```
   https://course-dd1366.eecs.kth.se/[ditt_användarnamn]/welcome.php
   https://course-dd1366.eecs.kth.se/[ditt_användarnamn]/shopping_list.php
   ```

---

## Labbinnehåll och Mål

### Översikt
Webbapplikationen ska lista varor som är slut eller snart kommer att vara slut i hushållet, baserat på en analys av användarens konsumtionsvanor.

### Generellt förfarande
1. Användaren loggar in på webbapplikationen.
2. Efter inloggning visas inköpslistan med automatiska förslag baserat på tidigare inköpsmönster.
3. Programmet analyserar historiska inköpsdata och genererar förslag baserat på genomsnittliga förbrukningsintervall per produkt.
4. Användaren kan manuellt lägga till varor som inte föreslås automatiskt.
5. Användaren granskar och modifierar listan, sedan klickar hen på "Spara" för att skapa en slutgiltig lista.
6. Den slutgiltiga listan lagras i databasen för bekräftelse.
7. På bekräftelsesidan kan användaren bekräfta inköp och lägga till impulsköp.
8. Programmet uppdaterar databasen med nya inköpsdatum för köpta varor.
9. Användaren loggar ut.

### Rekommenderade regler
Följande regler styr logiken för inköpsdatum:
1. Varor utan inköpsdatum i databasen har aldrig köpts och ska alltid inkluderas i inköpslistan.
2. När en vara köps uppdateras databasen med ett nytt inköpsdatum.
3. När en vara tas bort via webbgränssnittet raderas all relaterad data från databasen.

---

## Kravspecifikation

### Krav på webbläsare
Webbapplikationen måste fungera i Firefox och Chrome.

### Krav på databas
Datalagring ska ske i en SQL-databas.

### Nödvändiga sidor
Webbapplikationen ska inkludera följande sidor:
1. **Skapa konto**: Fält för användarnamn och lösenord.
2. **Inloggning**: Visas vid misslyckad inloggning med felmeddelande.
3. **Hantera varor**: Lägga till eller ta bort hushållsvaror i databasen.
4. **Inköpslista**: Skapa och modifiera inköpslistan.
5. **Bekräftelse**: Visa inköpslistan med knappar (t.ex. kryssrutor) för att markera köpta varor och en knapp för att avsluta shopping.
6. **Utloggning**: En utloggningsknapp på alla sidor utom inloggningssidan. Efter utloggning krävs ny inloggning.

### Säkerhetskrav

#### Databasintrång
Skydda mot SQL-injektion (se [OWASP SQL Injection](https://owasp.org/www-community/attacks/SQL_injection)). All indata från webbklienten måste valideras.

#### Databaskonsistens och integritet
Använd transaktionshantering för att undvika race conditions vid samtidig användning. Alternativ:
- Förbjud samtidig inloggning på samma konto (enklare, men med nackdelar, t.ex. begränsad flexibilitet).
- Tillåt flera användare att arbeta med varsin inköpslista, med korrekt hantering av samtidiga uppdateringar.

#### Lösenordshantering
Lösenord får inte lagras i klartext. Använd en hashfunktion (t.ex. SHA3-512) för att spara och jämföra lösenord.

### Ej examinerade säkerhetsaspekter
Sårbarheter som XSS hanteras i andra kurser, såsom datasäkerhet.

---

## Scenarier

### Scenario 1: Standardanvändning
1. Användaren loggar in och ser inköpslistan.
2. Programmet föreslår varor baserat på genomsnittliga inköpsintervall (t.ex. mjölk köps var 4:e dag, så den föreslås om nästa inköp är försenat).
3. Användaren kan manuellt lägga till varor som inte föreslås.
4. Efter att ha sparat listan lagras den i databasen. På bekräftelsesidan kan användaren lägga till nya varor.
5. När inköpen är bekräftade uppdateras databasen med nya inköpsdatum.
6. Användaren loggar ut.

### Scenario 2: Automatiserad varuföreslagning
1. Användaren loggar in och ser inköpslistan med förslag baserat på historiska inköpsdata.
2. Programmet analyserar genomsnittliga förbrukningsintervall per produkt.
3. Användaren kan lägga till varor manuellt.
4. Efter att ha sparat listan lagras den i databasen.
5. På bekräftelsesidan kan användaren lägga till spontana inköp.
6. Databasen uppdateras med nya inköpsdatum.
7. Användaren loggar ut.

### Scenario 3: Misslyckad inloggning
1. Användaren försöker logga in med felaktiga uppgifter och får ett felmeddelande.
2. Vid nästa försök lyckas inloggningen.
3. Inköpslistan visas.

### Scenario 4: Ersätta otillgänglig vara
1. Användaren loggar in och granskar inköpslistan.
2. En vara (t.ex. "Produkt A") är otillgänglig, så användaren ersätter den med "Produkt B".
3. Båda varorna sparas i databasen, med "Produkt B" markerad som tillfällig ersättning.
4. Systemet överväger båda varorna i framtida förslag.
5. Användaren bekräftar inköpen och loggar ut.

### Scenario 5: Ta bort utgången produkt
1. Användaren loggar in och upptäcker att "Produkt C" inte längre erbjuds.
2. Användaren tar bort "Produkt C" från inköpslistan.
3. Systemet uppdaterar databasen och exkluderar "Produkt C" från framtida förslag.
4. Användaren loggar ut.

---

*progp 2025: Labb W1: Laboration i webbprogrammering*