# HeliosAPI

## Usage
Every API call from client must have JWT token in 'Authorization' header.
JWT token must be signed by same signature which is known to server and client (can be found at index.php in variable $app['signingkey']).

## API methods

### List of clients

Url: \<server\>/heliosapi/clients

Method: GET

Input parameters:
```
name: {optional} <search string> [TabCisOrg.Nazev OR TabCisOrg.DruhyNazev],
status: {optional} <status> (0, 1, 2, 3) [TabCisOrg.Stav],
listfrom: {optional} <position from complete list where result begins>,
listto: {optional} <position from complete list where result ends>,
sort: {optional} <by which should be ordered> ('nameasc', 'namedesc') [TabCisOrg.Nazev]
```

Output parameters:
```
{ 
    rows: {
        Array {
            id: <id> [TabCisOrg.ID],
            orgnum: <organisation number> [TabCisOrg.CisloOrg],
            parentid: <parent client id> [TabCisOrg.NadrizenaOrg],
            name: <name> [TabCisOrg.Nazev],
            name2: <second name> [TabCisOrg.DruhyNazev],
            email: <email> [],
            phone: <phone number> [],
            contact: <contact>  [TabCisOrg.Kontakt],
            web: <web URL> [],
            status: <status 0 - 3> [TabCisOrg.Stav]
        }
    },
    totalrows: <total count of rows of whole list from which is listfrom and listto returned>
}
```

### Detail of client

Url:\<server\>/heliosapi/clients/\<id\>

Method: GET

Input parameters:
```
id: <client id>
```

Output parameters:
```
{ 
    id: <id> [TabCisOrg.ID],
    orgnum: <organisation number> [TabCisOrg.CisloOrg],
    parentid: <parent client id> [TabCisOrg.NadrizenaOrg],
    name: <name> [TabCisOrg.Nazev],
    name2: <second name> [TabCisOrg.DruhyNazev],
    email: <email> [],
    phone: <phone number> [],
    address: {
        street: <street> [TabCisOrg.Ulice],
        streetorinumber: <orientation number> [TabCisOrg.OrCislo],
        streetdesnumber: <descriptive number> [TabCisOrg.PopCislo],
        city: <city> [TabCisOrg.Misto],
        zip: <zip code> [TabCisOrg.PSC]
    },
    contact: <contact>  [TabCisOrg.Kontakt],
    ic: <ic number> [TabCisOrg.ICO],
    dic: <dic number> [TabCisOrg.DIC],
    web: <web URL> [],
    status: <status 0 - 3> [TabCisOrg.Stav]
}
```

### Create new client

Url:\<server\>/heliosapi/clients

Method: POST

Input parameters:
```
orgnum: <organisation number> [TabCisOrg.CisloOrg],
parentid: {optional} <parent client id> [TabCisOrg.NadrizenaOrg],
name: <name> [TabCisOrg.Nazev],
name2: <second name> [TabCisOrg.DruhyNazev],
street: <street> [TabCisOrg.Ulice],
streetorinumber: <orientation number> [TabCisOrg.OrCislo],
streetdesnumber: <descriptive number> [TabCisOrg.PopCislo],
city: <city> [TabCisOrg.Misto],
zip: {optional} <zip code> [TabCisOrg.PSC],
contact: {optional} <contact>  [TabCisOrg.Kontakt],
ic: {optional} <ic number> [TabCisOrg.ICO],
dic: {optional} <dic number> [TabCisOrg.DIC],
status: <status 0 - 3> [TabCisOrg.Stav]
```

Output parameters:
```
HTTP Response 201
Header Location: clients/{id of created client}
```

### List of products

Url:\<server\>/heliosapi/products

Method: GET

Input parameters:
```
name: <product name> [TabKmenZbozi.Nazev1 OR TabKmenZbozi.Nazev2 OR TabKmenZbozi.Nazev3 OR TabKmenZbozi.Nazev3 OR TabKmenZbozi.Nazev4],
centernumber: <center number> [TabKmenZbozi.KmenoveStredisko],
regnumber: <registration number> [KmenoveStredisko.RegCis],
listfrom: {optional} <position from complete list where result begins>,
listto: {optional} <position from complete list where result ends>,
sort: {optional} <by which should be ordered> ('nameasc', 'namedesc') [TabKmenZbozi.Nazev1]
```

Output parameters:
```
{ 
    rows: {
        Array {
            id: <id> [TabKmenZbozi.ID],
            regnum: <id> [TabKmenZbozi.RegCis],
            group: <id> [TabKmenZbozi.SkupZbo],
            name1: <id> [TabKmenZbozi.Nazev1],
            name2: <id> [TabKmenZbozi.Nazev2],
            name3: <id> [TabKmenZbozi.Nazev3],
            name4: <id> [TabKmenZbozi.Nazev4],
            skp: <id> [TabKmenZbozi.SKP]
        }
    }
}
```

### Detail of product

Url:\<server\>/heliosapi/products/\<product id\>

Method: GET

Input parameters:
```
id: <product id> [TabKmenZbozi.ID]
```

Output parameters:
```
{
    id: <id> [TabKmenZbozi.ID],
    group: <group id> [TabKmenZbozi.SkupZbo],
    regnum: <registration number> [TabKmenZbozi.RegCis],
    name: <name> [TabKmenZbozi.Nazev1],
    name2: <second name> [TabKmenZbozi.Nazev2],
    name3: <third name> [TabKmenZbozi.Nazev3],
    name4: <fourth name> [TabKmenZbozi.Nazev4],
    skp: <id> [TabKmenZbozi.SKP],
    range: <range of goods> [TabKmenZbozi.IdSortiment],
    vintage: <vintage> [],
    notice: <notice> [TabKmenZbozi.Upozorneni],
    note: <note> [TabKmenZbozi.Poznamka],
    muevidence: <measurement unit of evidence> [TabKmenZbozi.MJEvidence],
    mustocktaking: <measurement unit of stock-taking> [TabKmenZbozi.MJInventura],
    muinput: <measurement unit of input> [TabKmenZbozi.MJVstup],
    muoutput: <measurement unit of output> [TabKmenZbozi.MJVystup],
    vatinput: <vat input> [TabKmenZbozi.SazbaDPHVstup],
    vatoutput: <vat output> [TabKmenZbozi.SazbaDPHVystup],
    pdpcode: <PDP code> [TabKmenZbozi.IDKodPDP],
    edinput: <excise duty input> [TabKmenZbozi.SazbaSDVstup],
    edoutput: <excise duty output> [TabKmenZbozi.SazbaSDVystup],
    mued: <measurement unit of excise duty> [TabKmenZbozi.MJSD],
    edcode: <excise duty code> [TabKmenZbozi.KodSD],
    edcalc: <excise duty calculation> [TabKmenZbozi.PrepocetMJSD]
}
```