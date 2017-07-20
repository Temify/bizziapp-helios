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

### List of clients
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
name: {optional} <string length 1-100:search string> [TabCisOrg.Nazev OR TabCisOrg.DruhyNazev],
nameisnotnull: {optional} <string:'true' = name is not null, 'false' = name is null, null = name can be null > [TabCisOrg.Nazev OR TabCisOrg.DruhyNazev],
status: {optional} <string:status> ('0', '1', '2', '3') [TabCisOrg.Stav],
listfrom: {optional} <string:number of position from complete list where result begins>,
listto: {optional} <string:number of position from complete list where result ends>,
sort: {optional} <string:by which should be ordered> ('nameasc', 'namedesc', 'ideasc', 'iddesc') [TabCisOrg.Nazev]
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
            email: <string:email> [],
            phone: <string:phone number> [],
            contact: <string:contact>  [TabCisOrg.Kontakt],
            website: <string:web URL> [],
            status: <integer:status 0 - 3> [TabCisOrg.Stav]
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

### Detail of client
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
    email: <string:email> [],
    phone: <string:phone number> [],
    address: {
        street: <string:street> [TabCisOrg.Ulice],
        streetorinumber: <string:orientation number> [TabCisOrg.OrCislo],
        streetdesnumber: <string:descriptive number> [TabCisOrg.PopCislo],
        city: <string:city> [TabCisOrg.Misto],
        zip: <string:zip code> [TabCisOrg.PSC]
    },
    contact: <string:contact>  [TabCisOrg.Kontakt],
    ic: <string:ic number> [TabCisOrg.ICO],
    dic: <string:dic number> [TabCisOrg.DIC],
    website: <string:web URL> [],
    status: <integer:status 0 - 3> [TabCisOrg.Stav]
}
```

Possible HTTP result codes:
```
200 - OK - successfull
400 - Bad Request - when <client id> is not a number
404 - Not Found - when client with <client id> does not exists
```

### Create new client
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
    status: {optional} <string:status '0' - '3' - default '0'> [TabCisOrg.Stav]
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

### Update client
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
    status: {optional} <integer:status 0 - 3> [TabCisOrg.Stav]
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

### Delete client
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

### List of products
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
sort: {optional} <string:by which should be ordered> ('nameasc', 'namedesc') [TabKmenZbozi.Nazev1]
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
            skp: <string:skp> [TabKmenZbozi.SKP]
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

### Detail of product
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
    name: <string:first product name> [TabKmenZbozi.Nazev1],
    name2: <string:second product name> [TabKmenZbozi.Nazev2],
    name3: <string:third product name> [TabKmenZbozi.Nazev3],
    name4: <string:fourth product name> [TabKmenZbozi.Nazev4],
    skp: <string:skp> [TabKmenZbozi.SKP],
    range: <integer:range of goods> [TabKmenZbozi.IdSortiment],
    vintage: <string:vintage> [],
    notice: <string:notice> [TabKmenZbozi.Upozorneni],
    note: <string:note> [TabKmenZbozi.Poznamka],
    muevidence: <string:measurement unit of evidence> [TabKmenZbozi.MJEvidence],
    mustocktaking: <string:measurement unit of stock-taking> [TabKmenZbozi.MJInventura],
    muinput: <string:measurement unit of input> [TabKmenZbozi.MJVstup],
    muoutput: <string:measurement unit of output> [TabKmenZbozi.MJVystup],
    vatinput: <float:vat input> [TabKmenZbozi.SazbaDPHVstup],
    vatoutput: <float:vat output> [TabKmenZbozi.SazbaDPHVystup],
    pdpcode: <integer:PDP code> [TabKmenZbozi.IDKodPDP],
    edinput: <float:excise duty input> [TabKmenZbozi.SazbaSDVstup],
    edoutput: <flost:excise duty output> [TabKmenZbozi.SazbaSDVystup],
    mued: <string:measurement unit of excise duty> [TabKmenZbozi.MJSD],
    edcode: <string:excise duty code> [TabKmenZbozi.KodSD],
    edcalc: <float:excise duty calculation> [TabKmenZbozi.PrepocetMJSD]
}
```

Possible HTTP result codes:
```
200 - OK - successfull
400 - Bad Request - when <client id> is not a number
404 - Not Found - when client with <client id> does not exists
```