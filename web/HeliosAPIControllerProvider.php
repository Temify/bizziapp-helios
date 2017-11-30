<?php

namespace HeliosAPI;

use Doctrine\DBAL\Connection;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;

class HeliosAPIControllerProvider implements ControllerProviderInterface
{
    private $_signKey = null;

    public function __construct(string $signKey)
    {
        $this->_signKey = $signKey;
    }

    public function connect(Application $app)
    {
        // Creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        // Get list of clients
        $controllers->get('/clients', function (Application $app) {
            $paramsOk = true;
            $result = new \stdClass();
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = $request->query->all();

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabCisOrg', 'TCO');

            // Name
            if (!empty($inputParams['name'])) {
                if (strlen($inputParams['name']) <= 100) {
                    $qb->andWhere('TCO.Nazev LIKE ?');
                    $sqlParams[] = '%' . $inputParams['name'] . '%';
                    $qb->orWhere('TCO.DruhyNazev LIKE ?');
                    $sqlParams[] = '%' . $inputParams['name'] . '%';
                } else
                    $paramsOk = false;
            }

            // Name is not null
            if (!empty($inputParams['nameisnotnull'])) {
                if ($inputParams['nameisnotnull'] == 'true') {
                    $qb->andWhere("(TCO.Nazev != '' OR TCO.DruhyNazev != '')");
                } else if ($inputParams['nameisnotnull'] == 'false') {
                    $qb->andWhere("TCO.Nazev = ''");
                    $qb->andWhere("TCO.DruhyNazev = ''");
                } else
                    $paramsOk = false;
            }

            // Status
            if (isset($inputParams['status']) && (!empty($inputParams['status']) || $inputParams['status'] == '0')) {
                if (is_numeric($inputParams['status'])) {
                    $qb->andWhere('TCO.Stav = ?');
                    $sqlParams[] = $inputParams['status'];
                } else
                    $paramsOk = false;
            }


            if ($paramsOk === false)
                $app->abort(400, "Bad Request.");

            // Get total rows count
            $qb->select('COUNT(TCO.ID) AS totalcount');
            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select client whole list rows - TOTAL COUNT:' . $qb->getSql());

            $totalRows = $app['db']->fetchAll($qb->getSql(), $sqlParams);
            $result->totalrows = (int)$totalRows[0]['totalcount'];

            $result->totalrows = $totalRows[0]['totalcount'];
            // Get part of lits
            $qb->select(
                'TCO.ID',
                'TCO.CisloOrg',
                'TCO.NadrizenaOrg',
                'TCO.Nazev',
                'TCO.DruhyNazev',
                'TCO.Ulice',
                'TCO.OrCislo',
                'TCO.PopCislo',
                'TCO.Misto',
                'TCO.PSC',
                'TCO.IdZeme',
                'TCO.Kontakt',
                'TCO.ICO',
                'TCO.DIC',
                'TCO.Stav',
                'TCZ.Jmeno',
                'TCZ.Prijmeni',
                'TCZ.AdrTrvUlice',
                'TCZ.AdrTrvOrCislo',
                'TCZ.AdrTrvPopCislo',
                'TCZ.AdrTrvMisto',
                'TCZ.AdrTrvPSC',
                'TCZ.AdrTrvZeme'
            );


            // Responsible person
            $qb->leftJoin('TCO', 'TabCisZam', 'TCZ', 'TCO.OdpOs = TCZ.ID');

            // Limit from
            if (!empty($inputParams['listfrom']))
                if (is_numeric($inputParams['listfrom']))
                    $qb->setFirstResult($inputParams['listfrom']);
                else
                    $paramsOk = false;

            // Limit to
            if (!empty($inputParams['listto']))
                if (is_numeric($inputParams['listto']))
                    $qb->setMaxResults($inputParams['listto']);
                else
                    $paramsOk = false;

            // Sort
            if (!empty($inputParams['sort'])) {
                switch ($inputParams['sort']) {
                    case 'idasc': {
                        $qb->orderBy('TCO.ID', 'ASC');
                        break;
                    }

                    case 'iddesc': {
                        $qb->orderBy('TCO.ID', 'DESC');
                        break;
                    }

                    case 'nameasc': {
                        $qb->orderBy('TCO.Nazev', 'ASC');
                        break;
                    }

                    case 'namedesc': {
                        $qb->orderBy('TCO.Nazev', 'DESC');
                        break;
                    }

                    default: {
                        $paramsOk = false;
                        break;
                    }
                }
            }

            if ($paramsOk === false)
                $app->abort(400, "Bad Request.");

            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select client list :' . $qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);
            $contactReferences = [];

            foreach ($listData as $row) {
                $newRow = new \stdClass();
                $newRow->id = (int)$row['ID'];
                $newRow->orgnum = (int)$row['CisloOrg'];
                $newRow->parentid = (int)$row['NadrizenaOrg'];
                $newRow->name = $row['DruhyNazev'] == "" ? $row['Nazev'] : $row['Nazev'] . " (" . $row['DruhyNazev'] . ")";
                $newRow->contact = $row['Kontakt'];
                $newRow->ic = $row['ICO'];
                $newRow->dic = $row['DIC'];
                $newRow->status = (int)$row['Stav'];
                $newRow->address = new \stdClass();
                $newRow->address->street = $row['Ulice'];
                $newRow->address->streetorinumber = $row['OrCislo'];
                $newRow->address->streetdesnumber = $row['PopCislo'];
                $newRow->address->city = $row['Misto'];
                $newRow->address->zip = $row['PSC'];
                $newRow->address->country = $row['IdZeme'];
                $newRow->responsibleperson = new \stdClass();
                $newRow->responsibleperson->firstname = $row['Jmeno'];
                $newRow->responsibleperson->lastname = $row['Prijmeni'];
                $newRow->responsibleperson->street = $row['AdrTrvUlice'];
                $newRow->responsibleperson->streetornumber = $row['AdrTrvOrCislo'];
                $newRow->responsibleperson->streetdesnumber = $row['AdrTrvPopCislo'];
                $newRow->responsibleperson->city = $row['AdrTrvMisto'];
                $newRow->responsibleperson->zip = $row['AdrTrvPSC'];
                $newRow->responsibleperson->country = $row['AdrTrvZeme'];

                $contactReferences[(int)$row['ID']] = ['email' => [], 'phone' => [], 'website' => []];
                $newRow->email = &$contactReferences[(int)$row['ID']]['email'];
                $newRow->phone = &$contactReferences[(int)$row['ID']]['phone'];
                $newRow->website = &$contactReferences[(int)$row['ID']]['website'];

                $result->rows[] = $newRow;
            }

            //Get contacts
            //If only sublist is returned
            if (count($result->rows) < $result->totalrows) {
                // print 'SELECT IDOrg, Druh, Spojeni, Spojeni2 FROM TabKontakty WHERE TabKontakty.IDOrg = ?'.print_r(array_keys($contactReferences), true);
                // die();
                $listDataContact = $app['db']->fetchAll('SELECT IDOrg, Druh, Spojeni, Spojeni2 FROM TabKontakty WHERE TabKontakty.IDOrg IN (?)', array_keys($contactReferences));
            } else
                $listDataContact = $app['db']->fetchAll('SELECT IDOrg, Druh, Spojeni, Spojeni2 FROM TabKontakty WHERE TabKontakty.IDOrg IS NOT NULL');

            foreach ($listDataContact as $rowContact) {

                //'1' = phone-hard line, '2' = phone-mobile, '3' = fax, '4' = telex, '5' = operator, '6' = email, '7' = website, '8' = ico, '9' = ip address, '10' = bulk for email, '11' = skype, '12' = windows live messenger, '13' = login id, '14' = sms, '15' = data box
                switch ($rowContact['Druh']) {
                    case 6: //Email
                    case 10: {
                        $contactReferences[(int)$rowContact['IDOrg']]['email'][] = (!empty($rowContact['Spojeni'])) ? (!empty($rowContact['Spojeni2'])) ? $rowContact['Spojeni'] . ',' . $rowContact['Spojeni2'] : $rowContact['Spojeni'] : $rowContact['Spojeni2'];
                        break;
                    }
                    case 1: //Phone
                    case 2: {
                        $contactReferences[(int)$rowContact['IDOrg']]['phone'][] = (!empty($rowContact['Spojeni'])) ? (!empty($rowContact['Spojeni2'])) ? $rowContact['Spojeni'] . ',' . $rowContact['Spojeni2'] : $rowContact['Spojeni'] : $rowContact['Spojeni2'];
                        break;
                    }
                    case 7: //Website
                    {
                        $contactReferences[(int)$rowContact['IDOrg']]['website'][] = (!empty($rowContact['Spojeni'])) ? (!empty($rowContact['Spojeni2'])) ? $rowContact['Spojeni'] . ',' . $rowContact['Spojeni2'] : $rowContact['Spojeni'] : $rowContact['Spojeni2'];
                        break;
                    }
                }
            }

            //Construct response
            if ($app['debug']) $app['monolog']->info('Response: data:' . json_encode($result));
            return $app->json($result);
        });

        //Get only clients with parent-id not null
        $controllers->get('/clients-parent', function (Application $app) {
            $result = new \stdClass();

            $qb = $app['db']->createQueryBuilder();

            $qb->from('TabCisOrg', 'TCO_A')
                ->select(
                    'TCO_A.ID',
                    'TCO_B.ID AS ParentID'
                )
                ->leftJoin('TCO_A', 'TabCisOrg', 'TCO_B', 'TCO_A.NadrizenaOrg = TCO_B.CisloOrg')
                ->where('TCO_A.NadrizenaOrg IS NOT NULL')
                ->orderBy('TCO_A.ID', 'ASC');

            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select client list :' . $qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql());

            foreach ($listData as $row) {
                $newRow = new \stdClass();
                $newRow->id = (int)$row['ID'];
                $newRow->parentid = (int)$row['ParentID'];
                $result->rows[] = $newRow;
            }

            //Construct response
            if ($app['debug']) $app['monolog']->info('Response: data:' . json_encode($result));
            return $app->json($result);


        });
        // Get detail of client
        $controllers->get('/clients/{id}', function (Application $app, $id) {
            if (empty($id) || !is_numeric($id))
                $app->abort(400, "Bad Request.");

            $result = new \stdClass();

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabCisOrg', 'TCO');

            // Id
            $qb->andWhere('TCO.ID = ?');
            $sqlParams[] = $id;

            // Get data
            $qb->select(
                'TCO.ID',
                'TCO.CisloOrg',
                'TCO.NadrizenaOrg',
                'TCO.Nazev',
                'TCO.DruhyNazev',
                'TCO.Ulice',
                'TCO.OrCislo',
                'TCO.PopCislo',
                'TCO.Misto',
                'TCO.PSC',
                'TCO.IdZeme',
                'TCO.Kontakt',
                'TCO.ICO',
                'TCO.DIC',
                'TCO.Stav',
                'TCZ.Jmeno',
                'TCZ.Prijmeni',
                'TCZ.AdrTrvUlice',
                'TCZ.AdrTrvOrCislo',
                'TCZ.AdrTrvPopCislo',
                'TCZ.AdrTrvMisto',
                'TCZ.AdrTrvPSC',
                'TCZ.AdrTrvZeme'
            );

            // Responsible person
            $qb->leftJoin('TCO', 'TabCisZam', 'TCZ', 'TCO.OdpOs = TCZ.ID');

            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select client detail :' . $qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            if (count($listData) < 1)
                $app->abort(404, "Not Found.");

            foreach ($listData as $row) {
                $newRow = new \stdClass();
                $newRow->id = (int)$row['ID'];
                $newRow->orgnum = (int)$row['CisloOrg'];
                $newRow->parentid = (int)$row['NadrizenaOrg'];
                $newRow->name = $row['Nazev'];
                $newRow->name2 = $row['DruhyNazev'];
                $newRow->email = [];
                $newRow->phone = [];
                $newRow->website = [];
                $newRow->address = new \stdClass();
                $newRow->address->street = $row['Ulice'];
                $newRow->address->streetorinumber = $row['OrCislo'];
                $newRow->address->streetdesnumber = $row['PopCislo'];
                $newRow->address->city = $row['Misto'];
                $newRow->address->zip = $row['PSC'];
                $newRow->address->country = $row['IdZeme'];
                $newRow->contact = $row['Kontakt'];
                $newRow->ic = $row['ICO'];
                $newRow->dic = $row['DIC'];
                $newRow->status = (int)$row['Stav'];

                $newRow->responsibleperson = new \stdClass();
                $newRow->responsibleperson->firstname = $row['Jmeno'];
                $newRow->responsibleperson->lastname = $row['Prijmeni'];
                $newRow->responsibleperson->street = $row['AdrTrvUlice'];
                $newRow->responsibleperson->streetornumber = $row['AdrTrvOrCislo'];
                $newRow->responsibleperson->streetdesnumber = $row['AdrTrvPopCislo'];
                $newRow->responsibleperson->city = $row['AdrTrvMisto'];
                $newRow->responsibleperson->zip = $row['AdrTrvPSC'];
                $newRow->responsibleperson->country = $row['AdrTrvZeme'];

                $listDataContact = $app['db']->fetchAll('SELECT * FROM TabKontakty WHERE TabKontakty.IDOrg = ?', Array($row['ID']));
                foreach ($listDataContact as $rowContact) {
                    //'1' = phone-hard line, '2' = phone-mobile, '3' = fax, '4' = telex, '5' = operator, '6' = email, '7' = website, '8' = ico, '9' = ip address, '10' = bulk for email, '11' = skype, '12' = windows live messenger, '13' = login id, '14' = sms, '15' = data box
                    switch ($rowContact['Druh']) {
                        case 6: //Email
                        case 10: {
                            $newRow->email[] = (!empty($rowContact['Spojeni'])) ? (!empty($rowContact['Spojeni2'])) ? $rowContact['Spojeni'] . ',' . $rowContact['Spojeni2'] : $rowContact['Spojeni'] : $rowContact['Spojeni2'];
                            break;
                        }
                        case 1: //Phone
                        case 2: {
                            $newRow->phone[] = (!empty($rowContact['Spojeni'])) ? (!empty($rowContact['Spojeni2'])) ? $rowContact['Spojeni'] . ',' . $rowContact['Spojeni2'] : $rowContact['Spojeni'] : $rowContact['Spojeni2'];
                            break;
                        }
                        case 7: //Website
                        {
                            $newRow->website[] = (!empty($rowContact['Spojeni'])) ? (!empty($rowContact['Spojeni2'])) ? $rowContact['Spojeni'] . ',' . $rowContact['Spojeni2'] : $rowContact['Spojeni'] : $rowContact['Spojeni2'];
                            break;
                        }
                    }
                }

                $result = $newRow;
            }

            //Construct response
            if ($app['debug']) $app['monolog']->info('Response: data:' . json_encode($result));
            return $app->json($result);
        });

        // Create new client
        $controllers->post('/clients', function (Application $app) {
            $paramsOk = true;
            $newClientId = null;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);
            $qb = $app['db']->createQueryBuilder();

            // Check data
            $sqlParams = Array();

            // Default value: orgnum
            if (empty($inputParams['orgnum'])) {
                $sql = "DECLARE @CisloOrg INT;EXEC @CisloOrg=dbo.hp_NajdiPrvniVolny 'TabCisOrg','CisloOrg',1,2147483647,'',1,1;SELECT @CisloOrg AS neworgnum;";
                if ($app['debug']) $app['monolog']->info('DB Select new orgnum:' . $sql);
                $queryResult = $app['db']->executeQuery($sql);
                $newOrgnum = $queryResult->fetch();
                $inputParams['orgnum'] = (int)$newOrgnum['neworgnum'];
            }

            // Check if client already exists
            $sql = 'SELECT 1 AS clientexists FROM TabCisOrg WHERE TabCisOrg.CisloOrg = ' . $inputParams['orgnum'];
            if ($app['debug']) $app['monolog']->info('DB Check if client with orgnum exists:' . $sql);
            $queryResult = $app['db']->executeQuery($sql);
            $clientExists = $queryResult->fetch();
            if ($clientExists['clientexists'] == 1)
                $app->abort(409, "Conflict.");

            // Required fields
            if (
                $inputParams['orgnum'] != null && is_numeric($inputParams['orgnum']) &&
                $inputParams['name'] != null && strlen($inputParams['name']) <= 100
            ) {
                $sqlParams['CisloOrg'] = $inputParams['orgnum'];
                $sqlParams['Nazev'] = $inputParams['name'];
            } else
                $app->abort(400, "Bad Request.");

            // Optional fields
            if ($inputParams['status'] != null)
                if (is_numeric($inputParams['status']))
                    $sqlParams['Stav'] = $inputParams['status'];
                else
                    $paramsOk = false;

            if ($inputParams['name2'] != null)
                if (strlen($inputParams['name2']) <= 100)
                    $sqlParams['DruhyNazev'] = $inputParams['name2'];
                else
                    $paramsOk = false;

            if ($inputParams['street'] != null)
                if (strlen($inputParams['street']) <= 100)
                    $sqlParams['Ulice'] = $inputParams['street'];
                else
                    $paramsOk = false;

            if ($inputParams['city'] != null)
                if (strlen($inputParams['city']) <= 100)
                    $sqlParams['Misto'] = $inputParams['city'];
                else
                    $paramsOk = false;

            if ($inputParams['streetorinumber'] != null)
                if (is_numeric($inputParams['streetorinumber']))
                    $sqlParams['OrCislo'] = $inputParams['streetorinumber'];
                else
                    $paramsOk = false;

            if ($inputParams['streetdesnumber'] != null)
                if (is_numeric($inputParams['streetdesnumber']))
                    $sqlParams['PopCislo'] = $inputParams['streetdesnumber'];
                else
                    $paramsOk = false;

            if ($inputParams['parentid'] != null)
                if (is_numeric($inputParams['parentid']))
                    $sqlParams['NadrizenaOrg'] = $inputParams['parentid'];
                else
                    $paramsOk = false;

            if ($inputParams['zip'] != null)
                if (strlen($inputParams['zip']) <= 10)
                    $sqlParams['PSC'] = $inputParams['zip'];
                else
                    $paramsOk = false;
            if ($inputParams['contact'] != null)
                if (strlen($inputParams['contact']) <= 40)
                    $sqlParams['Kontakt'] = $inputParams['contact'];
                else
                    $paramsOk = false;
            if ($inputParams['ic'] != null)
                if (strlen($inputParams['ic']) <= 20)
                    $sqlParams['ICO'] = $inputParams['ic'];
                else
                    $paramsOk = false;
            if ($inputParams['dic'] != null)
                if (strlen($inputParams['dic']) <= 15)
                    $sqlParams['DIC'] = $inputParams['dic'];
                else
                    $paramsOk = false;

            if ($paramsOk === false)
                $app->abort(400, "Bad Request.");


            $app['db']->beginTransaction();
            $result = $app['db']->insert('TabCisOrg', $sqlParams);
            $newClientId = $app['db']->lastInsertId();

            // If exactly 1 row was affected            
            if ($result === 1)
                $app['db']->commit();
            else {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response = new Response(json_encode(array('id' => (int)$newClientId)), 201);
            $response->headers->set('Location', 'clients/' . $newClientId);
            return $response;
        });

        // Update all clients - method not allowed
        $controllers->put('/clients', function (Application $app) {
            $app->abort(405, "Method Not Allowed.");
        });

        // Update client
        $controllers->put('/clients/{id}', function (Application $app, $id) {
            if (empty($id) || !is_numeric($id))
                $app->abort(400, "Bad Request.");

            $paramsOk = true;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);

            // Check if client exists
            $qb = $app['db']->createQueryBuilder();
            $qb->select(
                'TabCisOrg.ID',
                'TabCisOrg.CisloOrg',
                'TabCisOrg.Stav'
            );
            $qb->from('TabCisOrg');
            $qb->andWhere('TabCisOrg.ID = ?');
            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select client by ID :' . $qb->getSql());
            $clientData = $app['db']->fetchAssoc($qb->getSql(), array($id));
            if (!is_array($clientData) || count($clientData) <= 0)
                $app->abort(404, "Not Found.");

            // Check data
            if (count($inputParams) < 1)
                $app->abort(204, "No Content.");

            // Optional fields - but must be at least one
            $sqlParams = Array();
            if ($inputParams['orgnum'] != null)
                if (is_numeric($inputParams['orgnum']))
                    $sqlParams['CisloOrg'] = $inputParams['orgnum'];
                else
                    $paramsOk = false;
            if ($inputParams['name'] != null)
                if (strlen($inputParams['name']) <= 100)
                    $sqlParams['Nazev'] = $inputParams['name'];
                else
                    $paramsOk = false;
            if ($inputParams['name2'] != null)
                if (strlen($inputParams['name2']) <= 100)
                    $sqlParams['DruhyNazev'] = $inputParams['name2'];
                else
                    $paramsOk = false;
            if ($inputParams['street'] != null)
                if (strlen($inputParams['street']) <= 100)
                    $sqlParams['Ulice'] = $inputParams['street'];
                else
                    $paramsOk = false;
            if ($inputParams['streetorinumber'] != null)
                if (strlen($inputParams['streetorinumber']) <= 15)
                    $sqlParams['OrCislo'] = $inputParams['streetorinumber'];
                else
                    $paramsOk = false;
            if ($inputParams['streetdesnumber'] != null)
                if (strlen($inputParams['streetdesnumber']) <= 15)
                    $sqlParams['PopCislo'] = $inputParams['streetdesnumber'];
                else
                    $paramsOk = false;
            if ($inputParams['city'] != null)
                if (strlen($inputParams['city']) <= 100)
                    $sqlParams['Misto'] = $inputParams['city'];
                else
                    $paramsOk = false;
            if ($inputParams['status'] != null)
                if (is_numeric($inputParams['status']))
                    $sqlParams['Stav'] = $inputParams['status'];
                else
                    $paramsOk = false;
            if ($inputParams['parentid'] != null)
                if (is_numeric($inputParams['parentid']))
                    $sqlParams['NadrizenaOrg'] = $inputParams['parentid'];
                else
                    $paramsOk = false;
            if ($inputParams['zip'] != null)
                if (strlen($inputParams['zip']) <= 10)
                    $sqlParams['PSC'] = $inputParams['zip'];
                else
                    $paramsOk = false;
            if ($inputParams['contact'] != null)
                if (strlen($inputParams['contact']) <= 40)
                    $sqlParams['Kontakt'] = $inputParams['contact'];
                else
                    $paramsOk = false;
            if ($inputParams['ic'] != null)
                if (strlen($inputParams['ic']) <= 20)
                    $sqlParams['ICO'] = $inputParams['ic'];
                else
                    $paramsOk = false;
            if ($inputParams['dic'] != null)
                if (strlen($inputParams['dic']) <= 15)
                    $sqlParams['DIC'] = $inputParams['dic'];
                else
                    $paramsOk = false;

            // No input data received or bad format data
            if (count($sqlParams) < 1 || $paramsOk === false)
                $app->abort(400, "Bad Request.");

            $app['db']->beginTransaction();
            $result = $app['db']->update('TabCisOrg', $sqlParams, array('ID' => $id));

            // If exactly 1 row was affected            
            if ($result === 1)
                $app['db']->commit();
            else {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response = new Response(null, 200);
            return $response;
        });

        // Delete all clients - method not allowed
        $controllers->delete('/clients', function (Application $app) {
            $app->abort(405, "Method Not Allowed.");
        });

        // Delete client
        $controllers->delete('/clients/{id}', function (Application $app, $id) {
            if (empty($id) || !is_numeric($id))
                $app->abort(400, "Bad Request.");

            $paramsOk = true;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);

            // Check if client exists
            $qb = $app['db']->createQueryBuilder();
            $qb->select(
                'TabCisOrg.ID',
                'TabCisOrg.CisloOrg',
                'TabCisOrg.Stav'
            );
            $qb->from('TabCisOrg');
            $qb->andWhere('TabCisOrg.ID = ?');
            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select client by ID :' . $qb->getSql());
            $clientData = $app['db']->fetchAssoc($qb->getSql(), array($id));
            if (!is_array($clientData) || count($clientData) <= 0)
                $app->abort(404, "Not Found.");

            $app['db']->beginTransaction();
            $result = $app['db']->update('TabCisOrg', array('Stav' => 1), array('ID' => $id));

            // If exactly 1 row was affected            
            if ($result === 1)
                $app['db']->commit();
            else {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response = new Response(null, 200);
            return $response;
        });

        // Get list of contacts
        $controllers->get('/contacts', function (Application $app) {
            $paramsOk = true;
            $result = new \stdClass();
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = $request->query->all();

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabKontakty');

            // Type
            if (!empty($inputParams['type'])) {
                if (strlen($inputParams['type']) <= 5 && is_numeric($inputParams['type'])) {
                    $qb->andWhere('TabKontakty.Druh = ?');
                    $sqlParams[] = $inputParams['type'];
                } else
                    $paramsOk = false;
            }

            // Organisation id
            if (!empty($inputParams['orgid'])) {
                if (strlen($inputParams['orgid']) <= 10 && is_numeric($inputParams['orgid'])) {
                    $qb->andWhere('TabKontakty.IDOrg = ?');
                    $sqlParams[] = $inputParams['orgid'];
                } else
                    $paramsOk = false;
            }

            // Primary
            if (!empty($inputParams['primary']) || $inputParams['primary'] == '0') {
                if (strlen($inputParams['primary']) <= 1 && is_numeric($inputParams['primary'])) {
                    $qb->andWhere('TabKontakty.Prednastaveno = ?');
                    $sqlParams[] = $inputParams['primary'];
                } else
                    $paramsOk = false;
            }

            // Description
            if (!empty($inputParams['description'])) {
                if (strlen($inputParams['description']) <= 255) {
                    $qb->andWhere('TabKontakty.Popis LIKE ?');
                    $sqlParams[] = '%' . $inputParams['description'] . '%';
                } else
                    $paramsOk = false;
            }

            // Connection
            if (!empty($inputParams['connection'])) {
                if (strlen($inputParams['connection']) <= 255) {
                    $qb->andWhere('TabKontakty.Spojeni LIKE ?');
                    $sqlParams[] = '%' . $inputParams['connection'] . '%';
                } else
                    $paramsOk = false;
            }

            // Connection 2
            if (!empty($inputParams['connection2'])) {
                if (strlen($inputParams['connection2']) <= 255) {
                    $qb->andWhere('TabKontakty.Spojeni2 LIKE ?');
                    $sqlParams[] = '%' . $inputParams['connection2'] . '%';
                } else
                    $paramsOk = false;
            }

            if ($paramsOk === false)
                $app->abort(400, "Bad Request.");

            // Get total rows count
            $qb->select('COUNT(TabKontakty.ID) AS totalcount');
            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select contact whole list rows - TOTAL COUNT:' . $qb->getSql());

            $totalRows = $app['db']->fetchAll($qb->getSql(), $sqlParams);
            $result->totalrows = (int)$totalRows[0]['totalcount'];

            $result->totalrows = $totalRows[0]['totalcount'];
            // Get part of lits
            $qb->select(
                'TabKontakty.ID',
                'TabKontakty.IDOrg',
                'TabKontakty.Druh',
                'TabKontakty.Prednastaveno',
                'TabKontakty.Popis',
                'TabKontakty.Spojeni',
                'TabKontakty.Spojeni2'
            );

            // Limit from
            if (!empty($inputParams['listfrom']))
                if (is_numeric($inputParams['listfrom']))
                    $qb->setFirstResult($inputParams['listfrom']);
                else
                    $paramsOk = false;

            // Limit to
            if (!empty($inputParams['listto']))
                if (is_numeric($inputParams['listto']))
                    $qb->setMaxResults($inputParams['listto']);
                else
                    $paramsOk = false;

            // Sort
            if (!empty($inputParams['sort'])) {
                switch ($inputParams['sort']) {
                    case 'typeasc': {
                        $qb->orderBy('TabKontakty.Druh', 'ASC');
                        break;
                    }

                    case 'typedesc': {
                        $qb->orderBy('TabKontakty.Druh', 'DESC');
                        break;
                    }

                    case 'connectionasc': {
                        $qb->orderBy('TabKontakty.Spojeni', 'ASC');
                        break;
                    }

                    case 'connectiondesc': {
                        $qb->orderBy('TabKontakty.Spojeni', 'DESC');
                        break;
                    }

                    case 'idasc': {
                        $qb->orderBy('TabKontakty.ID', 'ASC');
                        break;
                    }

                    case 'iddesc': {
                        $qb->orderBy('TabKontakty.ID', 'DESC');
                        break;
                    }

                    default: {
                        $paramsOk = false;
                        break;
                    }
                }
            }

            if ($paramsOk === false)
                $app->abort(400, "Bad Request.");

            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select contact list :' . $qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            foreach ($listData as $row) {
                if ($row['Popis'] != "") {
                    $update = false;
                    foreach ($result->rows as $resultRow) {
                        if ($resultRow->orgid == (int)$row['IDOrg'] && $resultRow->description == $row['Popis']
                            && $resultRow->description != ""
                        ) {
                            if ((int)$row['Druh'] > 0 && (int)$row['Druh'] < 5) {
                                $resultRow->telephone .= $resultRow->telephone == "" ? $row['Spojeni'] : ', ' . $row['Spojeni'];
                                $resultRow->telephone .= $row['Spojeni2'] !== "" ? ", " . $row['Spojeni2'] : "";
                            }
                            if ((int)$row['Druh'] == 6) {
                                $resultRow->email .= $resultRow->email == "" ? $row['Spojeni'] : ', ' . $row['Spojeni'];
                                $resultRow->email .= $row['Spojeni2'] !== "" ? ", " . $row['Spojeni2'] : "";
                            }
                            $update = true;
                            break;
                        }
                    }
                    if ($update) continue;
                }
                $newRow = new \stdClass();
                $newRow->id = (int)$row['ID'];
                $newRow->orgid = (int)$row['IDOrg'];
                $newRow->telephone = "";
                $newRow->email = "";
                if ((int)$row['Druh'] > 0 && (int)$row['Druh'] < 5) {
                    $newRow->telephone = $row['Spojeni'];
                    $newRow->telephone .= $row['Spojeni2'] !== "" ? ", " . $row['Spojeni2'] : "";
                }
                if ((int)$row['Druh'] == 6) {
                    $newRow->email = $row['Spojeni'];
                    $newRow->email .= $row['Spojeni2'] !== "" ? ", " . $row['Spojeni2'] : "";
                }
                $newRow->primary = (int)$row['Prednastaveno'];
                $newRow->description = $row['Popis'];
                $result->rows[] = $newRow;
            }

            //Construct response
            if ($app['debug']) $app['monolog']->info('Response: data:' . json_encode($result));
            return $app->json($result);
        });

        // Get detail of contact
        $controllers->get('/contacts/{id}', function (Application $app, $id) {
            if (empty($id) || !is_numeric($id))
                $app->abort(400, "Bad Request.");

            $result = new \stdClass();

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabKontakty');

            // Id
            $qb->andWhere('TabKontakty.ID = ?');
            $sqlParams[] = $id;

            // Get data
            $qb->select(
                'TabKontakty.ID',
                'TabKontakty.IDOrg',
                'TabKontakty.Druh',
                'TabKontakty.Prednastaveno',
                'TabKontakty.Popis',
                'TabKontakty.Spojeni',
                'TabKontakty.Spojeni2'
            );

            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select contact detail :' . $qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            if (count($listData) < 1)
                $app->abort(404, "Not Found.");

            foreach ($listData as $row) {
                $newRow = new \stdClass();
                $newRow->id = (int)$row['ID'];
                $newRow->orgid = (int)$row['IDOrg'];
                $newRow->type = (int)$row['Druh'];
                $newRow->primary = (int)$row['Prednastaveno'];
                $newRow->description = $row['Popis'];
                $newRow->connection = $row['Spojeni'];
                $newRow->connection2 = $row['Spojeni2'];
                $result = $newRow;
            }

            //Construct response
            if ($app['debug']) $app['monolog']->info('Response: data:' . json_encode($result));
            return $app->json($result);
        });

        // Create new contact
        $controllers->post('/contacts', function (Application $app) {
            $paramsOk = true;
            $newClientId = null;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);

            // Check data
            $sqlParams = Array();

            // Default value: primary
            if (empty($inputParams['primary']))
                $inputParams['primary'] = 0;


            // Required fields
            if (($inputParams['primary'] != null || $inputParams['primary'] == 0) && is_numeric($inputParams['primary']))
                $sqlParams['Prednastaveno'] = $inputParams['primary'];
            else
                $app->abort(400, "Bad Request.");

            // Optional fields
            if ($inputParams['fullname'] != null)
                if (strlen($inputParams['fullname']) <= 255)
                    $sqlParams['Popis'] = $inputParams['fullname'];
                else
                    $paramsOk = false;

            if ($inputParams['telephone'] != null)
                if (strlen($inputParams['telephone']) <= 255)
                    $sqlParams['Spojeni'] = $inputParams['telephone'];
                else
                    $paramsOk = false;

            if ($inputParams['email'] != null)
                if (strlen($inputParams['email']) <= 255)
                    $sqlParams['Spojeni2'] = $inputParams['email'];
                else
                    $paramsOk = false;

            if ($paramsOk === false)
                $app->abort(400, "Bad Request.");

            if ($inputParams['telephone'] != null && $inputParams['email'] != null)
                $sqlParams['Druh'] = 4;
            elseif ($inputParams['telephone' != null])
                $sqlParams['Druh'] = 2;
            elseif ($inputParams['email'] != null)
                $sqlParams['Druh'] = 6;
            else
                $sqlParams['Druh'] = 0;

            $qb = $app['db']->createQueryBuilder();
            $app['db']->beginTransaction();
            $result = $app['db']->insert('TabKontakty', $sqlParams);
            $newContactId = $app['db']->lastInsertId();

            // If exactly 1 row was affected            
            if ($result === 1)
                $app['db']->commit();
            else {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response = new Response(json_encode(array('id' => (int)$newContactId)), 201);
            $response->headers->set('Location', 'contacts/' . $newContactId);
            return $response;
        });

        // Update all contacts - method not allowed
        $controllers->put('/contacts', function (Application $app) {
            $app->abort(405, "Method Not Allowed.");
        });

        // Update contact
        $controllers->put('/contacts/{id}', function (Application $app, $id) {
            if (empty($id) || !is_numeric($id))
                $app->abort(400, "Bad Request.");

            $paramsOk = true;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);

            // Check if contact exists
            $qb = $app['db']->createQueryBuilder();
            $qb->select(
                'TabKontakty.ID'
            );
            $qb->from('TabKontakty');
            $qb->andWhere('TabKontakty.ID = ?');
            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select contact by ID :' . $qb->getSql());
            $contactData = $app['db']->fetchAssoc($qb->getSql(), array($id));
            if (!is_array($contactData) || count($contactData) <= 0)
                $app->abort(404, "Not Found.");

            // Check data
            if (count($inputParams) < 1)
                $app->abort(204, "No Content.");

            // Optional fields - but must be at least one
            $sqlParams = Array();
            if ($inputParams['orgid'] != null)
                if (is_numeric($inputParams['orgid']))
                    $sqlParams['IDOrg'] = $inputParams['orgid'];
                else
                    $paramsOk = false;

            if ($inputParams['type'] != null)
                if (is_numeric($inputParams['type']))
                    $sqlParams['Druh'] = $inputParams['type'];
                else
                    $paramsOk = false;

            if (($inputParams['primary'] != null || $inputParams['primary'] == '0'))
                if (is_numeric($inputParams['primary']))
                    $sqlParams['Prednastaveno'] = $inputParams['primary'];
                else
                    $paramsOk = false;

            if ($inputParams['fullname'] != null)
                if (strlen($inputParams['fullname']) <= 255)
                    $sqlParams['Popis'] = $inputParams['fullname'];
                else
                    $paramsOk = false;

            if ($inputParams['telephone'] != null)
                if (strlen($inputParams['telephone']) <= 255)
                    $sqlParams['Spojeni'] = $inputParams['telephone'];
                else
                    $paramsOk = false;

            if ($inputParams['email'] != null)
                if (strlen($inputParams['email']) <= 255)
                    $sqlParams['Spojeni2'] = $inputParams['email'];
                else
                    $paramsOk = false;

            // No input data received or bad format data
            if (count($sqlParams) < 1 || $paramsOk === false)
                $app->abort(400, "Bad Request.");

            $app['db']->beginTransaction();
            $result = $app['db']->update('TabKontakty', $sqlParams, array('ID' => $id));

            // If exactly 1 row was affected            
            if ($result === 1)
                $app['db']->commit();
            else {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response = new Response(null, 200);
            return $response;
        });

        // Delete all contacts - method not allowed
        $controllers->delete('/contacts', function (Application $app) {
            $app->abort(405, "Method Not Allowed.");
        });

        // Delete contact
        $controllers->delete('/contacts/{id}', function (Application $app, $id) {
            if (empty($id) || !is_numeric($id))
                $app->abort(400, "Bad Request.");

            $paramsOk = true;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);

            // Check if contact exists
            $qb = $app['db']->createQueryBuilder();
            $qb->select(
                'TabKontakty.ID'
            );
            $qb->from('TabKontakty');
            $qb->andWhere('TabKontakty.ID = ?');
            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select contact by ID :' . $qb->getSql());
            $contactData = $app['db']->fetchAssoc($qb->getSql(), array($id));
            if (!is_array($contactData) || count($contactData) <= 0)
                $app->abort(404, "Not Found.");

            $app['db']->beginTransaction();
            $result = $app['db']->delete('TabKontakty', array('ID' => $id));

            // If exactly 1 row was affected            
            if ($result === 1)
                $app['db']->commit();
            else {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response = new Response(null, 200);
            return $response;
        });

        // Get list of products
        $controllers->get('/products', function (Application $app) {
            $paramsOk = true;
            $result = new \stdClass();
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = $request->query->all();

            $qb2 = $app['db']->createQueryBuilder();
            $qb2->from('TabKmenZbozi', 'TKZ');
            $qb2->leftJoin('TKZ', 'TabPohybyZbozi', 'TPZ', 'TKZ.RegCis = TPZ.RegCis');
            $qb2->leftJoin('TPZ', 'TabDokladyZbozi', 'TDZ', 'TPZ.IDDoklad = TDZ.ID AND TDZ.DruhPohybuZbo = 6');
            $qb2->leftJoin('TKZ', 'TabNC', 'TNC', 'TKZ.ID = TNC.IDKmenZbozi AND TNC.CenovaUroven = 1');
            $qb2->select('TKZ.ID', 'TDZ.Splatnost');
            $qb2->where('TDZ.Splatnost IS NOT NULL');
            $app['db']->prepare($qb2->getSql());
            $listData2 = $app['db']->fetchAll($qb2->getSql());
            $orderDates = [];
            foreach ($listData2 as $item) {
                $orderDates[$item['ID']] = $item['Splatnost'];
            }

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabKmenZbozi', 'TKZ');
            $qb->innerJoin('TKZ', 'TabStavSkladu', 'TSS', 'TSS.IDKmenZbozi = TKZ.ID AND (TSS.IDSklad = \'00100001\' OR TSS.IDSklad = \'00100002\')');
            // Name
            if (!empty($inputParams['name'])) {
                if (strlen($inputParams['name']) <= 100) {
                    $qb->andWhere('TKZ.Nazev1 LIKE ?');
                    $sqlParams[] = '%' . $inputParams['name'] . '%';
                    $qb->orWhere('TKZ.Nazev2 LIKE ?');
                    $sqlParams[] = '%' . $inputParams['name'] . '%';
                    $qb->orWhere('TKZ.Nazev3 LIKE ?');
                    $sqlParams[] = '%' . $inputParams['name'] . '%';
                    $qb->orWhere('TKZ.Nazev4 LIKE ?');
                    $sqlParams[] = '%' . $inputParams['name'] . '%';
                } else
                    $paramsOk = false;
            }

            // Center
            if (!empty($inputParams['centernumber'])) {
                if (strlen($inputParams['centernumber']) <= 30) {
                    $qb->andWhere('TKZ.KmenoveStredisko LIKE ?');
                    $sqlParams[] = '%' . $inputParams['centernumber'] . '%';
                } else
                    $paramsOk = false;
            }

            // Registration number
            if (!empty($inputParams['regnumber'])) {
                if (strlen($inputParams['regnumber']) <= 30) {
                    $qb->andWhere('TKZ.RegCis LIKE ?');
                    $sqlParams[] = '%' . $inputParams['regnumber'] . '%';
                } else
                    $paramsOk = false;
            }

            // Donotorderhidden
            if (!empty($inputParams['donotorderhidden'])) {
                if ($inputParams['donotorderhidden'] == 1)
                    $qb->andWhere('(
                                    (TKZE._Neobjednavat IS NULL)
                                    OR	(TKZE._Neobjednavat IS NOT NULL AND (SELECT ABS(MnozstviDispo) + ABS(MnozstviKPrijmu) + ABS(MnozstviKVydeji) FROM TabStavSkladu WHERE IDSklad = \'00100001\' AND IDKmenZbozi = TKZ.ID) > 0)
                                    OR	(TKZE._Neobjednavat IS NOT NULL AND (SELECT ABS(MnozstviDispo) + ABS(MnozstviKPrijmu) + ABS(MnozstviKVydeji) FROM TabStavSkladu WHERE IDSklad = \'00100002\' AND IDKmenZbozi = TKZ.ID) > 0)
                                )');
                else
                    $paramsOk = false;
            }

            // Pricefrom
            if (!empty($inputParams['pricefrom'])) {
                if (is_numeric($inputParams['pricefrom'])) {
                    $qb->andWhere('TNC.CenaKC >= ?');
                    $sqlParams[] = $inputParams['pricefrom'];
                } else
                    $paramsOk = false;
            }

            // Priceto
            if (!empty($inputParams['priceto'])) {
                if (is_numeric($inputParams['priceto'])) {
                    $qb->andWhere('TNC.CenaKC <= ?');
                    $sqlParams[] = $inputParams['priceto'];
                } else
                    $paramsOk = false;
            }

            // Usualorigincountry
            if (!empty($inputParams['usualorigincountry'])) {
                if (strlen($inputParams['usualorigincountry']) <= 2) {
                    $qb->andWhere('TKZ.ObvyklaZemePuvodu LIKE ?');
                    $sqlParams[] = '%' . $inputParams['usualorigincountry'] . '%';
                } else
                    $paramsOk = false;
            }

            // Goodskind
            if (!empty($inputParams['goodskind'])) {
                if (is_numeric($inputParams['goodskind'])) {
                    $qb->andWhere('TKZE._DruhVina = ?');
                    $sqlParams[] = $inputParams['goodskind'];
                } else
                    $paramsOk = false;
            }

            // Goodstype
            if (!empty($inputParams['goodstype'])) {
                if (is_numeric($inputParams['goodstype'])) {
                    $qb->andWhere('TKZE._TypVina = ?');
                    $sqlParams[] = $inputParams['goodstype'];
                } else
                    $paramsOk = false;
            }

            if ($paramsOk === false)
                $app->abort(400, "Bad Request.");

            // External values
            $qb->leftJoin('TKZ', 'TabKmenZbozi_EXT', 'TKZE', 'TKZ.ID = TKZE.ID');

            // Prices
            if (!empty($inputParams['pricelevel']))
                if (is_numeric($inputParams['pricelevel']))
                    $qb->leftJoin('TKZ', 'TabNC', 'TNC', 'TKZ.ID = TNC.IDKmenZbozi AND TNC.CenovaUroven = ' . $inputParams['pricelevel']);
                else
                    $paramsOk = false;
            else
                $qb->leftJoin('TKZ', 'TabNC', 'TNC', 'TKZ.ID = TNC.IDKmenZbozi AND TNC.CenovaUroven = 1');


            // Get total rows count
            $qb->select('COUNT(TKZ.ID) AS totalcount');
            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select product whole list rows - TOTAL COUNT:' . $qb->getSql());
            $totalRows = $app['db']->fetchAll($qb->getSql(), $sqlParams);
            $result->totalrows = (int)$totalRows[0]['totalcount'];

            // Get part of lits
            $qb->select(
                'TKZ.ID',
                'TKZ.RegCis',
                'TSS.ID AS SkladovaKarta',
                'TSS.IDSklad AS TypSkladu',
                'TKZ.SkupZbo',
                'TKZ.Nazev1',
                'TKZ.Nazev2',
                'TKZ.Nazev3',
                'TKZ.Nazev4',
                'TKZ.SazbaDPHVystup',
                'TNC.CenaKC',
                'TKZ.SKP',
                'TKZ.Blokovano',
                '(SELECT CONCAT(MnozstviDispo, \'/\', MnozstviKPrijmu, \'/\', MnozstviKVydeji, \'/\', Objednano, \'/\', BlokovanoProDObj) FROM TabStavSkladu WHERE IDSklad = \'00100001\' AND IDKmenZbozi = TKZ.ID) AS \'BeznySklad\'',
                '(SELECT CONCAT(MnozstviDispo, \'/\', MnozstviKPrijmu, \'/\', MnozstviKVydeji, \'/\', Objednano, \'/\', BlokovanoProDObj) FROM TabStavSkladu WHERE IDSklad = \'00100002\' AND IDKmenZbozi = TKZ.ID) AS \'DanovySklad\'',
                'TKZE._Neobjednavat',
                'TKZE._DruhVina',
                'TKZE._IVK',
                'TKZ.ObvyklaZemePuvodu',
                'TKZE._TypVina'
            );

            // Limit from
            if (!empty($inputParams['listfrom']))
                if (is_numeric($inputParams['listfrom']))
                    $qb->setFirstResult($inputParams['listfrom']);
                else
                    $paramsOk = false;

            // Limit to
            if (!empty($inputParams['listto']))
                if (is_numeric($inputParams['listto']))
                    $qb->setMaxResults($inputParams['listto']);
                else
                    $paramsOk = false;

            // Sort
            $qb->orderBy('TKZ.ID', 'ASC');
            $qb->addOrderBy('TSS.IDSklad', 'ASC');
            if (!empty($inputParams['sort'])) {
                switch ($inputParams['sort']) {
                    case 'nameasc': {
                        $qb->orderBy('TKZ.Nazev1', 'ASC');
                        break;
                    }

                    case 'namedesc': {
                        $qb->orderBy('TKZ.Nazev1', 'DESC');
                        break;
                    }

                    default: {
                        $paramsOk = false;
                        break;
                    }
                }
            }

            if ($paramsOk === false)
                $app->abort(400, "Bad Request.");

            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select client list :' . $qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);
            $lastid = 0;
            foreach ($listData as $row) {
                if($lastid == (int) $row['ID']) continue;
                $lastid = (int)$row['ID'];
                $newRow = new \stdClass();
                $newRow->id = (int)$row['ID'];
                $newRow->regnum = $row['RegCis'];
                $newRow->storagecard = $row['TypSkladu'] == '00100001' ? $row['SkladovaKarta'] : null;
                $newRow->group = $row['SkupZbo'];
                $newRow->name1 = $row['Nazev1'];
                $newRow->name2 = $row['Nazev2'];
                $newRow->name3 = $row['Nazev3'];
                $newRow->name4 = $row['Nazev4'];
                $newRow->skp = $row['SKP'];
                $newRow->price = (float)floatval($row['CenaKC']);
                $newRow->pricevat = (float)floatval($row['CenaKC'] * (1 + (0.01 * $row['SazbaDPHVystup'])));
                if (!empty($row['Nazev3']) && is_numeric($row['Nazev3']))
                    $newRow->vintage = (int)$row['Nazev3'];
                else
                    $newRow->vintage = null;
                $newRow->blocked = (int)$row['Blokovano'];

                $tmpBeznySklad = (!empty($row['BeznySklad'])) ? explode('/', $row['BeznySklad']) : ['', '', '', ''];
                $tmpDanovySklad = (!empty($row['DanovySklad'])) ? explode('/', $row['DanovySklad']) : ['', '', '', ''];

                $newRow->storages[] = [
                    'storageid' => (int)3,
                    'quantityavailable' => intval($tmpBeznySklad[0]) - intval($tmpBeznySklad[2]) - intval($tmpBeznySklad[4]),
                    'quantitytoreceive' => intval($tmpBeznySklad[1]),
                    'quantityordered' => intval($tmpBeznySklad[3])
                ];

                $newRow->storages[] = [
                    'storageid' => (int)6,
                    'quantityavailable' => intval($tmpBeznySklad[0]) - intval($tmpBeznySklad[2]) - intval($tmpBeznySklad[4]),
                    'quantitytoreceive' => intval($tmpBeznySklad[1]),
                    'quantityordered' => intval($tmpBeznySklad[3])
                ];
                $newRow->orderdate = array_key_exists($row['id'], $orderDates) ? $orderDates[$row['id']] : null;
                $newRow->donotorder = (int)$row['_Neobjednavat'];
                $newRow->goodskind = (int)$row['_DruhVina'];
                $newRow->ivk = (int)$row['_IVK'];
                $newRow->usualorigincountry = $row['ObvyklaZemePuvodu'];
                $newRow->goodstype = (int)$row['_TypVina'];


                $result->rows[] = $newRow;
            }

            //Construct response
            if ($app['debug']) $app['monolog']->info('Response: data:' . json_encode($result));
            return $app->json($result);
        });

        // Get detail of product
        $controllers->get('/products/{id}', function (Application $app, $id) {
            if (empty($id) || !is_numeric($id))
                $app->abort(400, "Bad Request.");

            $result = new \stdClass();
            $request = $app['request_stack']->getCurrentRequest();

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabKmenZbozi', 'TKZ');
            $qb->leftJoin('TKZ', 'TabStavSkladu', 'TSS', 'TSS.IDKmenZbozi = TKZ.ID');


            // Id
            $qb->andWhere('TKZ.ID = ?');
            $sqlParams[] = $id;

            // Get data
            $qb->select(
                'TKZ.ID',
                'TKZ.SkupZbo',
                'TKZ.RegCis',
                'TKZ.DruhSkladu',
                'TSS.ID AS SkladovaKarta',
                'TKZ.Nazev1',
                'TKZ.Nazev2',
                'TKZ.Nazev3',
                'TKZ.Nazev4',
                'TKZ.SKP',
                'TKZ.IdSortiment',
                'TKZ.Upozorneni',
                'TKZ.Poznamka',
                'TKZ.MJEvidence',
                'TKZ.MJInventura',
                'TKZ.MJVstup',
                'TKZ.MJVystup',
                'TKZ.SazbaDPHVstup',
                'TKZ.SazbaDPHVystup',
                'TKZ.IDKodPDP',
                'TKZ.SazbaSDVstup',
                'TKZ.SazbaSDVystup',
                'TKZ.MJSD',
                'TKZ.KodSD',
                'TKZ.PrepocetMJSD',
                'TKZ.Blokovano',
                'TNC.CenaKC',
                '(SELECT CONCAT(MnozstviDispo, \'/\', MnozstviKPrijmu, \'/\', MnozstviKVydeji) FROM TabStavSkladu WHERE IDSklad = \'00100001\' AND IDKmenZbozi = TKZ.ID) AS \'BeznySklad\'',
                '(SELECT CONCAT(MnozstviDispo, \'/\', MnozstviKPrijmu, \'/\', MnozstviKVydeji) FROM TabStavSkladu WHERE IDSklad = \'00100002\' AND IDKmenZbozi = TKZ.ID) AS \'DanovySklad\'',
                'TDZ.Splatnost',
                'TKZE._Neobjednavat',
                'TKZE._DruhVina',
                'TKZE._IVK',
                'TKZ.ObvyklaZemePuvodu',
                'TKZE._TypVina'
            );

            // Prices
            $qb->leftJoin('TKZ', 'TabNC', 'TNC', 'TKZ.ID = TNC.IDKmenZbozi AND TNC.CenovaUroven = 1');
            $qb->leftJoin('TKZ', 'TabPohybyZbozi', 'TPZ', 'TKZ.RegCis = TPZ.RegCis');
            $qb->leftJoin('TPZ', 'TabDokladyZbozi', 'TDZ', 'TPZ.IDDoklad = TDZ.ID');
            // External values
            $qb->leftJoin('TKZ', 'TabKmenZbozi_EXT', 'TKZE', 'TKZ.ID = TKZE.ID');

            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select product detail :' . $qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            if (count($listData) < 1)
                $app->abort(404, "Not Found.");

            foreach ($listData as $row) {
                $newRow = new \stdClass();
                $newRow->id = (int)$row['ID'];
                $newRow->group = $row['SkupZbo'];
                $newRow->storagecard = $row['SkladovaKarta'];
                $newRow->regnum = $row['RegCis'];
                $newRow->storagetype = $row['DruhSkladu'];
                $newRow->name1 = $row['Nazev1'];
                $newRow->name2 = $row['Nazev2'];
                $newRow->name3 = $row['Nazev3'];
                $newRow->name4 = $row['Nazev4'];
                $newRow->skp = $row['SKP'];
                $newRow->price = (float)floatval($row['CenaKC']);
                $newRow->pricevat = (float)floatval($row['CenaKC'] * (1 + (0.01 * $row['SazbaDPHVystup'])));
                if (!empty($row['Nazev3']) && is_numeric($row['Nazev3']))
                    $newRow->vintage = (int)$row['Nazev3'];
                else
                    $newRow->vintage = null;
                $newRow->range = $row['IdSortiment'];
                $newRow->notice = $row['Upozorneni'];
                $newRow->note = $row['Poznamka'];
                $newRow->muevidence = $row['MJEvidence'];
                $newRow->mustocktaking = $row['MJInventura'];
                $newRow->muinput = $row['MJVstup'];
                $newRow->muoutput = $row['MJVystup'];
                $newRow->vatinput = $row['SazbaDPHVstup'];
                $newRow->vatoutput = $row['SazbaDPHVystup'];
                $newRow->pdpcode = $row['IDKodPDP'];
                $newRow->edinput = $row['SazbaSDVstup'];
                $newRow->edoutput = $row['SazbaSDVystup'];
                $newRow->mued = $row['MJSD'];
                $newRow->edcode = $row['KodSD'];
                $newRow->edcalc = $row['PrepocetMJSD'];
                $newRow->blocked = (int)$row['Blokovano'];
                $tmpBeznySklad = (!empty($row['BeznySklad'])) ? explode('/', $row['BeznySklad']) : ['', '', ''];
                $tmpDanovySklad = (!empty($row['DanovySklad'])) ? explode('/', $row['DanovySklad']) : ['', '', ''];

                $newRow->storages[] = [
                    'storageid' => (int)3,
                    'quantityavailable' => intval($tmpBeznySklad[0]),
                    'quantitytoreceive' => intval($tmpBeznySklad[1]),
                    'quantitytodispense' => intval($tmpBeznySklad[2])
                ];

                $newRow->storages[] = [
                    'storageid' => (int)6,
                    'quantityavailable' => intval($tmpDanovySklad[0]),
                    'quantitytoreceive' => intval($tmpDanovySklad[1]),
                    'quantitytodispense' => intval($tmpDanovySklad[2])
                ];
                $newRow->orderdate = $row['Splatnost'];
                $newRow->donotorder = (int)$row['_Neobjednavat'];
                $newRow->goodskind = (int)$row['_DruhVina'];
                $newRow->ivk = (int)$row['_IVK'];
                $newRow->usualorigincountry = $row['ObvyklaZemePuvodu'];
                $newRow->goodstype = (int)$row['_TypVina'];

                $result = $newRow;
            }

            //Construct response
            if ($app['debug']) $app['monolog']->info('Response: data:' . json_encode($result));
            return $app->json($result);
        });

        // Create new product
        $controllers->post('/products', function (Application $app) {
            $paramsOk = true;
            $newProductId = null;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);
            $qb = $app['db']->createQueryBuilder();

            // Check data
            $sqlParams = Array();

            if (empty($inputParams['storagetype']) && $inputParams['storagetype'] != '0')
                $inputParams['storagetype'] = 1;

            if (empty($inputParams['blocked']))
                $inputParams['blocked'] = 0;

            // Check if product already exists
            $sql = 'SELECT 1 AS productexists FROM TabKmenZbozi WHERE TabKmenZbozi.RegCis = \'' . $inputParams['regnum'] . '\'';
            if ($app['debug']) $app['monolog']->info('DB Check if product with regnum exists:' . $sql);
            $queryResult = $app['db']->executeQuery($sql);
            $clientExists = $queryResult->fetch();
            if ($clientExists['productexists'] == 1)
                $app->abort(409, "Conflict.");

            // Required fields
            if (
                $inputParams['group'] != null && strlen($inputParams['group']) <= 3 &&
                $inputParams['regnum'] != null && strlen($inputParams['regnum']) <= 30 &&
                $inputParams['name'] != null && strlen($inputParams['name']) <= 100 &&
                $inputParams['storagetype'] != null && is_numeric($inputParams['storagetype']) &&
                ($inputParams['blocked'] != null || $inputParams['blocked'] == 0) && is_numeric($inputParams['blocked']) &&
                $inputParams['price'] != null && is_numeric($inputParams['price'])
            ) {
                $sqlParams['TabKmenZbozi']['SkupZbo'] = $inputParams['group'];
                $sqlParams['TabKmenZbozi']['RegCis'] = $inputParams['regnum'];
                $sqlParams['TabKmenZbozi']['Nazev1'] = $inputParams['name'];
                $sqlParams['TabKmenZbozi']['DruhSkladu'] = $inputParams['storagetype'];
                $sqlParams['TabKmenZbozi']['Blokovano'] = $inputParams['blocked'];
                $sqlParams['TabNC']['CenaKc'] = $inputParams['price'];
            } else
                $app->abort(400, "Bad Request.");

            // Optional fields
            if ($inputParams['name2'] != null)
                if (strlen($inputParams['name2']) <= 100)
                    $sqlParams['TabKmenZbozi']['Nazev2'] = $inputParams['name2'];
                else
                    $paramsOk = false;

            if ($inputParams['name3'] != null)
                if (strlen($inputParams['name3']) <= 100)
                    $sqlParams['TabKmenZbozi']['Nazev3'] = $inputParams['name3'];
                else
                    $paramsOk = false;

            if ($inputParams['name4'] != null)
                if (strlen($inputParams['name4']) <= 100)
                    $sqlParams['TabKmenZbozi']['Nazev4'] = $inputParams['name4'];
                else
                    $paramsOk = false;

            if ($inputParams['skp'] != null)
                if (strlen($inputParams['skp']) <= 50)
                    $sqlParams['TabKmenZbozi']['SKP'] = $inputParams['skp'];
                else
                    $paramsOk = false;

            if ($inputParams['range'] != null)
                if (is_numeric($inputParams['range']))
                    $sqlParams['TabKmenZbozi']['IdSortiment'] = $inputParams['range'];
                else
                    $paramsOk = false;

            if ($inputParams['notice'] != null)
                if (strlen($inputParams['notice']) <= 255)
                    $sqlParams['TabKmenZbozi']['Upozorneni'] = $inputParams['notice'];
                else
                    $paramsOk = false;

            if ($inputParams['note'] != null)
                if (strlen($inputParams['note']) <= 1073741823)
                    $sqlParams['TabKmenZbozi']['Poznamka'] = $inputParams['note'];
                else
                    $paramsOk = false;

            if ($inputParams['muevidence'] != null)
                if (strlen($inputParams['muevidence']) <= 10)
                    $sqlParams['TabKmenZbozi']['MJEvidence'] = $inputParams['muevidence'];
                else
                    $paramsOk = false;

            if ($inputParams['mustocktaking'] != null)
                if (strlen($inputParams['mustocktaking']) <= 10)
                    $sqlParams['TabKmenZbozi']['MJInventura'] = $inputParams['mustocktaking'];
                else
                    $paramsOk = false;

            if ($inputParams['muinput'] != null)
                if (strlen($inputParams['muinput']) <= 10)
                    $sqlParams['TabKmenZbozi']['MJVstup'] = $inputParams['muinput'];
                else
                    $paramsOk = false;

            if ($inputParams['muoutput'] != null)
                if (strlen($inputParams['muoutput']) <= 10)
                    $sqlParams['TabKmenZbozi']['MJVystup'] = $inputParams['muoutput'];
                else
                    $paramsOk = false;

            if ($inputParams['vatinput'] != null)
                if (is_numeric($inputParams['vatinput']))
                    $sqlParams['TabKmenZbozi']['SazbaDPHVstup'] = $inputParams['vatinput'];
                else
                    $paramsOk = false;

            if ($inputParams['vatoutput'] != null)
                if (is_numeric($inputParams['vatoutput']))
                    $sqlParams['TabKmenZbozi']['SazbaDPHVystup'] = $inputParams['vatoutput'];
                else
                    $paramsOk = false;

            if ($inputParams['pdpcode'] != null)
                if (is_numeric($inputParams['pdpcode']))
                    $sqlParams['TabKmenZbozi']['IDKodPDP'] = $inputParams['pdpcode'];
                else
                    $paramsOk = false;

            if ($inputParams['edinput'] != null)
                if (is_numeric($inputParams['edinput']))
                    $sqlParams['TabKmenZbozi']['SazbaSDVstup'] = $inputParams['edinput'];
                else
                    $paramsOk = false;

            if ($inputParams['edoutput'] != null)
                if (is_numeric($inputParams['edoutput']))
                    $sqlParams['TabKmenZbozi']['SazbaSDVystup'] = $inputParams['edoutput'];
                else
                    $paramsOk = false;

            if ($inputParams['mued'] != null)
                if (strlen($inputParams['mued']) <= 10)
                    $sqlParams['TabKmenZbozi']['MJSD'] = $inputParams['mued'];
                else
                    $paramsOk = false;

            if ($inputParams['edcode'] != null)
                if (strlen($inputParams['edcode']) <= 10)
                    $sqlParams['TabKmenZbozi']['KodSD'] = $inputParams['edcode'];
                else
                    $paramsOk = false;

            if ($inputParams['edcalc'] != null)
                if (is_numeric($inputParams['edcalc']))
                    $sqlParams['TabKmenZbozi']['PrepocetMJSD'] = $inputParams['edcalc'];
                else
                    $paramsOk = false;

            if ($inputParams['usualorigincountry'] != null)
                if (strlen($inputParams['usualorigincountry']) <= 2)
                    $sqlParams['TabKmenZbozi']['ObvyklaZemePuvodu'] = $inputParams['usualorigincountry'];
                else
                    $paramsOk = false;

            if ($inputParams['donotorder'] != null)
                if (is_numeric($inputParams['donotorder']))
                    $sqlParams['TabKmenZbozi_EXT']['_Neobjednavat'] = $inputParams['donotorder'];
                else
                    $paramsOk = false;

            if ($inputParams['goodskind'] != null)
                if (is_numeric($inputParams['goodskind']))
                    $sqlParams['TabKmenZbozi_EXT']['_DruhVina'] = $inputParams['goodskind'];
                else
                    $paramsOk = false;

            if ($inputParams['goodstype'] != null)
                if (is_numeric($inputParams['goodstype']))
                    $sqlParams['TabKmenZbozi_EXT']['_TypVina'] = $inputParams['goodstype'];
                else
                    $paramsOk = false;

            if ($inputParams['ivk'] != null)
                if (is_numeric($inputParams['ivk']))
                    $sqlParams['TabKmenZbozi_EXT']['_IVK'] = $inputParams['ivk'];
                else
                    $paramsOk = false;

            if ($paramsOk === false)
                $app->abort(400, "Bad Request.");

            $app['db']->beginTransaction();
            if ($app['debug']) $app['monolog']->info('POST PRODUCT:TabKmenZbozi');
            $result['TabKmenZbozi'] = $app['db']->insert('TabKmenZbozi', $sqlParams['TabKmenZbozi']);
            $lastInsertedId['TabKmenZbozi'] = $app['db']->lastInsertId();

            if (count($sqlParams['TabKmenZbozi_EXT']) > 0) {
                if ($app['debug']) $app['monolog']->info('POST PRODUCT:TabKmenZbozi_EXT');
                $result['TabKmenZbozi_EXT'] = $app['db']->update('TabKmenZbozi_EXT', $sqlParams['TabKmenZbozi_EXT'], array('ID' => $lastInsertedId['TabKmenZbozi']));
            }

            if ($app['debug']) $app['monolog']->info('POST PRODUCT:IDKmenZbozi');
            $sqlParams['TabNC']['IDKmenZbozi'] = $lastInsertedId['TabKmenZbozi'];
            $sqlParams['TabNC']['CenovaUroven'] = 1;
            $result['TabNC'] = $app['db']->insert('TabNC', $sqlParams['TabNC']);
            $lastInsertedId['TabNC'] = $app['db']->lastInsertId();

            // If exactly 1 row was affected
            if (count($result) === array_sum($result))
                $app['db']->commit();
            else {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response = new Response(json_encode(array('id' => (int)$lastInsertedId['TabKmenZbozi'])), 201);
            $response->headers->set('Location', 'products/' . $lastInsertedId['TabKmenZbozi']);
            return $response;
        });

        // Update all products - method not allowed
        $controllers->put('/products', function (Application $app) {
            $app->abort(405, "Method Not Allowed.");
        });

        // Update product
        $controllers->put('/products/{id}', function (Application $app, $id) {
            $paramsOk = true;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);
            $qb = $app['db']->createQueryBuilder();

            // Check data
            $sqlParams = Array();


            // Check if product exists
            $qb = $app['db']->createQueryBuilder();
            $qb->select(
                'TabKmenZbozi.ID'
            );
            $qb->from('TabKmenZbozi');
            $qb->andWhere('TabKmenZbozi.ID = ?');
            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select product by ID :' . $qb->getSql());
            $productData = $app['db']->fetchAssoc($qb->getSql(), array($id));
            if (!is_array($productData) || count($productData) <= 0)
                $app->abort(404, "Not Found.");

            // Optional fields
            if ($inputParams['group'] != null)
                if (strlen($inputParams['group']) <= 3)
                    $sqlParams['TabKmenZbozi']['SkupZbo'] = $inputParams['group'];
                else
                    $paramsOk = false;

            if ($inputParams['regnum'] != null)
                if (strlen($inputParams['regnum']) <= 30)
                    $sqlParams['TabKmenZbozi']['RegCis'] = $inputParams['regnum'];
                else
                    $paramsOk = false;

            if ($inputParams['name'] != null)
                if (strlen($inputParams['name']) <= 100)
                    $sqlParams['TabKmenZbozi']['Nazev1'] = $inputParams['name'];
                else
                    $paramsOk = false;

            if ($inputParams['storagetype'] != null)
                if (is_numeric($inputParams['storagetype']))
                    $sqlParams['TabKmenZbozi']['DruhSkladu'] = $inputParams['storagetype'];
                else
                    $paramsOk = false;

            if ($inputParams['name2'] != null)
                if (strlen($inputParams['name2']) <= 100)
                    $sqlParams['TabKmenZbozi']['Nazev2'] = $inputParams['name2'];
                else
                    $paramsOk = false;

            if ($inputParams['name3'] != null)
                if (strlen($inputParams['name3']) <= 100)
                    $sqlParams['TabKmenZbozi']['Nazev3'] = $inputParams['name3'];
                else
                    $paramsOk = false;

            if ($inputParams['name4'] != null)
                if (strlen($inputParams['name4']) <= 100)
                    $sqlParams['TabKmenZbozi']['Nazev4'] = $inputParams['name4'];
                else
                    $paramsOk = false;

            if ($inputParams['skp'] != null)
                if (strlen($inputParams['skp']) <= 50)
                    $sqlParams['TabKmenZbozi']['SKP'] = $inputParams['skp'];
                else
                    $paramsOk = false;

            if ($inputParams['range'] != null)
                if (is_numeric($inputParams['range']))
                    $sqlParams['TabKmenZbozi']['IdSortiment'] = $inputParams['range'];
                else
                    $paramsOk = false;

            if ($inputParams['notice'] != null)
                if (strlen($inputParams['notice']) <= 255)
                    $sqlParams['TabKmenZbozi']['Upozorneni'] = $inputParams['notice'];
                else
                    $paramsOk = false;

            if ($inputParams['note'] != null)
                if (strlen($inputParams['note']) <= 1073741823)
                    $sqlParams['TabKmenZbozi']['Poznamka'] = $inputParams['note'];
                else
                    $paramsOk = false;

            if ($inputParams['muevidence'] != null)
                if (strlen($inputParams['muevidence']) <= 10)
                    $sqlParams['TabKmenZbozi']['MJEvidence'] = $inputParams['muevidence'];
                else
                    $paramsOk = false;

            if ($inputParams['mustocktaking'] != null)
                if (strlen($inputParams['mustocktaking']) <= 10)
                    $sqlParams['TabKmenZbozi']['MJInventura'] = $inputParams['mustocktaking'];
                else
                    $paramsOk = false;

            if ($inputParams['muinput'] != null)
                if (strlen($inputParams['muinput']) <= 10)
                    $sqlParams['TabKmenZbozi']['MJVstup'] = $inputParams['muinput'];
                else
                    $paramsOk = false;

            if ($inputParams['muoutput'] != null)
                if (strlen($inputParams['muoutput']) <= 10)
                    $sqlParams['TabKmenZbozi']['MJVystup'] = $inputParams['muoutput'];
                else
                    $paramsOk = false;

            if ($inputParams['vatinput'] != null)
                if (is_numeric($inputParams['vatinput']))
                    $sqlParams['TabKmenZbozi']['SazbaDPHVstup'] = $inputParams['vatinput'];
                else
                    $paramsOk = false;

            if ($inputParams['vatoutput'] != null)
                if (is_numeric($inputParams['vatoutput']))
                    $sqlParams['TabKmenZbozi']['SazbaDPHVystup'] = $inputParams['vatoutput'];
                else
                    $paramsOk = false;

            if ($inputParams['pdpcode'] != null)
                if (is_numeric($inputParams['pdpcode']))
                    $sqlParams['TabKmenZbozi']['IDKodPDP'] = $inputParams['pdpcode'];
                else
                    $paramsOk = false;

            if ($inputParams['edinput'] != null)
                if (is_numeric($inputParams['edinput']))
                    $sqlParams['TabKmenZbozi']['SazbaSDVstup'] = $inputParams['edinput'];
                else
                    $paramsOk = false;

            if ($inputParams['edoutput'] != null)
                if (is_numeric($inputParams['edoutput']))
                    $sqlParams['TabKmenZbozi']['SazbaSDVystup'] = $inputParams['edoutput'];
                else
                    $paramsOk = false;

            if ($inputParams['mued'] != null)
                if (strlen($inputParams['mued']) <= 10)
                    $sqlParams['TabKmenZbozi']['MJSD'] = $inputParams['mued'];
                else
                    $paramsOk = false;

            if ($inputParams['edcode'] != null)
                if (strlen($inputParams['edcode']) <= 10)
                    $sqlParams['TabKmenZbozi']['KodSD'] = $inputParams['edcode'];
                else
                    $paramsOk = false;

            if ($inputParams['edcalc'] != null)
                if (is_numeric($inputParams['edcalc']))
                    $sqlParams['TabKmenZbozi']['PrepocetMJSD'] = $inputParams['edcalc'];
                else
                    $paramsOk = false;

            if (($inputParams['blocked'] != null || $inputParams['blocked'] == '0'))
                if (is_numeric($inputParams['blocked']))
                    $sqlParams['TabKmenZbozi']['Blokovano'] = $inputParams['blocked'];
                else
                    $paramsOk = false;

            if (($inputParams['price'] != null || $inputParams['price'] == '0'))
                if (is_numeric($inputParams['price']))
                    $sqlParams['TabNC']['CenaKc'] = $inputParams['price'];
                else
                    $paramsOk = false;

            if (($inputParams['donotorder'] != null || $inputParams['donotorder'] == '0'))
                if (is_numeric($inputParams['donotorder']))
                    $sqlParams['TabKmenZbozi_EXT']['_Neobjednavat'] = $inputParams['donotorder'];
                else
                    $paramsOk = false;

            if (isset($inputParams['goodskind']))
                if (is_numeric($inputParams['goodskind']))
                    $sqlParams['TabKmenZbozi_EXT']['_DruhVina'] = $inputParams['goodskind'];
                else
                    $paramsOk = false;

            if (isset($inputParams['ivk']))
                if (is_numeric($inputParams['ivk']))
                    $sqlParams['TabKmenZbozi_EXT']['_IVK'] = $inputParams['ivk'];
                else
                    $paramsOk = false;

            if ($inputParams['usualorigincountry'] != null)
                if (strlen($inputParams['usualorigincountry']) <= 2)
                    $sqlParams['TabKmenZbozi']['ObvyklaZemePuvodu'] = $inputParams['usualorigincountry'];
                else
                    $paramsOk = false;

            if (isset($inputParams['goodstype']))
                if (is_numeric($inputParams['goodstype']))
                    $sqlParams['TabKmenZbozi_EXT']['_TypVina'] = $inputParams['goodstype'];
                else
                    $paramsOk = false;

            // No input data received or bad format data
            if ($app['debug']) $app['monolog']->info('PUT PRODUCT params[' . count($sqlParams) . '][' . $paramsOk . ']:' . print_r($sqlParams, true));
            if (count($sqlParams) < 1 || $paramsOk === false)
                $app->abort(400, "Bad Request.");

            $app['db']->beginTransaction();

            if ($app['debug']) $app['monolog']->info('PUT PRODUCT:TabKmenZbozi');
            $result['TabKmenZbozi'] = $app['db']->update('TabKmenZbozi', $sqlParams['TabKmenZbozi'], array('ID' => $id));

            if (count($sqlParams['TabKmenZbozi_EXT']) > 0) {
                if ($app['debug']) $app['monolog']->info('POST PRODUCT:TabKmenZbozi_EXT');
                $result['TabKmenZbozi_EXT'] = $app['db']->update('TabKmenZbozi_EXT', $sqlParams['TabKmenZbozi_EXT'], array('ID' => $id));
            }

            if (count($sqlParams['TabNC']) > 0) {
                if ($app['debug']) $app['monolog']->info('POST PRODUCT:IDKmenZbozi');
                $result['TabNC'] = $app['db']->update('TabNC', $sqlParams['TabNC'], array('IDKmenZbozi' => $id, 'CenovaUroven' => 1));
            }

            // If exactly 1 row was affected
            if (count($result) === array_sum($result))
                $app['db']->commit();
            else {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response = new Response(null, 200);
            return $response;
        });

        // Delete all products - method not allowed
        $controllers->delete('/products', function (Application $app) {
            $app->abort(405, "Method Not Allowed.");
        });

        // Delete product
        $controllers->delete('/products/{id}', function (Application $app, $id) {
            if (empty($id) || !is_numeric($id))
                $app->abort(400, "Bad Request.");

            $paramsOk = true;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);

            // Check if product exists
            $qb = $app['db']->createQueryBuilder();
            $qb->select(
                'TabKmenZbozi.ID'
            );
            $qb->from('TabKmenZbozi');
            $qb->andWhere('TabKmenZbozi.ID = ?');
            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select product by ID :' . $qb->getSql());
            $productData = $app['db']->fetchAssoc($qb->getSql(), array($id));
            if (!is_array($productData) || count($productData) <= 0)
                $app->abort(404, "Not Found.");

            $app['db']->beginTransaction();
            $result = $app['db']->update('TabKmenZbozi', array('Blokovano' => 1), array('ID' => $id));

            // If exactly 1 row was affected            
            if ($result === 1)
                $app['db']->commit();
            else {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response = new Response(null, 200);
            return $response;
        });

        // Get list of storages
        $controllers->get('/storages', function (Application $app) {
            $paramsOk = true;
            $result = new \stdClass();
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = $request->query->all();

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabStrom');

            // Storage number
            if (!empty($inputParams['number'])) {
                if (strlen($inputParams['number']) <= 30) {
                    $qb->andWhere('TabStrom.Cislo LIKE ?');
                    $sqlParams[] = '%' . $inputParams['number'] . '%';
                } else
                    $paramsOk = false;
            }


            // Name
            if (!empty($inputParams['name'])) {
                if (strlen($inputParams['name']) <= 40) {
                    $qb->andWhere('TabStrom.Nazev LIKE ?');
                    $sqlParams[] = '%' . $inputParams['name'] . '%';
                } else
                    $paramsOk = false;
            }

            // Center number
            if (!empty($inputParams['centernumber'])) {
                if (strlen($inputParams['centernumber']) <= 21) {
                    $qb->andWhere('TabStrom.CisloStr LIKE ?');
                    $sqlParams[] = '%' . $inputParams['centernumber'] . '%';
                } else
                    $paramsOk = false;
            }

            if ($paramsOk === false)
                $app->abort(400, "Bad Request.");

            // Get total rows count
            $qb->select('COUNT(TabStrom.Id) AS totalcount');
            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select storage whole list rows - TOTAL COUNT:' . $qb->getSql());
            $totalRows = $app['db']->fetchAll($qb->getSql(), $sqlParams);
            $result->totalrows = (int)$totalRows[0]['totalcount'];

            // Get part of lits
            $qb->select(
                'TabStrom.Id',
                'TabStrom.Cislo',
                'TabStrom.Nazev',
                'TabStrom.CisloStr'
            );

            // Limit from
            if (!empty($inputParams['listfrom']))
                if (is_numeric($inputParams['listfrom']))
                    $qb->setFirstResult($inputParams['listfrom']);
                else
                    $paramsOk = false;

            // Limit to
            if (!empty($inputParams['listto']))
                if (is_numeric($inputParams['listto']))
                    $qb->setMaxResults($inputParams['listto']);
                else
                    $paramsOk = false;

            // Sort
            if (!empty($inputParams['sort'])) {
                switch ($inputParams['sort']) {
                    case 'numberasc': {
                        $qb->orderBy('TabStrom.Cislo', 'ASC');
                        break;
                    }

                    case 'numberdesc': {
                        $qb->orderBy('TabStrom.Cislo', 'DESC');
                        break;
                    }

                    default: {
                        $paramsOk = false;
                        break;
                    }
                }
            }

            if ($paramsOk === false)
                $app->abort(400, "Bad Request.");

            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select storage list :' . $qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            foreach ($listData as $row) {
                $newRow = new \stdClass();
                $newRow->id = (int)$row['Id'];
                $newRow->regnum = $row['Cislo'];
                $newRow->name1 = $row['Nazev'];
                $newRow->name2 = $row['CisloStr'];
                $result->rows[] = $newRow;
            }

            //Construct response
            if ($app['debug']) $app['monolog']->info('Response: data:' . json_encode($result));
            return $app->json($result);
        });

        // Get detail of storage
        $controllers->get('/storages/{id}', function (Application $app, $id) {
            if (empty($id) || !is_numeric($id))
                $app->abort(400, "Bad Request.");

            $result = new \stdClass();
            $request = $app['request_stack']->getCurrentRequest();

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabStrom', 'TS');
            $qb->leftJoin('TS', 'TabStavSkladu', 'TSS', 'TSS.IDSklad = TS.Cislo');
            $qb->leftJoin('TSS', 'TabKmenZbozi', 'TKZ', 'TKZ.ID = TSS.IDKmenZbozi');

            // Id
            $qb->andWhere('TS.Id = ?');
            $sqlParams[] = $id;

            // Get data
            $qb->select(
                'TS.Id',
                'TS.Cislo',
                'TS.Nazev',
                'TS.CisloStr',
                'TKZ.ID',
                'TKZ.RegCis',
                'TKZ.SkupZbo',
                'TKZ.Nazev1',
                'TKZ.Nazev2',
                'TKZ.Nazev3',
                'TKZ.Nazev4',
                'TKZ.SKP',
                'TKZ.Blokovano',
                'TSS.Mnozstvi',
                'TSS.MnozstviDispo',
                'TSS.MnozstviDispo'
            );

            $app['db']->prepare($qb->getSql());
            if ($app['debug']) $app['monolog']->info('DB Select storage detail :' . $qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            if (count($listData) < 1)
                $app->abort(404, "Not Found.");

            $newRow = new \stdClass();
            foreach ($listData as $row) {
                $newProduct = new \stdClass();
                $newProductStorage = new \stdClass();

                // Storage amount
                $newProductStorage->amount = (int)$row['Mnozstvi'];
                $newProductStorage->availableamount = (int)$row['MnozstviDispo'];
                $newProductStorage->dispenseamount = (int)$row['MnozstviKVydeji'];

                // Product info
                $newProduct->id = (int)$row['ID'];
                $newProduct->regnum = $row['RegCis'];
                $newProduct->group = $row['SkupZbo'];
                $newProduct->name1 = $row['Nazev1'];
                $newProduct->name2 = $row['Nazev2'];
                $newProduct->name3 = $row['Nazev3'];
                $newProduct->name4 = $row['Nazev4'];
                $newProduct->skp = $row['SKP'];
                $newProduct->blocked = $row['Blokovano'];
                $newProduct->storage = $newProductStorage;

                $result->id = (int)$row['Id'];
                $result->number = $row['Cislo'];
                $result->name = $row['Nazev'];
                $result->centernumber = $row['CisloStr'];
                $result->products[] = $newProduct;
            }

            //Construct response
            if ($app['debug']) $app['monolog']->info('Response: data:' . json_encode($result));
            return $app->json($result);
        });

        // Create new order
        $controllers->post('/orders', function (Application $app) {

            $paramsOk = true;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);
            if (!array_key_exists('orgnum', $inputParams) || $inputParams['orgnum'] ===  null || !is_numeric($inputParams['orgnum']))
                $paramsOk = false;

            if (!array_key_exists('products', $inputParams) || $inputParams['products'] == null)
                $paramsOk = false;


            if ($paramsOk === false)
                $app->abort(400, "Bad Request.");

            $statement = $app['db']->prepare("DECLARE @PoradoveCislo NVARCHAR(13)
            DECLARE @Error INT
            DECLARE @Datum DATETIME
            EXECUTE dbo.hp_DosleObj_PoradoveCislo @Rada='BIZ', @DatumPripadu=@Datum OUTPUT, @Cislo = @PoradoveCislo OUTPUT, @ErrorCode=@Error OUTPUT
            SELECT @PoradoveCislo AS PoradCis, @Datum AS Datum, @Error as Error");
            $statement->execute();

            $result = $statement->fetchAll();

            $statement = $app['db']->prepare("DECLARE @Error INT
            DECLARE @ReturnID INT
            EXECUTE @ReturnID = dbo.hp_DosleObj_NovaHlavicka01 @Rada = 'BIZ', @DatumPripadu = '" . $result[0]['Datum'] . "', 
            @Cislo = '" . $result[0]['PoradCis'] . "', @Sklad='00100001', 
            @ErrorCode = @Error OUTPUT, @ZpusobRET=0, @CisloOrg=" . $inputParams['orgnum'] . "
            SELECT @ReturnID as CisH, @Error as Error");
            $statement->execute();
            $result = $statement->fetchAll();

            foreach ($inputParams['products'] as $product) {

                $statement = $app['db']->prepare("DECLARE @NEWID INT DECLARE @Error INT
                EXECUTE dbo.hp_DosleObj_NovaPolozka01 @IdDoklad = " . $result[0]['CisH'] . ", @IdZboSklad = " . $product['storage_card'] . ", 
                @BarCode=NULL, @NewId=@NEWID OUTPUT, @ErrorCode=@Error OUTPUT, @ZpusobRet=2, @PovolitDuplicitu=1,
                @PovolitBlokovane=1,@TypMnozstvi=NULL, @Mnozstvi=" . $product['quantity'] . ", @IDVyrobek=NULL, 
                @StinJeVyrobek=NULL, @PomerDV=1, @Cena=NULL, @VstupniCena=NULL, @DotahovatSazbuDPH=NULL, @VnucenaSazbaDPH=NULL,
                @VnucenaSazbaSD=NULL");
                $statement->execute();
            }

            return $app->json(null, 201);
        });

        return $controllers;
    }
}

?>