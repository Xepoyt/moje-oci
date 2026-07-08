# Moje Oči – Portál pro zdravotnická zařízení

Moderní webový portál navržený pro registraci a správu partnerských zdravotnických zařízení (klinik a ordinací). Aplikace je postavená na frameworku **Nette (PHP 8.2+)**.

Projekt klade důraz na čistou komponentovou architekturu, bezpečný vícestupňový schvalovací proces a skvělé uživatelské prostředí (UX) díky využití AJAXu a responzivního designu.

## Hlavní vlastnosti

* **Vícestupňová registrace:** Zabezpečený proces registrace s automatickým ověřováním e-mailové adresy pomocí unikátních tokenů a finálním schválením administrátorem.
* **Komplexní validace dat:** Oboustranná (Frontend + Backend) kontrola vstupů, včetně dedikovaného algoritmu pro ověřování platnosti českého IČO.
* **Klientská zóna:** Správa profilu kliniky, kontaktních údajů a hesel. Změny citlivých údajů podléhají schválení administrátorem.
* **Administrační rozhraní:** Výpis registrovaných zařízení pomocí interaktivního DataGridu (stránkování, řazení, filtrování). Detaily klinik a schvalování změn probíhá plynule přes AJAX v modálních oknech bez přesměrování.
* **Notifikační systém:** Plně automatizované rozesílání e-mailů (využívající Latte šablony) pro uživatele i administrátory (potvrzení registrace, schválení/zamítnutí, obnova zapomenutého hesla).
* **Komponentový přístup:** Logika UI je rozbita do znovupoužitelných a testovatelných Nette komponent.

## Použité technologie

* **Backend:** PHP 8.2+, [Nette Framework 3](https://nette.org/) (Application, Database Explorer, Security, Mail)
* **Databáze:** MariaDB / MySQL
* **Frontend:** HTML5, Latte šablony, CSS3, Bootstrap 5, Bootstrap Icons
* **JavaScript:** Vlastní validace + [Nette Forms JS](https://nette.org/cs/forms), [Naja.js](https://naja.js.org/) (pro bezproblémový AJAX)
* **Nástroje:** Composer, Tracy (pro ladění a zachytávání výjimek)

## Architektura a struktura projektu

Aplikace využívá striktní rozvrstvení na logické celky a využívá návrhový vzor Dependency Injection:

* **Prezentační vrstva (`app/Presentation/`):** Presentery (Home, Login, Registration, Account, Admin) starající se o směrování a oprávnění. Neobsahují formuláře, ty jsou delegovány na komponenty.
* **Komponenty (`app/Components/`):** Samostatné třídy (`*Control`) zapouzdřující UI a zpracování formulářů (např. `ClinicsGridControl`, `ChangeClinicControl`, `LoginControl`). Vytvářeny přes autogenerované továrničky (`*ControlFactory`).
* **Služby (`app/Services/`):** Jádro byznys logiky aplikace. Spojují databázi, maily a další moduly dohromady.
* `RegistrationService` - orchestrace registrací, generování tokenů a schvalovací proces.
* `AccountService` - správa klientských účtů, resetování hesel, žádosti o změnu údajů.
* `EmailService` - obaluje Nette Mailer, překládá data do Latte šablon a odesílá notifikace.
* **Modely (`app/Models/`):** Třída `FacilityManager` se stará o dotazování do databáze (Nette Database Explorer) a vracení `ActiveRow` objektů.
* **Security & Utils:** Vlastní `ClinicAuthenticator` pro bezpečné ověřování hesel (Bcrypt) a přidělování rolí. Vlastní výjimky (např. `ClinicNotFoundException`).

## Instalace pro lokální vývoj

1. **Klonování repozitáře:** 
    ```bash 
    git clone <url-tveho-repozitare>
    cd moje-oci
    ```
2. **Instalace závislostí:** 
    ```bash
    composer install
    ```
3. **Příprava databáze:**
    * Vytvořte si lokální databázi (např. `moje_oci`).
    * Importujte strukturu databáze (tabulky `clinics`, `programs`, ...).
    * Vytvořte soubor `config/local.neon` ze vzoru `common.neon` a doplňte přístupové údaje k databázi a SMTP serveru pro odesílání e-mailů.
4. **Spuštění serveru:**
    Můžete využít vestavěný PHP server v Nette: 
    ```bash
    php -S localhost:8000 -t www
    ```
    Aplikace poběží na adrese `http://localhost:8000`.

## Významné UX a bezpečnostní prvky

Během vývoje byl kladen důraz na bezpečnost a plynulost uživatelského zážitku:

* **AJAX Integrace (Naja):** Zásadní prvky administrace (stránkování, filtrování, načítání detailů do modálních oken) využívají Nette Snippets. Stránka se nepřenačítá, což šetří databázi i čas uživatele.
* **State Machine klinik:** Zajištění, aby uživatelé nemohli změnit svá data a okamžitě je propsat na veřejnost bez vědomí administrátora. Data se nejprve ukládají jako "požadavek na změnu".
* **Důraz na UX formulářů:** Implementace vlastního JavaScriptu pro Bootstrap formuláře řešící moderní vykreslování chybně vyplněných formulářů.