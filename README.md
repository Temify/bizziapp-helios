# HeliosAPI

## Usage
Every API call from client must have JWT token in 'Authorization' header.
JWT token must be signed by same signature which is known to server and client (can be found at index.php in variable $app['signingkey']).

Possible HTTP result codes over all methods:
```
401 - Unauthorized - when Authorization header does not contains "Bearer " + valid JWT token signed by signing key
404 - Not Found - method or path not found
405 - Method Not Allowed - when requested unknown method
415 - Unsupported Media Type - when Content-Type header is not "application/json"
500 - Internal Server Error - when server or database error
```

## Settings
API settings.

### Environment varaibles

```
DEBUG: <boolean:debug mode is true=ON or false=OFF - default false - in debug mode, additional web/log/development.log is created/appended>
DB_DRIVER: <string:db driver - default 'pdo_sqlsrv'>
DB_HOST: <string:db host>
DB_PORT: <integer:db port - default 1433>
DB_NAME: <string:db name>
DB_USER: <string:db username>
DB_PASSWORD: <string:db password>
```

## API methods

### List of clients - version 1

Get list of clients.

#### Request
Url: `<server>/heliosapi/clients`

Method: GET

Headers:
```
Accept: application/json
Authorization: Bearer <JWT token>
```

GET parameters:
```
name: {optional} <string length 1-100:name - search string> [TabCisOrg.Nazev OR TabCisOrg.DruhyNazev],
nameisnotnull: {optional} <string:'true' = name is not null, 'false' = name is null, null = name can be null > [TabCisOrg.Nazev OR TabCisOrg.DruhyNazev],
status: {optional} <string:status> ('0' = active, '1' = blocked, '2' = disabled, '3' = potential) [TabCisOrg.Stav],
listfrom: {optional} <string:number of position from complete list where result begins>,
listto: {optional} <string:number of position from complete list where result ends>,
sort: {optional} <string:by which should be ordered> ('nameasc', 'namedesc', 'ideasc', 'iddesc') [TabCisOrg.Nazev, TabCisOrg.ID]
```

#### Response
HTTP Response Code: 200

Headers:
```
Content-Type: application/json
```

Output JSON object:
```
{ 
    rows: {
        Array {
            id: <integer:client id> [TabCisOrg.ID],
            orgnum: <integer:organisation number> [TabCisOrg.CisloOrg],
            parentid: <integer:parent client id> [TabCisOrg.NadrizenaOrg],
            name: <string:name> [TabCisOrg.Nazev],
            name2: <string:second name> [TabCisOrg.DruhyNazev],
            email: <array of string:email> [IF TabKontakty.Druh = 6 or 10 THEN TabKontakty.Sopojeni, TabKontakty.Sopojeni2],
            phone: <array of string:phone number> [IF TabKontakty.Druh = 1 or 2 THEN TabKontakty.Sopojeni, TabKontakty.Sopojeni2],
            website: <array of string:web URL> [IF TabKontakty.Druh = 7 THEN TabKontakty.Sopojeni, TabKontakty.Sopojeni2],
            ic: <string:ic number> [TabCisOrg.ICO],
            dic: <string:dic number> [TabCisOrg.DIC],
            contact: <string:contact>  [TabCisOrg.Kontakt],
            status: <integer:status 0 - 3> [TabCisOrg.Stav],
            address: {
                street: <string:street> [TabCisOrg.Ulice],
                streetorinumber: <string:orientation number> [TabCisOrg.OrCislo],
                streetdesnumber: <string:descriptive number> [TabCisOrg.PopCislo],
                city: <string:city> [TabCisOrg.Misto],
                zip: <string:zip code> [TabCisOrg.PSC],
                country: <string:short country code> [TabCisOrg.IdZeme]
            },
            responsibleperson: {
                firstname: <string:first name> [TabCisZam.Jmeno],
                lastname: <string:last name> [TabCisZam.Prijmeni],
                street: <string:street> [TabCisZam.AdrTrvUlice],
                streetornumber: <string:orientation number> [TabCisZam.AdrTrvOrCislo],
                streetdesnumber: <string:descriptive number> [TabCisZam.AdrTrvPopCislo],
                city: <string:city> [TabCisZam.AdrTrvMisto],
                zip: <string:zip code> [TabCisZam.AdrTrvPSC],
                country: <string:short country code> [TabCisZam.AdrTrvZeme]
            }
        }
    },
    totalrows: <integer:number of total count of rows of whole list from which is listfrom and listto returned>
}
```

Possible HTTP result codes:
```
200 - OK - successfull
400 - Bad Request - when GET parameters have no correct format
```

### Detail of client - version 1
Get detail of specific client.

#### Request
Url:`<server>/heliosapi/clients/<string:client id>`

Method: GET

Headers:
```
Accept: application/json
Authorization: Bearer <JWT token>
```

GET parameters:

Empty

#### Response
HTTP Response Code: 200

Headers:
```
Content-Type: application/json
```

Output JSON object:
```
{ 
    id: <integer:client id> [TabCisOrg.ID],
    orgnum: <integer:organisation number> [TabCisOrg.CisloOrg],
    parentid: <integer:parent client id> [TabCisOrg.NadrizenaOrg],
    name: <string:name> [TabCisOrg.Nazev],
    name2: <string:second name> [TabCisOrg.DruhyNazev],
    email: <array of string:email> [IF TabKontakty.Druh = 6 or 10 THEN TabKontakty.Sopojeni, TabKontakty.Sopojeni2],
    phone: <array of string:phone number> [IF TabKontakty.Druh = 1 or 2 THEN TabKontakty.Sopojeni, TabKontakty.Sopojeni2],
    website: <array of string:web URL> [IF TabKontakty.Druh = 7 THEN TabKontakty.Sopojeni, TabKontakty.Sopojeni2],
    address: {
        street: <string:street> [TabCisOrg.Ulice],
        streetorinumber: <string:orientation number> [TabCisOrg.OrCislo],
        streetdesnumber: <string:descriptive number> [TabCisOrg.PopCislo],
        city: <string:city> [TabCisOrg.Misto],
        zip: <string:zip code> [TabCisOrg.PSC],
        country: <string:short country code> [TabCisOrg.IdZeme]
    },
    responsibleperson: {
        firstname: <string:first name> [TabCisZam.Jmeno],
        lastname: <string:last name> [TabCisZam.Prijmeni],
        street: <string:street> [TabCisZam.AdrTrvUlice],
        streetornumber: <string:orientation number> [TabCisZam.AdrTrvOrCislo],
        streetdesnumber: <string:descriptive number> [TabCisZam.AdrTrvPopCislo],
        city: <string:city> [TabCisZam.AdrTrvMisto],
        zip: <string:zip code> [TabCisZam.AdrTrvPSC],
        country: <string:short country code> [TabCisZam.AdrTrvZeme]
    },
    contact: <string:contact>  [TabCisOrg.Kontakt],
    ic: <string:ic number> [TabCisOrg.ICO],
    dic: <string:dic number> [TabCisOrg.DIC],
    status: <integer:status> (0 = active, 1 = blocked, 2 = disabled, 3 = potential) [TabCisOrg.Stav]
}
```

Possible HTTP result codes:
```
200 - OK - successfull
400 - Bad Request - when <client id> is not a number
404 - Not Found - when client with <client id> does not exists
```

### Create new client - version 1
Create a new client.

#### Request
Url:`<server>/heliosapi/clients`

Method: POST

Headers:
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer <JWT token>
```

POST JSON object:
```
{
    orgnum: {optional} <integer:organisation number - when null, 0 or missing, will be benerated automatically> [TabCisOrg.CisloOrg],
    parentid: {optional} <integer:parent client id> [TabCisOrg.NadrizenaOrg],
    name: <string length 1-100:name> [TabCisOrg.Nazev],
    name2: {optional} <string length 1-100:second name> [TabCisOrg.DruhyNazev],
    street: <string length 1-100:street> [TabCisOrg.Ulice],
    streetorinumber: <string length 1-15:orientation number> [TabCisOrg.OrCislo],
    streetdesnumber: <string length 1-15:descriptive number> [TabCisOrg.PopCislo],
    city: <string length 1-100:city> [TabCisOrg.Misto],
    zip: {optional} <string length 1-10:zip code> [TabCisOrg.PSC],
    contact: {optional} <string:contact>  [TabCisOrg.Kontakt],
    ic: {optional} <string length 1-20:ic number> [TabCisOrg.ICO],
    dic: {optional} <string length 1-15:dic number> [TabCisOrg.DIC],
    status: {optional} <string:status> ('0' = active, '1' = blocked, '2' = disabled, '3' = potential - default '0') [TabCisOrg.Stav]
}
```

#### Response
HTTP Response Code: 201

Headers:
```
Content-Type: application/json
Header Location: clients/<string:client id>
```

Output JSON object:
````
{
    id: <integer:client id of created client> [TabCisOrg.ID]
}
````

Possible HTTP result codes:
```
201 - Created - successfull
400 - Bad Request - when input parameters are not correct
409 - Conflict - when client with orgnum already exists
```

### Update client - version 1
Update detail data of specific client.

#### Request
Url:`<server>/heliosapi/clients/<string:client id>`

Method: PUT

Headers:
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer <JWT token>
```

PUT JSON object:
```
{
    orgnum: {optional} <integer:organisation number> [TabCisOrg.CisloOrg],
    parentid: {optional} <integer:parent client id> [TabCisOrg.NadrizenaOrg],
    name: {optional} <string length 1-100:name> [TabCisOrg.Nazev],
    name2: {optional} <string length 1-100:second name> [TabCisOrg.DruhyNazev],
    street: {optional} <string length 1-100:street> [TabCisOrg.Ulice],
    streetorinumber: {optional} <string length 1-15:orientation number> [TabCisOrg.OrCislo],
    streetdesnumber: {optional} <string length 1-15:descriptive number> [TabCisOrg.PopCislo],
    city: {optional} <string length 1-100:city> [TabCisOrg.Misto],
    zip: {optional} <string length 1-10:zip code> [TabCisOrg.PSC],
    contact: {optional} <string length 1-40:contact>  [TabCisOrg.Kontakt],
    ic: {optional} <string length 1-20:ic number> [TabCisOrg.ICO],
    dic: {optional} <string length 1-15:dic number> [TabCisOrg.DIC],
    status: {optional} <integer:status> (0 = active, 1 = blocked, 2 = disabled, 3 = potential) [TabCisOrg.Stav]
}
```

#### Response
HTTP Response Code: 200

Headers:
```
Content-Type: application/json
```

Output JSON object:

Empty

Possible HTTP result codes:
```
200 - OK - update successfull
204 - No Content - missing all input parameters
400 - Bad Request - input data not valid
404 - Not Found - <client id> not found
405 - Method Not Allowed - when <client id> is missing
500 - Internal Server Error - when update affected != 1 rows, calls also rollback
```

### Delete client - version 1
Delete specific client.

#### Request
Url:`<server>/heliosapi/clients/<string:client id>`

Method: DELETE

Headers:
```
Accept: application/json
Authorization: Bearer <JWT token>
```

DELETE parameters:

Empty

#### Response
HTTP Response Code: 200

Headers:
```
Content-Type: application/json
```

Output JSON object:

Empty

Possible HTTP result codes:
```
200 - OK - delete successfull
404 - Not Found - <client id> not found
405 - Method Not Allowed - when <client id> is missing
500 - Internal Server Error - when delete affected != 1 rows, calls also rollback
```

### List of products - version 1
Get list of products.

#### Request
Url:`<server>/heliosapi/products`

Method: GET

Headers:
```
Accept: application/json
Authorization: Bearer <JWT token>
```

GET parameters:
```
name: <string length 1-100:product name> [TabKmenZbozi.Nazev1 OR TabKmenZbozi.Nazev2 OR TabKmenZbozi.Nazev3 OR TabKmenZbozi.Nazev3 OR TabKmenZbozi.Nazev4],
centernumber: <string length 1-30:center number> [TabKmenZbozi.KmenoveStredisko],
regnumber: <string length 1-30:registration number> [TabKmenZbozi.RegCis],
listfrom: {optional} <integer:position from complete list where result begins>,
listto: {optional} <integer:position from complete list where result ends>,
sort: {optional} <string:by which should be ordered> ('nameasc', 'namedesc') [TabKmenZbozi.Nazev1],
pricelevel: {optional} <integer:price level> (default = 1) [TabNC.CenovaUroven],
donotorderhidden: {optional} <integer length 1:hide products with donotorder=1 with no items in storage> [IF TabKmenZbozi_EXT._Neobjednavat == 1 AND TabStavSkladu.MnozstviDispo == 0 AND TabStavSkladu.MnozstviKPrijmu == 0 AND TabStavSkladu.MnozstviKVydeji == 0],
pricefrom: {optional} <integer:product price without vat is equal or higher> [TabNC.CenaKC],
priceto: {optional} <integer:product price without vat is less or equal> [TabNC.CenaKC],
usualorigincountry: {optional} <string:short code of country> [TabKmenZbozi.ObvyklaZemePuvodu],
goodskind: <integer:typ of goods> (NULL = not inserted, 1 = common, 2 = allocation, 3 = rarity, 4 = clearance sale, 5 = archive, 6 = POS material)[TabKmenZbozi_EXT._DruhVina],
goodstype: <integer:typ of wine> (NULL = not inserted, 1 = white, 2 = rose, 3 = red, 4 = champagne, 5 = sparkling) [TabKmenZbozi_EXT._TypVina]
```

#### Response
Headers:
```
Content-Type: application/json
```

Output JSON object:
```
{ 
    rows: {
        Array {
            id: <integer:product id> [TabKmenZbozi.ID],
            regnum: <string:registration number> [TabKmenZbozi.RegCis],
            group: <string:group id> [TabKmenZbozi.SkupZbo],
            name1: <string:first product name> [TabKmenZbozi.Nazev1],
            name2: <string:second product name> [TabKmenZbozi.Nazev2],
            name3: <string:third product name> [TabKmenZbozi.Nazev3],
            name4: <string:fourth product name> [TabKmenZbozi.Nazev4],
            skp: <string:skp> [TabKmenZbozi.SKP],
            price: <float:price without VAT> [TabNC.CenaKC],
            pricevat: <float:price with VAT> [TabNC.CenaKC * (1 + (0,01 * TabKmenZbozi.SazbaDPHVystup))],
            vintage: <integer:vintage of wine> [TabKmenZbozi.Nazev3],
            blocked: <integer:product is active or archived> (0 = active, 1 = archived) [TabKmenZbozi.Blokovano],
            donotorder: <integer:do not orged this produc anymore> (0 = order, 1 = do not order) [TabKmenZbozi_EXT._Neobjednavat],
            goodskind: <integer:typ of goods> (NULL = not inserted, 1 = common, 2 = allocation, 3 = rarity, 4 = clearance sale, 5 = archive, 6 = POS material),[TabKmenZbozi_EXT._DruhVina],
            ivk: <integer:wine accessories> (NULL/0 = wine, 1 = accessories) [TabKmenZbozi_EXT._IVK],
            usualorigincountry: <string:usual country of origin> [TabKmenZbozi.ObvyklaZemePuvodu],
            goodstype: <integer:typ of wine> (NULL = not inserted, 1 = white, 2 = rose, 3 = red, 4 = champagne, 5 = sparkling) [TabKmenZbozi_EXT._TypVina],
            storages: [
                {
                    storageid: <int:id of storage> (3 = Bezny sklad, 6 = Danovy sklad) [TabStrom.Id],
                    quantityavailable: <integer:quantity of goods available> [TabStavSkladu.MnozstviDispo],
                    quantitytoreceive: <integer:quantity of goods to receive> [TabStavSkladu.MnozstviKPrijmu],
                    quantitytodispense: <integer:quantity of goods to dispense> [TabStavSkladu.MnozstviKVydeji]
                }
            ]
        }
    },
    totalrows: <integer:total count of rows of whole list from which is listfrom and listto returned>
}
```

Possible HTTP result codes:
```
200 - OK - successfull
400 - Bad Request - when GET parameters have no correct format
```

### Detail of product - version 1
Get detail of specific product.

#### Request
Url:`<server>/heliosapi/products/<string:product id>`

Method: GET

Headers:
```
Accept: application/json
Authorization: Bearer <JWT token>
```

GET parameters:
```
id: <string:product id> [TabKmenZbozi.ID]
```

#### Response
Headers:
```
Content-Type: application/json
```

Output JSON object:
```
{
    id: <integer:product id> [TabKmenZbozi.ID],
    group: <string:group id> [TabKmenZbozi.SkupZbo],
    regnum: <string:registration number> [TabKmenZbozi.RegCis],
    storagetype: {optional} <integer:type of storage> (0 = service, 1 = global configuration, 2 = FIFO, 3 = averages, 4 = customs warehouse) [TabKmenZbozi.DruhSkladu],
    name1: <string:first product name> [TabKmenZbozi.Nazev1],
    name2: <string:second product name> [TabKmenZbozi.Nazev2],
    name3: <string:third product name> [TabKmenZbozi.Nazev3],
    name4: <string:fourth product name> [TabKmenZbozi.Nazev4],
    vintage: <integer:vintage of wine> [TabKmenZbozi.Nazev3],
    skp: <string:skp> [TabKmenZbozi.SKP],
    range: <integer:range of goods> [TabKmenZbozi.IdSortiment],
    notice: <string:notice> [TabKmenZbozi.Upozorneni],
    note: <string:note> [TabKmenZbozi.Poznamka],
    muevidence: <string:measurement unit of evidence> [TabKmenZbozi.MJEvidence],
    mustocktaking: <string:measurement unit of stock-taking> [TabKmenZbozi.MJInventura],
    muinput: <string:measurement unit of input> [TabKmenZbozi.MJVstup],
    muoutput: <string:measurement unit of output> [TabKmenZbozi.MJVystup],
    vatinput: <float:vat input> [TabKmenZbozi.SazbaDPHVstup],
    vatoutput: <float:vat output> [TabKmenZbozi.SazbaDPHVystup],
    price: <float:price without VAT - pricelevel = 1> [TabNC.CenaKC],
    pricevat: <float:price with VAT - pricelevel = 1> [TabNC.CenaKC * (1 + (0,01 * TabKmenZbozi.SazbaDPHVystup))],
    pdpcode: <integer:PDP code> [TabKmenZbozi.IDKodPDP],
    edinput: <float:excise duty input> [TabKmenZbozi.SazbaSDVstup],
    edoutput: <flost:excise duty output> [TabKmenZbozi.SazbaSDVystup],
    mued: <string:measurement unit of excise duty> [TabKmenZbozi.MJSD],
    edcode: <string:excise duty code> [TabKmenZbozi.KodSD],
    edcalc: <float:excise duty calculation> [TabKmenZbozi.PrepocetMJSD],
    blocked: <integer:product is active or archived> (0 = active, 1 = archived) [TabKmenZbozi.Blokovano],
    donotorder: <integer:do not orged this produc anymore> (0 = order, 1 = do not order) [TabKmenZbozi_EXT._Neobjednavat],
    goodskind: <integer:typ o goods> (NULL = not inserted, 1 = common, 2 = allocation, 3 = rarity, 4 = clearance sale, 5 = archive, 6 = POS material) [TabKmenZbozi_EXT._DruhVina],
    ivk: <integer:wine accessories> (NULL/0 = wine, 1 = accessories) [TabKmenZbozi_EXT._IVK],
    usualorigincountry: <string:usual country of origin> [TabKmenZbozi.ObvyklaZemePuvodu],
    goodstype: <integer:typ o wine> (NULL = not inserted, 1 = white, 2 = rose, 3 = red, 4 = champagne, 5 = sparkling) [TabKmenZbozi_EXT._TypVina],
    storages: [
        {
            storageid: <int:id of storage> (3 = Bezny sklad, 6 = Danovy sklad) [TabStrom.Id],
            quantityavailable: <integer:quantity of goods available> [TabStavSkladu.MnozstviDispo],
            quantitytoreceive: <integer:quantity of goods to receive> [TabStavSkladu.MnozstviKPrijmu],
            quantitytodispense: <integer:quantity of goods to dispense> [TabStavSkladu.MnozstviKVydeji]
        }
    ]
```

Possible HTTP result codes:
```
200 - OK - successfull
400 - Bad Request - when <client id> is not a number
404 - Not Found - when client with <client id> does not exists
```

### Create product - version 1
Create a new product.

#### Request
Url:`<server>/heliosapi/products`

Method: POST

Headers:
```
Accept: application/json
Authorization: Bearer <JWT token>
```

POST JSON object:
```
{
    group: <string length 1-3:group id> [TabKmenZbozi.SkupZbo => TabSkupinyZbozi.SkupZbo],
    regnum: <string length 1-30:registration number> [TabKmenZbozi.RegCis],
    storagetype: {optional} <integer:type of storage> (0 = service, 1 = global configuration, 2 = FIFO, 3 = averages, 4 = customs warehouse - default 1) [TabKmenZbozi.DruhSkladu],
    name: <string length 1-100:first product name> [TabKmenZbozi.Nazev1],
    name2: {optional} <string length 1-100:second product name> [TabKmenZbozi.Nazev2],
    name3: {optional} <string length 1-100:third product name> [TabKmenZbozi.Nazev3],
    name4: {optional} <string length 1-100:fourth product name> [TabKmenZbozi.Nazev4],
    skp: {optional} <string length 1-50:skp> [TabKmenZbozi.SKP],
    range: {optional} <integer:range of goods> [TabKmenZbozi.IdSortiment => TabSortiment.ID],
    notice: {optional} <string length 1-255:notice> [TabKmenZbozi.Upozorneni],
    note: {optional} <string length 1-1073741823:note> [TabKmenZbozi.Poznamka],
    muevidence: {optional} <string length 1-10:measurement unit of evidence> [TabKmenZbozi.MJEvidence],
    mustocktaking: {optional} <string length 1-10:measurement unit of stock-taking> [TabKmenZbozi.MJInventura],
    muinput: {optional} <string length 1-10:measurement unit of input> [TabKmenZbozi.MJVstup],
    muoutput: {optional} <string length 1-10:measurement unit of output> [TabKmenZbozi.MJVystup],
    vatinput: {optional} <float:vat input> [TabKmenZbozi.SazbaDPHVstup],
    vatoutput: <float:vat output> [TabKmenZbozi.SazbaDPHVystup],
    pdpcode: {optional} <integer:PDP code> [TabKmenZbozi.IDKodPDP],
    edinput: {optional} <float:excise duty input> [TabKmenZbozi.SazbaSDVstup],
    edoutput: {optional} <flost:excise duty output> [TabKmenZbozi.SazbaSDVystup],
    mued: {optional} <string length 1-10:measurement unit of excise duty> [TabKmenZbozi.MJSD],
    edcode: {optional} <string length 1-10:excise duty code> [TabKmenZbozi.KodSD],
    edcalc: {optional} <float:excise duty calculation> [TabKmenZbozi.PrepocetMJSD],
    blocked: {optional} <integer:product is active or archived> (0 = active, 1 = archived - default 0) [TabKmenZbozi.Blokovano],
    price: <float:price for pricelevel 1> [TabNC.CenaKc],
    donotorder: {optional} <integer:do not orged this produc anymore> (0 = order, 1 = do not order) [TabKmenZbozi_EXT._Neobjednavat],
    goodskind: {optional} <integer:typ o goods> (NULL = not inserted, 1 = common, 2 = allocation, 3 = rarity, 4 = clearance sale, 5 = archive, 6 = POS material) [TabKmenZbozi_EXT._DruhVina],
    ivk: {optional} <integer:wine accessories> (NULL/0 = wine, 1 = accessories) [TabKmenZbozi_EXT._IVK]
    usualorigincountry: {optional} <string length 1-2:usual country of origin> [TabKmenZbozi.ObvyklaZemePuvodu],
    goodstype: {optional} <integer:typ o wine> (NULL = not inserted, 1 = white, 2 = rose, 3 = red, 4 = champagne, 5 = sparkling) [TabKmenZbozi_EXT._TypVina]
}
```

#### Response
HTTP Response Code: 201

Headers:
```
Content-Type: application/json
Header Location: products/<string:product id>
```

Output JSON object:
````
{
    id: <integer:product id of created product> [TabKmenZbozi.ID]
}
````

Possible HTTP result codes:
```
201 - Created - successfull
400 - Bad Request - when input parameters are not correct
409 - Conflict - when product with regnum already exists
```

### Update product - version 1
Update detail data of specific product.

#### Request
Url:`<server>/heliosapi/products/<string:product id>`

Method: PUT

Headers:
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer <JWT token>
```

PUT JSON object:
```
{
    group: {optional} <string length 1-3:group id> [TabKmenZbozi.SkupZbo => TabSkupinyZbozi.SkupZbo],
    regnum: {optional} <string length 1-30:registration number> [TabKmenZbozi.RegCis],
    storagetype: {optional} <integer:type of storage 0 = service, 1 = global configuration, 2 = FIFO, 3 = averages, 4 = customs warehouse) [TabKmenZbozi.DruhSkladu],
    name: {optional} <string length 1-100:first product name> [TabKmenZbozi.Nazev1],
    name2: {optional} <string length 1-100:second product name> [TabKmenZbozi.Nazev2],
    name3: {optional} <string length 1-100:third product name> [TabKmenZbozi.Nazev3],
    name4: {optional} <string length 1-100:fourth product name> [TabKmenZbozi.Nazev4],
    skp: {optional} <string length 1-50:skp> [TabKmenZbozi.SKP],
    range: {optional} <integer:range of goods> [TabKmenZbozi.IdSortiment => TabSortiment.ID],
    notice: {optional} <string length 1-255:notice> [TabKmenZbozi.Upozorneni],
    note: {optional} <string length 1-1073741823:note> [TabKmenZbozi.Poznamka],
    muevidence: {optional} <string length 1-10:measurement unit of evidence> [TabKmenZbozi.MJEvidence],
    mustocktaking: {optional} <string length 1-10:measurement unit of stock-taking> [TabKmenZbozi.MJInventura],
    muinput: {optional} <string length 1-10:measurement unit of input> [TabKmenZbozi.MJVstup],
    muoutput: {optional} <string length 1-10:measurement unit of output> [TabKmenZbozi.MJVystup],
    vatinput: {optional} <float:vat input> [TabKmenZbozi.SazbaDPHVstup],
    vatoutput: {optional} <float:vat output> [TabKmenZbozi.SazbaDPHVystup],
    pdpcode: {optional} <integer:PDP code> [TabKmenZbozi.IDKodPDP],
    edinput: {optional} <float:excise duty input> [TabKmenZbozi.SazbaSDVstup],
    edoutput: {optional} <flost:excise duty output> [TabKmenZbozi.SazbaSDVystup],
    mued: {optional} <string length 1-10:measurement unit of excise duty> [TabKmenZbozi.MJSD],
    edcode: {optional} <string length 1-10:excise duty code> [TabKmenZbozi.KodSD],
    edcalc: {optional} <float:excise duty calculation> [TabKmenZbozi.PrepocetMJSD],
    blocked: {optional} <integer:product is active or archived> (0 = active, 1 = archived) [TabKmenZbozi.Blokovano],
    price: {optional} <float:price for pricelevel 1> [TabNC.CenaKc],
    donotorder: {optional} <integer:do not orged this produc anymore> (0 = order, 1 = do not order) [TabKmenZbozi_EXT._Neobjednavat],
    goodskind: {optional} <integer:typ o goods> (NULL = not inserted, 1 = common, 2 = allocation, 3 = rarity, 4 = clearance sale, 5 = archive, 6 = POS material) [TabKmenZbozi_EXT._DruhVina],
    ivk: {optional} <integer:wine accessories> (NULL/0 = wine, 1 = accessories) [TabKmenZbozi_EXT._IVK]
    usualorigincountry: {optional} <string length 1-2:usual country of origin> [TabKmenZbozi.ObvyklaZemePuvodu],
    goodstype: {optional} <integer:typ o wine> (NULL = not inserted, 1 = white, 2 = rose, 3 = red, 4 = champagne, 5 = sparkling) [TabKmenZbozi_EXT._TypVina]
}
```

#### Response
HTTP Response Code: 200

Headers:
```
Content-Type: application/json
```

Output JSON object:

Empty

Possible HTTP result codes:
```
200 - OK - update successfull
204 - No Content - missing all input parameters
400 - Bad Request - input data not valid
404 - Not Found - <product id> not found
405 - Method Not Allowed - when <product id> is missing
500 - Internal Server Error - when update affected != 1 rows, calls also rollback
```

### Delete product - version 1
Delete specific product.

#### Request
Url:`<server>/heliosapi/products/<string:product id>`

Method: DELETE

Headers:
```
Accept: application/json
Authorization: Bearer <JWT token>
```

DELETE parameters:

Empty

#### Response
HTTP Response Code: 200

Headers:
```
Content-Type: application/json
```

Output JSON object:

Empty

Possible HTTP result codes:
```
200 - OK - delete successfull
404 - Not Found - <product id> not found
405 - Method Not Allowed - when <product id> is missing
500 - Internal Server Error - when delete affected != 1 rows, calls also rollback
```

### List of contacts - version 1
Get list of contacts.

#### Request
Url:`<server>/heliosapi/contacts`

Method: GET

Headers:
```
Accept: application/json
Authorization: Bearer <JWT token>
```

GET parameters:
```
type: {optional} <string length 1-5:contact type> ('1' = phone-hard line, '2' = phone-mobile, '3' = fax, '4' = telex, '5' = operator, '6' = email, '7' = website, '8' = ico, '9' = ip address, '10' = bulk for email, '11' = skype, '12' = windows live messenger, '13' = login id, '14' = sms, '15' = data box) [TabKontakty.Druh],
orgid: {optional} <string length 1-10:organisation id> [TabKontakty.IDOrg],
primary: {optional} <string length 1:primary contact of specific type for orgid> ('0' = not primary, '1' = primary) [TabKontakty.Prednastaveno],
description: {optional} <string length 1-255:contact description - search string> [TabKontakty.Popis],
connection: {optional} <string length 1-255:contact connection by type - search string> [TabKontakty.Spojeni],
connection2: {optional} <string length 1-255:contact second connection by type - search string> [TabKontakty.Spojeni2],
listfrom: {optional} <string:number of position from complete list where result begins>,
listto: {optional} <string:number of position from complete list where result ends>,
sort: {optional} <string:by which should be ordered> ('typeasc', 'typedesc', 'connectionasc', 'connectiondesc') [TabKontakty.Druh, TabKontakty.Spojeni]
```

#### Response
Headers:
```
Content-Type: application/json
```

Output JSON object:
```
{ 
    rows: {
        Array {
            id: <integer:product id> [TabKontakty.ID],
            orgid: <integer:organisation id> [TabKontakty.IDOrg],
            type: <integer:contact type> (1 = phone-hard line, 2 = phone-mobile, 3 = fax, 4 = telex, 5 = operator, 6 = email, 7 = website, 8 = ico, 9 = ip address, 10 = bulk for email, 11 = skype, 12 = windows live messenger, 13 = login id, 14 = sms, 15 = data box) [TabKontakty.Druh],
            primary: <integer length 1:primary contact of specific type for orgid> (0 = not primary, 1 = primary) [TabKontakty.Prednastaveno],
            description: <string length 1-255:contact description> [TabKontakty.Popis],
            connection: <string length 1-255:contact connection by type> [TabKontakty.Spojeni],
            connection2: <string length 1-255:contact second connection by type> [TabKontakty.Spojeni2]
        }
    },
    totalrows: <integer:total count of rows of whole list from which is listfrom and listto returned>
}
```

Possible HTTP result codes:
```
200 - OK - successfull
400 - Bad Request - when GET parameters have no correct format
```

### Detail of contact - version 1
Get detail of specific contact.

#### Request
Url:`<server>/heliosapi/contacts/<string:contact id>`

Method: GET

Headers:
```
Accept: application/json
Authorization: Bearer <JWT token>
```

GET parameters:
```
id: <string:contact id> [TabKontakty.ID]
```

#### Response
Headers:
```
Content-Type: application/json
```

Output JSON object:
```
{
    id: <integer:product id> [TabKontakty.ID],
    orgid: <integer:organisation id> [TabKontakty.IDOrg],
    type: <integer:contact type> (1 = phone-hard line, 2 = phone-mobile, 3 = fax, 4 = telex, 5 = operator, 6 = email, 7 = website, 8 = ico, 9 = ip address, 10 = bulk for email, 11 = skype, 12 = windows live messenger, 13 = login id, 14 = sms, 15 = data box) [TabKontakty.Druh],
    primary: <integer length 1:primary contact of specific type for orgid> (0 = not primary, 1 = primary) [TabKontakty.Prednastaveno],
    description: <string length 1-255:contact description> [TabKontakty.Popis],
    connection: <string length 1-255:contact connection by type> [TabKontakty.Spojeni],
    connection2: <string length 1-255:contact second connection by type> [TabKontakty.Spojeni2]
}
```

Possible HTTP result codes:
```
200 - OK - successfull
400 - Bad Request - when <contact id> is not a number
404 - Not Found - when contact with <contact id> does not exists
```

### Create contact - version 1
Create a new contact.

#### Request
Url:`<server>/heliosapi/contacts`

Method: POST

Headers:
```
Accept: application/json
Authorization: Bearer <JWT token>
```

POST JSON object:
```
{
    orgid: <integer:organisation id> [TabKontakty.IDOrg],
    type: <integer:contact type> (1 = phone-hard line, 2 = phone-mobile, 3 = fax, 4 = telex, 5 = operator, 6 = email, 7 = website, 8 = ico, 9 = ip address, 10 = bulk for email, 11 = skype, 12 = windows live messenger, 13 = login id, 14 = sms, 15 = data box) [TabKontakty.Druh],
    primary: {optional} <integer length 1:primary contact of specific type for orgid> (0 = not primary, 1 = primary - default = 0) [TabKontakty.Prednastaveno],
    description: {optional} <string length 1-255:contact description> [TabKontakty.Popis],
    connection: {optional} <string length 1-255:contact connection by type> [TabKontakty.Spojeni],
    connection2: {optional} <string length 1-255:contact second connection by type> [TabKontakty.Spojeni2]
}
```

#### Response
HTTP Response Code: 201

Headers:
```
Content-Type: application/json
Header Location: products/<string:product id>
```

Output JSON object:
````
{
    id: <integer:contact id of created contact> [TabKontakty.ID]
}
````

Possible HTTP result codes:
```
201 - Created - successfull
400 - Bad Request - when input parameters are not correct
```

### Update contact - version 1
Update detail data of specific contact.

#### Request
Url:`<server>/heliosapi/contacts/<string:contact id>`

Method: PUT

Headers:
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer <JWT token>
```

PUT JSON object:
```
{
    orgid: {optional} <integer:organisation id> [TabKontakty.IDOrg],
    type: {optional} <integer:contact type> (1 = phone-hard line, 2 = phone-mobile, 3 = fax, 4 = telex, 5 = operator, 6 = email, 7 = website, 8 = ico, 9 = ip address, 10 = bulk for email, 11 = skype, 12 = windows live messenger, 13 = login id, 14 = sms, 15 = data box) [TabKontakty.Druh],
    primary: {optional} <integer length 1:primary contact of specific type for orgid> (0 = not primary, 1 = primary - default = 0) [TabKontakty.Prednastaveno],
    description: {optional} <string length 1-255:contact description> [TabKontakty.Popis],
    connection: {optional} <string length 1-255:contact connection by type> [TabKontakty.Spojeni],
    connection2: {optional} <string length 1-255:contact second connection by type> [TabKontakty.Spojeni2]
}
```

#### Response
HTTP Response Code: 200

Headers:
```
Content-Type: application/json
```

Output JSON object:

Empty

Possible HTTP result codes:
```
200 - OK - update successfull
204 - No Content - missing all input parameters
400 - Bad Request - input data not valid
404 - Not Found - <contact id> not found
405 - Method Not Allowed - when <contact id> is missing
500 - Internal Server Error - when update affected != 1 rows, calls also rollback
```

### Delete contact - version 1
Delete specific contact.

#### Request
Url:`<server>/heliosapi/contacts/<string:contact id>`

Method: DELETE

Headers:
```
Accept: application/json
Authorization: Bearer <JWT token>
```

DELETE parameters:

Empty

#### Response
HTTP Response Code: 200

Headers:
```
Content-Type: application/json
```

Output JSON object:

Empty

Possible HTTP result codes:
```
200 - OK - delete successfull
404 - Not Found - <contact id> not found
405 - Method Not Allowed - when <contact id> is missing
500 - Internal Server Error - when delete affected != 1 rows, calls also rollback (reference to contact is probably used in another table and contact cant't be deleted)
```

### List of storages - version 1
Get list of storages.

#### Request
Url:`<server>/heliosapi/storages`

Method: GET

Headers:
```
Accept: application/json
Authorization: Bearer <JWT token>
```

GET parameters:
```
number: {optional} <string length 1-30:storage number - search string> [TabStrom.Cislo],
name: {optional} <string length 1-40:storage name - search string> [TabStrom.Nazev],
centernumber: {optional} <string length 1-21:storage center number - search string> [TabStrom.CisloStr],
listfrom: {optional} <string:number of position from complete list where result begins>,
listto: {optional} <string:number of position from complete list where result ends>,
sort: {optional} <string:by which should be ordered> ('numberasc', 'numberdesc') [TabStrom.Cislo]
```

#### Response
Headers:
```
Content-Type: application/json
```

Output JSON object:
```
{ 
    rows: {
        Array {
            id: <integer:storage id> [TabStrom.Id],
            number: <string length 1-30:storage number> [TabStrom.Cislo],
            name: <string length 1-40:storage name> [TabStrom.Nazev],
            centernumber: <string length 1-21:storage center number> [TabStrom.CisloStr]
        }
    },
    totalrows: <integer:total count of rows of whole list from which is listfrom and listto returned>
}
```

Possible HTTP result codes:
```
200 - OK - successfull
400 - Bad Request - when GET parameters have no correct format
```

### Detail of storage - version 1
Get detail of specific storage.

#### Request
Url:`<server>/heliosapi/storages/<string:storage id>`

Method: GET

Headers:
```
Accept: application/json
Authorization: Bearer <JWT token>
```

GET parameters:
```
id: <string:storage id> [TabStrom.Id]
```

#### Response
Headers:
```
Content-Type: application/json
```

Output JSON object:
```
{
    id: <integer:storage id> [TabStrom.Id],
    number: <string length 1-30:storage number> [TabStrom.Cislo],
    name: <string length 1-40:storage name> [TabStrom.Nazev],
    centernumber: <string length 1-21:storage center number> [TabStrom.CisloStr],
    products: {
        Array {
                id: <integer:product id> [TabKmenZbozi.ID],
                regnum: <string:registration number> [TabKmenZbozi.RegCis],
                group: <string:group id> [TabKmenZbozi.SkupZbo],
                name1: <string:first product name> [TabKmenZbozi.Nazev1],
                name2: <string:second product name> [TabKmenZbozi.Nazev2],
                name3: <string:third product name> [TabKmenZbozi.Nazev3],
                name4: <string:fourth product name> [TabKmenZbozi.Nazev4],
                skp: <string:skp> [TabKmenZbozi.SKP],
                blocked: <integer:product is active or archived> (0 = active, 1 = archived) [TabKmenZbozi.Blokovano],
                storage: {
                    amount: <integer:amount of product in storage> [TabStavSkladu.Mnozstvi],
                    availableamount: <integer:available amount of product in storage> [TabStavSkladu.MnozstviDispo],
                    dispenseamount: <integer:amount of product to dispense in storage> [TabStavSkladu.MnozstviKVydeji]
                }

        }
    }
}
```

Possible HTTP result codes:
```
200 - OK - successfull
400 - Bad Request - when <storage id> is not a number
404 - Not Found - when storage with <storage id> does not exists
```
