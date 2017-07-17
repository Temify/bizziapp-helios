<?php
namespace HeliosAPI;

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
        $controllers->get('/clients', function (Application $app) 
        {
            $result = new \stdClass();
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = $request->query->all();

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabCisOrg');
            
            // Name
            if(!empty($inputParams['name']))
            {
                $qb->andWhere('TabCisOrg.Nazev LIKE ?');
                $sqlParams[] = '%'.$inputParams['name'].'%';
                $qb->orWhere('TabCisOrg.DruhyNazev LIKE ?');
                $sqlParams[] = '%'.$inputParams['name'].'%';
            }

            // Name is not null
            if(!empty($inputParams['nameisnotnull']))
            {
                if($inputParams['nameisnotnull'] == 'true')
                {
                    $qb->andWhere("(TabCisOrg.Nazev != '' || TabCisOrg.DruhyNazev != '')");
                }
                else
                {
                    $qb->andWhere("TabCisOrg.Nazev = ''");
                    $qb->andWhere("TabCisOrg.DruhyNazev = ''");
                }
            }

            // Status
            if(isset($inputParams['status']) && (!empty($inputParams['status']) || $inputParams['status'] == '0'))
            {
                $qb->andWhere('TabCisOrg.Stav = ?');
                $sqlParams[] = $inputParams['status'];
            }

            // Get total rows count
            $qb->select('COUNT(TabCisOrg.ID) AS totalcount');
            $app['db']->prepare($qb->getSql());
            if($app['debug']) $app['monolog']->info('DB Select client whole list rows - TOTAL COUNT:'.$qb->getSql());

            $totalRows = $app['db']->fetchAll($qb->getSql(), $sqlParams);
            $result->totalrows = (int)$totalRows[0]['totalcount'];

            $result->totalrows = $totalRows[0]['totalcount'];
            // Get part of lits
            $qb->select(
                        'TabCisOrg.ID', 
                        'TabCisOrg.CisloOrg', 
                        'TabCisOrg.NadrizenaOrg',
                        'TabCisOrg.Nazev',
                        'TabCisOrg.DruhyNazev',
                        'TabCisOrg.Kontakt',
                        'TabCisOrg.Stav'
                        );

            // Limit from
            if(!empty($inputParams['listfrom']))
                $qb->setFirstResult($inputParams['listfrom']);
            
            // Limit to
            if(!empty($inputParams['listto']))
                $qb->setMaxResults($inputParams['listto']);

			// Sort
			if(!empty($inputParams['sort']))
			{
				switch($inputParams['sort'])
				{
					case 'idasc':
					{
						$qb->orderBy('TabCisOrg.ID', 'ASC');
						break;
					}

					case 'iddesc':
					{
						$qb->orderBy('TabCisOrg.ID', 'DESC');
						break;
					}

					case 'nameasc':
					{
						$qb->orderBy('TabCisOrg.Nazev', 'ASC');
						break;
					}

					case 'namedesc':
					{
						$qb->orderBy('TabCisOrg.Nazev', 'DESC');
						break;
					}

                    default:
                    {
                        break;
                    }
				}
			}

            $app['db']->prepare($qb->getSql());
            if($app['debug']) $app['monolog']->info('DB Select client list :'.$qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            foreach($listData as $row)
            {
                $newRow = new \stdClass();
                $newRow->id = (int)$row['ID'];
                $newRow->orgnum = (int)$row['CisloOrg'];
                $newRow->parentid = (int)$row['NadrizenaOrg'];
                $newRow->name = $row['Nazev'];
                $newRow->name2 = $row['DruhyNazev'];
                $newRow->email = '';
                $newRow->phone = '';
                $newRow->contact = $row['Kontakt'];
                $newRow->web = '';
                $newRow->status = (int)$row['Stav'];
                $result->rows[] = $newRow;
            }

            //Construct response
            if($app['debug']) $app['monolog']->info('Response: data:'.json_encode($result));
            return $app->json($result);
        });
        
        // Get detail of client
        $controllers->get('/clients/{id}', function (Application $app, $id) 
        {
            if(empty($id) || !is_numeric($id))
                $app->abort(400, "Bad Request.");

            $result = new \stdClass();

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabCisOrg');
            
            // Name
            $qb->andWhere('TabCisOrg.ID = ?');
            $sqlParams[] = $id;

            // Get data
            $qb->select(
                        'TabCisOrg.ID',
                        'TabCisOrg.CisloOrg' ,
                        'TabCisOrg.NadrizenaOrg',
                        'TabCisOrg.Nazev',
                        'TabCisOrg.DruhyNazev',
                        'TabCisOrg.Ulice',
                        'TabCisOrg.OrCislo',
                        'TabCisOrg.PopCislo',
                        'TabCisOrg.Misto',
                        'TabCisOrg.PSC',
                        'TabCisOrg.Kontakt',
                        'TabCisOrg.ICO',
                        'TabCisOrg.DIC',
                        'TabCisOrg.Stav'
                        );

            $app['db']->prepare($qb->getSql());
            if($app['debug']) $app['monolog']->info('DB Select client detail :'.$qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            if(count($listData) < 1)
                $app->abort(404, "Not Found.");

            foreach($listData as $row)
            {
                $newRow = new \stdClass();
                $newRow->id = (int)$row['ID'];
                $newRow->orgnum = (int)$row['CisloOrg'];
                $newRow->parentid = (int)$row['NadrizenaOrg'];
                $newRow->name = $row['Nazev'];
                $newRow->name2 = $row['DruhyNazev'];
                $newRow->email = '';
                $newRow->phone = '';
                $newRow->address = new \stdClass();
                $newRow->address->street = $row['Ulice'];
                $newRow->address->streetorinumber = $row['OrCislo'];
                $newRow->address->streetdesnumber = $row['PopCislo'];
                $newRow->address->city = $row['Misto'];
                $newRow->address->zip = $row['PSC'];
                $newRow->contact = $row['Kontakt'];
                $newRow->ic = '';
                $newRow->dic = '';
                $newRow->web = '';
                $newRow->status = (int)$row['Stav'];
                $result = $newRow;
            }

            //Construct response
	        if($app['debug']) $app['monolog']->info('Response: data:'.json_encode($result));
            return $app->json($result);
        });

        // Create new client
        $controllers->post('/clients', function (Application $app) 
        {
            $newClientId = null;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);
            $qb = $app['db']->createQueryBuilder();

            // Check data
            $sqlParams = Array();

            // Generate orgnum
            if(empty($inputParams['orgnum']))
            {
                $sql = "DECLARE @CisloOrg INT;EXEC @CisloOrg=dbo.hp_NajdiPrvniVolny 'TabCisOrg','CisloOrg',1,2147483647,'',1,1;SELECT @CisloOrg AS neworgnum;";
                if($app['debug']) $app['monolog']->info('DB Select new orgnum:'.$sql);
                $queryResult = $app['db']->executeQuery($sql);
                $newOrgnum = $queryResult->fetch();
                $inputParams['orgnum'] = (int)$newOrgnum['neworgnum'];
            }

            // Check if client already exists
            $sql = 'SELECT 1 AS clientexists FROM TabCisOrg WHERE TabCisOrg.CisloOrg = '.$inputParams['orgnum'];
            if($app['debug']) $app['monolog']->info('DB Cseck if client with orgnum exists:'.$sql);
            $queryResult = $app['db']->executeQuery($sql);
            $clientExists = $queryResult->fetch();
            if($clientExists['clientexists'] == 1)
                $app->abort(409, "Conflict.");

            // Required fields
            if(
                $inputParams['orgnum'] != null && is_numeric($inputParams['orgnum']) &&
                $inputParams['name'] != null && strlen($inputParams['name']) <= 100 &&
                $inputParams['name2'] != null && strlen($inputParams['name2']) <= 100 &&
                $inputParams['street'] != null && strlen($inputParams['street']) <= 100 &&
                $inputParams['streetorinumber'] != null && strlen($inputParams['streetorinumber']) <= 15 &&
                $inputParams['streetdesnumber'] != null && strlen($inputParams['streetdesnumber']) <= 15 &&
                $inputParams['city'] != null && strlen($inputParams['city']) <= 100 &&
                is_numeric($inputParams['status'])
                )
            {
                $sqlParams['CisloOrg'] = $inputParams['orgnum'];
                $sqlParams['Nazev'] = $inputParams['name'];
                $sqlParams['DruhyNazev'] = $inputParams['name2'];
                $sqlParams['Ulice'] = $inputParams['street'];
                $sqlParams['OrCislo'] = $inputParams['streetorinumber'];
                $sqlParams['PopCislo'] = $inputParams['streetdesnumber'];
                $sqlParams['Misto'] = $inputParams['city'];
                $sqlParams['Stav'] = $inputParams['status'];
            }
            else
                $app->abort(400, "Bad Request.");

            // Optional fields
            if(
                ($inputParams['parentid'] == null || is_numeric($inputParams['parentid'])) &&
                ($inputParams['zip'] == null || strlen($inputParams['zip']) <= 10) &&
                ($inputParams['contact'] == null || strlen($inputParams['contact']) <= 40) &&
                ($inputParams['ic'] == null || strlen($inputParams['ic']) <= 20) &&
                ($inputParams['dic'] == null || strlen($inputParams['dic']) <= 15)
            )
            {
                $sqlParams['NadrizenaOrg'] = $inputParams['parentid'];
                $sqlParams['PSC'] = $inputParams['zip'];
                $sqlParams['Kontakt'] = $inputParams['contact'];
                $sqlParams['ICO'] = $inputParams['ic'];
                $sqlParams['DIC'] = $inputParams['dic'];
            }
            else
                $app->abort(400, "Bad Request.");

            $app['db']->insert('TabCisOrg', $sqlParams);
            $newClientId = $app['db']->lastInsertId();

            $response =  new Response(json_encode(array('id' => (int)$newClientId)), 201);
            $response->headers->set('Location', 'clients/'.$newClientId);
            return $response;
        });

        // Update all clients - method not allowed
        $controllers->put('/clients', function (Application $app)
        {
            $app->abort(405, "Method Not Allowed.");
        });

        // Update client
        $controllers->put('/clients/{id}', function (Application $app, $id)
        {
            $request = $app['request_stack']->getCurrentRequest();

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
            if($app['debug']) $app['monolog']->info('DB Select client by ID :'.$qb->getSql());
            $clientData = $app['db']->fetchAssoc($qb->getSql(), array($id));
            if(!is_array($clientData) || count($clientData) <= 0)
                $app->abort(404, "Not Found.");

            // Check data
            $inputData = $request->request->all();
            if(count($inputData) < 1)
                $app->abort(204, "No Content.");

            // Optional fields - but must be at least one
            $sqlParams = Array();
            if($request->get('orgnum') != null && is_numeric($request->get('orgnum')))
                $sqlParams['CisloOrg'] = $request->get('orgnum');
            if($request->get('name') != null && strlen($request->get('name')) <= 100)
                $sqlParams['Nazev'] = $request->get('name');
            if($request->get('name2') != null && strlen($request->get('name2')) <= 100)
                $sqlParams['DruhyNazev'] = $request->get('name2');
            if($request->get('street') != null && strlen($request->get('street')) <= 100)
                $sqlParams['Ulice'] = $request->get('street');
            if($request->get('streetorinumber') != null && strlen($request->get('streetorinumber')) <= 15)
                $sqlParams['OrCislo'] = $request->get('streetorinumber');
            if($request->get('streetdesnumber') != null && strlen($request->get('streetdesnumber')) <= 15)
                $sqlParams['PopCislo'] = $request->get('streetdesnumber');
            if($request->get('city') != null && strlen($request->get('city')) <= 100)
                $sqlParams['Misto'] = $request->get('city');
            if(is_numeric($request->get('status')))        
                $sqlParams['Stav'] = $request->get('status');
            if(($request->get('parentid') == null || is_numeric($request->get('parentid'))))
                $sqlParams['NadrizenaOrg'] = $request->get('parentid');
            if(($request->get('zip') == null || strlen($request->get('zip')) <= 10))
                $sqlParams['PSC'] = $request->get('zip');
            if(($request->get('contact') == null || strlen($request->get('contact')) <= 40))
                $sqlParams['Kontakt'] = $request->get('contact');
            if(($request->get('ic') == null || strlen($request->get('ic')) <= 20))
                $sqlParams['ICO'] = $request->get('ic');
            if(($request->get('dic') == null || strlen($request->get('dic')) <= 15))
                $sqlParams['DIC'] = $request->get('dic');

            // No input data received
            print_r($sqlParams);
            die();
            if(count($sqlParams) < 1)
                $app->abort(400, "Bad Request.");

            $app['db']->beginTransaction();
            $result = $app['db']->update('TabCisOrg', $sqlParams, array('ID' => $id));

            // If exactly 1 row was affected            
            if($result === 1)
                $app['db']->commit();
            else
            {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            return true;
        });

        // Get list of products
        $controllers->get('/products', function (Application $app) 
        {
            $result = new \stdClass();
            $request = $app['request_stack']->getCurrentRequest();

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabKmenZbozi');
            
            // Name
            if(!empty($request->get('name')))
            {
                $qb->andWhere('TabKmenZbozi.Nazev1 LIKE ?');
                $sqlParams[] = '%'.$request->get('name').'%';
                $qb->orWhere('TabKmenZbozi.Nazev2 LIKE ?');
                $sqlParams[] = '%'.$request->get('name').'%';
                $qb->orWhere('TabKmenZbozi.Nazev3 LIKE ?');
                $sqlParams[] = '%'.$request->get('name').'%';
                $qb->orWhere('TabKmenZbozi.Nazev4 LIKE ?');
                $sqlParams[] = '%'.$request->get('name').'%';
            }

            // Center
            if(!empty($request->get('centernumber')))
            {
                $qb->andWhere('TabKmenZbozi.KmenoveStredisko LIKE ?');
                $sqlParams[] = '%'.$request->get('centernumber').'%';
            }

            // Registration number
            if(!empty($request->get('regnumber')))
            {
                $qb->andWhere('TabKmenZbozi.RegCis LIKE ?');
                $sqlParams[] = '%'.$request->get('regnumber').'%';
            }

            // Get total rows count
            $qb->select('COUNT(TabKmenZbozi.ID) AS totalcount');
            $app['db']->prepare($qb->getSql());
            if($app['debug']) $app['monolog']->info('DB Select product whole list rows - TOTAL COUNT:'.$qb->getSql());
            $totalRows = $app['db']->fetchAll($qb->getSql(), $sqlParams);
            $result->totalrows = (int)$totalRows[0]['totalcount'];

            // Get part of lits
            $qb->select(
                        'TabKmenZbozi.ID', 
                        'TabKmenZbozi.RegCis', 
                        'TabKmenZbozi.SkupZbo',
                        'TabKmenZbozi.Nazev1',
                        'TabKmenZbozi.Nazev2',
                        'TabKmenZbozi.Nazev3',
                        'TabKmenZbozi.Nazev4',
                        'TabKmenZbozi.SKP'
                        );

            // Limit from
            if(!empty($request->get('listfrom')))
                $qb->setFirstResult($request->get('listfrom'));
            
            // Limit to
            if(!empty($request->get('listto')))
                $qb->setMaxResults($request->get('listto'));

			// Sort
			if(!empty($request->get('sort')))
			{
				switch($request->get('sort'))
				{
					case 'nameasc':
					{
						$qb->orderBy('TabKmenZbozi.Nazev1', 'ASC');
						break;
					}

					case 'namedesc':
					{
						$qb->orderBy('TabKmenZbozi.Nazev1', 'DESC');
						break;
					}

					default:
					{
						break;
					}
				}
			}

            $app['db']->prepare($qb->getSql());
            if($app['debug']) $app['monolog']->info('DB Select client list :'.$qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            foreach($listData as $row)
            {
                $newRow = new \stdClass();
                $newRow->id = (int)$row['ID'];
                $newRow->regnum = $row['RegCis'];
                $newRow->group = $row['SkupZbo'];
                $newRow->name1 = $row['Nazev1'];
                $newRow->name2 = $row['Nazev2'];
                $newRow->name3 = $row['Nazev3'];
                $newRow->name4 = $row['Nazev4'];
                $newRow->skp = $row['SKP'];
                $result->rows[] = $newRow;
            }

            //Construct response
	        if($app['debug']) $app['monolog']->info('Response: data:'.json_encode($result));
            return $app->json($result);
        });

        // Get detail of product
        $controllers->get('/products/{id}', function (Application $app, $id) 
        {
            $result = new \stdClass();
            $request = $app['request_stack']->getCurrentRequest();   

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabKmenZbozi');
            
            // Name
            $qb->andWhere('TabKmenZbozi.ID = ?');
            $sqlParams[] = $id;

            // Get data
            $qb->select(
                        'TabKmenZbozi.ID',
                        'TabKmenZbozi.SkupZbo' ,
                        'TabKmenZbozi.RegCis',
                        'TabKmenZbozi.Nazev1',
                        'TabKmenZbozi.Nazev2',
                        'TabKmenZbozi.Nazev3',
                        'TabKmenZbozi.Nazev4',
                        'TabKmenZbozi.SKP',
                        'TabKmenZbozi.IdSortiment',
                        'TabKmenZbozi.Upozorneni',
                        'TabKmenZbozi.Poznamka',
                        'TabKmenZbozi.MJEvidence',
                        'TabKmenZbozi.MJInventura',
                        'TabKmenZbozi.MJVstup',
                        'TabKmenZbozi.MJVystup',
                        'TabKmenZbozi.SazbaDPHVstup',
                        'TabKmenZbozi.SazbaDPHVystup',
                        'TabKmenZbozi.IDKodPDP',
                        'TabKmenZbozi.SazbaSDVstup',
                        'TabKmenZbozi.SazbaSDVystup',
                        'TabKmenZbozi.MJSD',
                        'TabKmenZbozi.KodSD',
                        'TabKmenZbozi.PrepocetMJSD'
                        );

            $app['db']->prepare($qb->getSql());
            if($app['debug']) $app['monolog']->info('DB Select product detail :'.$qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            foreach($listData as $row)
            {
                $newRow = new \stdClass();
                $newRow->id = (int)$row['ID'];
                $newRow->group = $row['SkupZbo'];
                $newRow->regnum = $row['RegCis'];
                $newRow->name = $row['Nazev1'];
                $newRow->name2 = $row['Nazev2'];
                $newRow->name3 = $row['Nazev3'];
                $newRow->name4 = $row['Nazev4'];
                $newRow->skp = $row['SKP'];
                $newRow->range = $row['IdSortiment'];
                $newRow->vintage = '';
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
                $result = $newRow;
            }

            //Construct response
	        if($app['debug']) $app['monolog']->info('Response: data:'.json_encode($result));
            return $app->json($result);
        });
        return $controllers;
    }
}
?>