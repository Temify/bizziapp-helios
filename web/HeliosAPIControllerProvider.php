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
            $paramsOk = true;
            $result = new \stdClass();
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = $request->query->all();

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabCisOrg');

            // Name
            if(!empty($inputParams['name']))
            {
                if(strlen($inputParams['name']) <= 100)
                {
                    $qb->andWhere('TabCisOrg.Nazev LIKE ?');
                    $sqlParams[] = '%'.$inputParams['name'].'%';
                    $qb->orWhere('TabCisOrg.DruhyNazev LIKE ?');
                    $sqlParams[] = '%'.$inputParams['name'].'%';
                }
                else
                    $paramsOk = false;
            }

            // Name is not null
            if(!empty($inputParams['nameisnotnull']))
            {
                if($inputParams['nameisnotnull'] == 'true')
                {
                    $qb->andWhere("(TabCisOrg.Nazev != '' || TabCisOrg.DruhyNazev != '')");
                }
                else if($inputParams['nameisnotnull'] == 'false')
                {
                    $qb->andWhere("TabCisOrg.Nazev = ''");
                    $qb->andWhere("TabCisOrg.DruhyNazev = ''");
                }
                else
                    $paramsOk = false;
            }

            // Status
            if(isset($inputParams['status']) && (!empty($inputParams['status']) || $inputParams['status'] == '0'))
            {
                if(is_numeric($inputParams['status']))
                {
                    $qb->andWhere('TabCisOrg.Stav = ?');
                    $sqlParams[] = $inputParams['status'];
                }
                else
                    $paramsOk = false;
            }

            if($paramsOk === false)
                $app->abort(400, "Bad Request.");

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
                if(is_numeric($inputParams['listfrom']))
                    $qb->setFirstResult($inputParams['listfrom']);
                else
                    $paramsOk = false;
            
            // Limit to
            if(!empty($inputParams['listto']))
                if(is_numeric($inputParams['listto']))
                    $qb->setMaxResults($inputParams['listto']);
                else
                    $paramsOk = false;

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
                        $paramsOk = false;
                        break;
                    }
				}
			}

            if($paramsOk === false)
                $app->abort(400, "Bad Request.");

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
                $newRow->website = $row['web'];
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
            
            // Id
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
                $newRow->website = '';
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
            $paramsOk = true;
            $newClientId = null;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);
            $qb = $app['db']->createQueryBuilder();

            // Check data
            $sqlParams = Array();

            // Default value: orgnum
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
            if($app['debug']) $app['monolog']->info('DB Check if client with orgnum exists:'.$sql);
            $queryResult = $app['db']->executeQuery($sql);
            $clientExists = $queryResult->fetch();
            if($clientExists['clientexists'] == 1)
                $app->abort(409, "Conflict.");

            // Required fields
            if(
                $inputParams['orgnum'] != null && is_numeric($inputParams['orgnum']) &&
                $inputParams['name'] != null && strlen($inputParams['name']) <= 100 &&
                $inputParams['street'] != null && strlen($inputParams['street']) <= 100 &&
                $inputParams['streetorinumber'] != null && strlen($inputParams['streetorinumber']) <= 15 &&
                $inputParams['streetdesnumber'] != null && strlen($inputParams['streetdesnumber']) <= 15 &&
                $inputParams['city'] != null && strlen($inputParams['city']) <= 100
                )
            {
                $sqlParams['CisloOrg'] = $inputParams['orgnum'];
                $sqlParams['Nazev'] = $inputParams['name'];
                $sqlParams['Ulice'] = $inputParams['street'];
                $sqlParams['OrCislo'] = $inputParams['streetorinumber'];
                $sqlParams['PopCislo'] = $inputParams['streetdesnumber'];
                $sqlParams['Misto'] = $inputParams['city'];
            }
            else
                $app->abort(400, "Bad Request.");

            // Optional fields
            if($inputParams['status'] != null)
                if(is_numeric($inputParams['status']))
                    $sqlParams['Stav'] = $inputParams['status'];
                else
                    $paramsOk = false;

            if($inputParams['name2'] != null)
                if(strlen($inputParams['name2']) <= 100)
                    $sqlParams['DruhyNazev'] = $inputParams['name2'];
                else
                    $paramsOk = false;

            if($inputParams['parentid'] != null)
                if(is_numeric($inputParams['parentid']))
                    $sqlParams['NadrizenaOrg'] = $inputParams['parentid'];
                else
                    $paramsOk = false;

            if($inputParams['zip'] != null)
                if(strlen($inputParams['zip']) <= 10)
                    $sqlParams['PSC'] = $inputParams['zip'];
                else
                    $paramsOk = false;
            if($inputParams['contact'] != null)
                if(strlen($inputParams['contact']) <= 40)
                    $sqlParams['Kontakt'] = $inputParams['contact'];
                else
                    $paramsOk = false;
            if($inputParams['ic'] != null)
                if(strlen($inputParams['ic']) <= 20)
                    $sqlParams['ICO'] = $inputParams['ic'];
                else
                    $paramsOk = false;
            if($inputParams['dic'] != null)
                if(strlen($inputParams['dic']) <= 15)
                $sqlParams['DIC'] = $inputParams['dic'];
                else
                    $paramsOk = false;
            
            if($paramsOk === false)
                $app->abort(400, "Bad Request.");


            $app['db']->beginTransaction();
            $result = $app['db']->insert('TabCisOrg', $sqlParams);
            $newClientId = $app['db']->lastInsertId();

            // If exactly 1 row was affected            
            if($result === 1)
                $app['db']->commit();
            else
            {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

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
            if(empty($id) || !is_numeric($id))
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
            if($app['debug']) $app['monolog']->info('DB Select client by ID :'.$qb->getSql());
            $clientData = $app['db']->fetchAssoc($qb->getSql(), array($id));
            if(!is_array($clientData) || count($clientData) <= 0)
                $app->abort(404, "Not Found.");

            // Check data
            if(count($inputParams) < 1)
                $app->abort(204, "No Content.");

            // Optional fields - but must be at least one
            $sqlParams = Array();
            if($inputParams['orgnum'] != null)
                if(is_numeric($inputParams['orgnum']))
                    $sqlParams['CisloOrg'] = $inputParams['orgnum'];
                else
                    $paramsOk = false;
            if($inputParams['name'] != null)
                if(strlen($inputParams['name']) <= 100)
                    $sqlParams['Nazev'] = $inputParams['name'];
                else
                    $paramsOk = false;
            if($inputParams['name2'] != null)
                if(strlen($inputParams['name2']) <= 100)
                    $sqlParams['DruhyNazev'] = $inputParams['name2'];
                else
                    $paramsOk = false;
            if($inputParams['street'] != null)
                if(strlen($inputParams['street']) <= 100)
                    $sqlParams['Ulice'] = $inputParams['street'];
                else
                    $paramsOk = false;
            if($inputParams['streetorinumber'] != null)
                if(strlen($inputParams['streetorinumber']) <= 15)
                    $sqlParams['OrCislo'] = $inputParams['streetorinumber'];
                else
                    $paramsOk = false;
            if($inputParams['streetdesnumber'] != null)
                if(strlen($inputParams['streetdesnumber']) <= 15)
                    $sqlParams['PopCislo'] = $inputParams['streetdesnumber'];
                else
                    $paramsOk = false;
            if($inputParams['city'] != null)
                if(strlen($inputParams['city']) <= 100)
                    $sqlParams['Misto'] = $inputParams['city'];
                else
                    $paramsOk = false;
            if($inputParams['status'] != null)
                if(is_numeric($inputParams['status']))
                    $sqlParams['Stav'] = $inputParams['status'];
                else
                    $paramsOk = false;
            if($inputParams['parentid'] != null)
                if(is_numeric($inputParams['parentid']))
                    $sqlParams['NadrizenaOrg'] = $inputParams['parentid'];
                else
                    $paramsOk = false;
            if($inputParams['zip'] != null)
                if(strlen($inputParams['zip']) <= 10)
                    $sqlParams['PSC'] = $inputParams['zip'];
                else
                    $paramsOk = false;
            if($inputParams['contact'] != null)
                if(strlen($inputParams['contact']) <= 40)
                    $sqlParams['Kontakt'] = $inputParams['contact'];
                else
                    $paramsOk = false;
            if($inputParams['ic'] != null)
                if(strlen($inputParams['ic']) <= 20)
                    $sqlParams['ICO'] = $inputParams['ic'];
                else
                    $paramsOk = false;
            if($inputParams['dic'] != null)
                if(strlen($inputParams['dic']) <= 15)
                    $sqlParams['DIC'] = $inputParams['dic'];
                else
                    $paramsOk = false;

            // No input data received or bad format data
            if(count($sqlParams) < 1 || $paramsOk === false)
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

            $response =  new Response(null, 200);
            return $response;
        });

        // Delete all clients - method not allowed
        $controllers->delete('/clients', function (Application $app)
        {
            $app->abort(405, "Method Not Allowed.");
        });

        // Delete client
        $controllers->delete('/clients/{id}', function (Application $app, $id)
        {
            if(empty($id) || !is_numeric($id))
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
            if($app['debug']) $app['monolog']->info('DB Select client by ID :'.$qb->getSql());
            $clientData = $app['db']->fetchAssoc($qb->getSql(), array($id));
            if(!is_array($clientData) || count($clientData) <= 0)
                $app->abort(404, "Not Found.");

            $app['db']->beginTransaction();
            $result = $app['db']->update('TabCisOrg', array('Stav' => 1), array('ID' => $id));

            // If exactly 1 row was affected            
            if($result === 1)
                $app['db']->commit();
            else
            {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response =  new Response(null, 200);
            return $response;
        });

        // Get list of contacts
        $controllers->get('/contacts', function (Application $app) 
        {
            $paramsOk = true;
            $result = new \stdClass();
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = $request->query->all();

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabKontakty');

            // Type
            if(!empty($inputParams['type']))
            {
                if(strlen($inputParams['type']) <= 5 && is_numeric($inputParams['type']))
                {
                    $qb->andWhere('TabKontakty.Druh = ?');
                    $sqlParams[] = $inputParams['type'];
                }
                else
                    $paramsOk = false;
            }

            // Organisation id
            if(!empty($inputParams['orgid']))
            {
                if(strlen($inputParams['orgid']) <= 10 && is_numeric($inputParams['orgid']))
                {
                    $qb->andWhere('TabKontakty.IDOrg = ?');
                    $sqlParams[] = $inputParams['orgid'];
                }
                else
                    $paramsOk = false;
            }

            // Primary
            if(!empty($inputParams['primary']) || $inputParams['primary'] == 0)
            {
                if(strlen($inputParams['primary']) <= 1 && is_numeric($inputParams['primary']))
                {
                    $qb->andWhere('TabKontakty.Prednastaveno = ?');
                    $sqlParams[] = $inputParams['primary'];
                }
                else
                    $paramsOk = false;
            }

            // Description
            if(!empty($inputParams['description']))
            {
                if(strlen($inputParams['description']) <= 255)
                {
                    $qb->andWhere('TabKontakty.Popis LIKE ?');
                    $sqlParams[] = '%'.$inputParams['description'].'%';
                }
                else
                    $paramsOk = false;
            }

           // Connection
            if(!empty($inputParams['connection']))
            {
                if(strlen($inputParams['connection']) <= 255)
                {
                    $qb->andWhere('TabKontakty.Spojeni LIKE ?');
                    $sqlParams[] = '%'.$inputParams['connection'].'%';
                }
                else
                    $paramsOk = false;
            }

           // Connection 2
            if(!empty($inputParams['connection2']))
            {
                if(strlen($inputParams['connection2']) <= 255)
                {
                    $qb->andWhere('TabKontakty.Spojeni2 LIKE ?');
                    $sqlParams[] = '%'.$inputParams['connection2'].'%';
                }
                else
                    $paramsOk = false;
            }

            if($paramsOk === false)
                $app->abort(400, "Bad Request.");

            // Get total rows count
            $qb->select('COUNT(TabKontakty.ID) AS totalcount');
            $app['db']->prepare($qb->getSql());
            if($app['debug']) $app['monolog']->info('DB Select contact whole list rows - TOTAL COUNT:'.$qb->getSql());

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
            if(!empty($inputParams['listfrom']))
                if(is_numeric($inputParams['listfrom']))
                    $qb->setFirstResult($inputParams['listfrom']);
                else
                    $paramsOk = false;
            
            // Limit to
            if(!empty($inputParams['listto']))
                if(is_numeric($inputParams['listto']))
                    $qb->setMaxResults($inputParams['listto']);
                else
                    $paramsOk = false;

			// Sort
			if(!empty($inputParams['sort']))
			{
				switch($inputParams['sort'])
				{
					case 'typeasc':
					{
						$qb->orderBy('TabKontakty.Druh', 'ASC');
						break;
					}

					case 'typedesc':
					{
						$qb->orderBy('TabKontakty.Druh', 'DESC');
						break;
					}

					case 'connectionasc':
					{
						$qb->orderBy('TabKontakty.Spojeni', 'ASC');
						break;
					}

					case 'connectiondesc':
					{
						$qb->orderBy('TabKontakty.Spojeni', 'DESC');
						break;
					}

                    default:
                    {
                        $paramsOk = false;
                        break;
                    }
				}
			}

            if($paramsOk === false)
                $app->abort(400, "Bad Request.");

            $app['db']->prepare($qb->getSql());
            if($app['debug']) $app['monolog']->info('DB Select contact list :'.$qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            foreach($listData as $row)
            {
                $newRow = new \stdClass();
                $newRow->id = (int)$row['ID'];
                $newRow->orgid = (int)$row['IDOrg'];
                $newRow->type = (int)$row['Druh'];
                $newRow->primary = (int)$row['Prednastaveno'];
                $newRow->description = $row['Popis'];
                $newRow->connection = $row['Spojeni'];
                $newRow->connection2 = $row['Spojeni2'];
                $result->rows[] = $newRow;
            }

            //Construct response
            if($app['debug']) $app['monolog']->info('Response: data:'.json_encode($result));
            return $app->json($result);
        });
        
        // Get detail of contact
        $controllers->get('/contacts/{id}', function (Application $app, $id) 
        {
            if(empty($id) || !is_numeric($id))
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
            if($app['debug']) $app['monolog']->info('DB Select contact detail :'.$qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            if(count($listData) < 1)
                $app->abort(404, "Not Found.");

            foreach($listData as $row)
            {
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
	        if($app['debug']) $app['monolog']->info('Response: data:'.json_encode($result));
            return $app->json($result);
        });

        // Create new contact
        $controllers->post('/contacts', function (Application $app) 
        {
            $paramsOk = true;
            $newClientId = null;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);
            $qb = $app['db']->createQueryBuilder();

            // Check data
            $sqlParams = Array();

            // Default value: primary
            if(empty($inputParams['primary']))
                $inputParams['primary'] = 0;

            // Required fields
            if(
                $inputParams['orgid'] != null && is_numeric($inputParams['orgid']) &&
                $inputParams['type'] != null && is_numeric($inputParams['type']) &&
                ($inputParams['primary'] != null || $inputParams['primary'] == 0) && is_numeric($inputParams['primary'])
                )
            {
                $sqlParams['IDOrg'] = $inputParams['orgid'];
                $sqlParams['Druh'] = $inputParams['type'];
                $sqlParams['Prednastaveno'] = $inputParams['primary'];
            }
            else
                $app->abort(400, "Bad Request.");

            // Optional fields
            if($inputParams['description'] != null)
                if(strlen($inputParams['description']) <= 255)
                    $sqlParams['Popis'] = $inputParams['description'];
                else
                    $paramsOk = false;

            if($inputParams['connection'] != null)
                if(strlen($inputParams['connection']) <= 255)
                    $sqlParams['Spojeni'] = $inputParams['connection'];
                else
                    $paramsOk = false;

            if($inputParams['connection2'] != null)
                if(strlen($inputParams['connection2']) <= 255)
                    $sqlParams['Spojeni2'] = $inputParams['connection2'];
                else
                    $paramsOk = false;

            if($paramsOk === false)
                $app->abort(400, "Bad Request.");


            $app['db']->beginTransaction();
            $result = $app['db']->insert('TabKontakty', $sqlParams);
            $newContactId = $app['db']->lastInsertId();

            // If exactly 1 row was affected            
            if($result === 1)
                $app['db']->commit();
            else
            {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response =  new Response(json_encode(array('id' => (int)$newContactId)), 201);
            $response->headers->set('Location', 'contacts/'.$newContactId);
            return $response;
        });

        // Update all contacts - method not allowed
        $controllers->put('/contacts', function (Application $app)
        {
            $app->abort(405, "Method Not Allowed.");
        });

        // Update contact
        $controllers->put('/contacts/{id}', function (Application $app, $id)
        {
            if(empty($id) || !is_numeric($id))
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
            if($app['debug']) $app['monolog']->info('DB Select contact by ID :'.$qb->getSql());
            $contactData = $app['db']->fetchAssoc($qb->getSql(), array($id));
            if(!is_array($contactData) || count($contactData) <= 0)
                $app->abort(404, "Not Found.");

            // Check data
            if(count($inputParams) < 1)
                $app->abort(204, "No Content.");

            // Optional fields - but must be at least one
            $sqlParams = Array();
            if($inputParams['orgid'] != null)
                if(is_numeric($inputParams['orgid']))
                    $sqlParams['IDOrg'] = $inputParams['orgid'];
                else
                    $paramsOk = false;
            
            if($inputParams['type'] != null)
                if(is_numeric($inputParams['type']))
                    $sqlParams['Druh'] = $inputParams['type'];
                else
                    $paramsOk = false;

            if(($inputParams['primary'] != null || $inputParams['primary'] == '0'))
                if(is_numeric($inputParams['primary']))
                    $sqlParams['Prednastaveno'] = $inputParams['primary'];
                else
                    $paramsOk = false;

            if($inputParams['description'] != null)
                if(strlen($inputParams['description']) <= 255)
                    $sqlParams['Popis'] = $inputParams['description'];
                else
                    $paramsOk = false;

            if($inputParams['connection'] != null)
                if(strlen($inputParams['connection']) <= 255)
                    $sqlParams['Spojeni'] = $inputParams['connection'];
                else
                    $paramsOk = false;

            if($inputParams['connection2'] != null)
                if(strlen($inputParams['connection2']) <= 255)
                    $sqlParams['Spojeni2'] = $inputParams['connection2'];
                else
                    $paramsOk = false;

            // No input data received or bad format data
            if(count($sqlParams) < 1 || $paramsOk === false)
                $app->abort(400, "Bad Request.");

            $app['db']->beginTransaction();
            $result = $app['db']->update('TabKontakty', $sqlParams, array('ID' => $id));

            // If exactly 1 row was affected            
            if($result === 1)
                $app['db']->commit();
            else
            {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response =  new Response(null, 200);
            return $response;
        });

        // Delete all contacts - method not allowed
        $controllers->delete('/contacts', function (Application $app)
        {
            $app->abort(405, "Method Not Allowed.");
        });

        // Delete contact
        $controllers->delete('/contacts/{id}', function (Application $app, $id)
        {
            if(empty($id) || !is_numeric($id))
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
            if($app['debug']) $app['monolog']->info('DB Select contact by ID :'.$qb->getSql());
            $contactData = $app['db']->fetchAssoc($qb->getSql(), array($id));
            if(!is_array($contactData) || count($contactData) <= 0)
                $app->abort(404, "Not Found.");

            $app['db']->beginTransaction();
            $result = $app['db']->delete('TabKontakty', array('ID' => $id));

            // If exactly 1 row was affected            
            if($result === 1)
                $app['db']->commit();
            else
            {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response =  new Response(null, 200);
            return $response;
        });

        // Get list of products
        $controllers->get('/products', function (Application $app) 
        {
            $paramsOk = true;
            $result = new \stdClass();
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = $request->query->all();

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabKmenZbozi');
            
            // Name
            if(!empty($inputParams['name']))
            {
                if(strlen($inputParams['name']) <= 100)
                {
                    $qb->andWhere('TabKmenZbozi.Nazev1 LIKE ?');
                    $sqlParams[] = '%'.$inputParams['name'].'%';
                    $qb->orWhere('TabKmenZbozi.Nazev2 LIKE ?');
                    $sqlParams[] = '%'.$inputParams['name'].'%';
                    $qb->orWhere('TabKmenZbozi.Nazev3 LIKE ?');
                    $sqlParams[] = '%'.$inputParams['name'].'%';
                    $qb->orWhere('TabKmenZbozi.Nazev4 LIKE ?');
                    $sqlParams[] = '%'.$inputParams['name'].'%';
                }
                else
                    $paramsOk = false;
            }

            // Center
            if(!empty($inputParams['centernumber']))
            {
                if(strlen($inputParams['centernumber']) <= 30)
                {
                    $qb->andWhere('TabKmenZbozi.KmenoveStredisko LIKE ?');
                    $sqlParams[] = '%'.$inputParams['centernumber'].'%';
                }
                else
                    $paramsOk = false;
            }

            // Registration number
            if(!empty($inputParams['regnumber']))
            {
                if(strlen($inputParams['regnumber']) <= 30)
                {
                    $qb->andWhere('TabKmenZbozi.RegCis LIKE ?');
                    $sqlParams[] = '%'.$inputParams['regnumber'].'%';
                }
                else
                    $paramsOk = false;
            }

            if($paramsOk === false)
                $app->abort(400, "Bad Request.");

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
                        'TabKmenZbozi.SKP',
                        'TabKmenZbozi.Blokovano'
                        );

            // Limit from
            if(!empty($inputParams['listfrom']))
                if(is_numeric($inputParams['listfrom']))
                    $qb->setFirstResult($inputParams['listfrom']);
                else
                    $paramsOk = false;
            
            // Limit to
            if(!empty($inputParams['listto']))
                if(is_numeric($inputParams['listto']))
                    $qb->setMaxResults($inputParams['listto']);
                else
                    $paramsOk = false;

			// Sort
			if(!empty($inputParams['sort']))
			{
				switch($inputParams['sort'])
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
                        $paramsOk = false;
						break;
					}
				}
			}

            if($paramsOk === false)
                $app->abort(400, "Bad Request.");

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
                $newRow->blocked = (int)$row['Blokovano'];
                $result->rows[] = $newRow;
            }

            //Construct response
	        if($app['debug']) $app['monolog']->info('Response: data:'.json_encode($result));
            return $app->json($result);
        });

        // Get detail of product
        $controllers->get('/products/{id}', function (Application $app, $id) 
        {
            if(empty($id) || !is_numeric($id))
                $app->abort(400, "Bad Request.");

            $result = new \stdClass();
            $request = $app['request_stack']->getCurrentRequest();   

            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabKmenZbozi');
            
            // Id
            $qb->andWhere('TabKmenZbozi.ID = ?');
            $sqlParams[] = $id;

            // Get data
            $qb->select(
                        'TabKmenZbozi.ID',
                        'TabKmenZbozi.SkupZbo',
                        'TabKmenZbozi.RegCis',
                        'TabKmenZbozi.DruhSkladu',
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
                        'TabKmenZbozi.PrepocetMJSD',
                        'TabKmenZbozi.Blokovano'
                        );

            $app['db']->prepare($qb->getSql());
            if($app['debug']) $app['monolog']->info('DB Select product detail :'.$qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            if(count($listData) < 1)
                $app->abort(404, "Not Found.");

            foreach($listData as $row)
            {
                $newRow = new \stdClass();
                $newRow->id = (int)$row['ID'];
                $newRow->group = $row['SkupZbo'];
                $newRow->regnum = $row['RegCis'];
                $newRow->storagetype = $row['DruhSkladu'];
                $newRow->name = $row['Nazev1'];
                $newRow->name2 = $row['Nazev2'];
                $newRow->name3 = $row['Nazev3'];
                $newRow->name4 = $row['Nazev4'];
                $newRow->skp = $row['SKP'];
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
                $result = $newRow;
            }

            //Construct response
	        if($app['debug']) $app['monolog']->info('Response: data:'.json_encode($result));
            return $app->json($result);
        });

        // Create new product
        $controllers->post('/products', function (Application $app) 
        {
            $paramsOk = true;
            $newProductId = null;
            $request = $app['request_stack']->getCurrentRequest();
            $inputParams = json_decode($request->getContent(), true);
            $qb = $app['db']->createQueryBuilder();

            // Check data
            $sqlParams = Array();

            if(empty($inputParams['storagetype']) && $inputParams['storagetype'] != '0')
                $inputParams['storagetype'] = 1;

            if(empty($inputParams['blocked']))
                $inputParams['blocked'] = 0;

            // Check if product already exists
            $sql = 'SELECT 1 AS productexists FROM TabKmenZbozi WHERE TabKmenZbozi.RegCis = \''.$inputParams['regnum'].'\'';
            if($app['debug']) $app['monolog']->info('DB Check if product with regnum exists:'.$sql);
            $queryResult = $app['db']->executeQuery($sql);
            $clientExists = $queryResult->fetch();
            if($clientExists['productexists'] == 1)
                $app->abort(409, "Conflict.");

            // Required fields
            if(
                $inputParams['group'] != null && strlen($inputParams['group']) <= 3 &&
                $inputParams['regnum'] != null && strlen($inputParams['regnum']) <= 30 &&
                $inputParams['name'] != null && strlen($inputParams['name']) <= 100 &&
                $inputParams['storagetype'] != null && is_numeric($inputParams['storagetype']) &&
                ($inputParams['blocked'] != null || $inputParams['blocked'] == 0) && is_numeric($inputParams['blocked'])
                )
            {
                $sqlParams['SkupZbo'] = $inputParams['group'];
                $sqlParams['RegCis'] = $inputParams['regnum'];
                $sqlParams['Nazev1'] = $inputParams['name'];
                $sqlParams['DruhSkladu'] = $inputParams['storagetype'];
                $sqlParams['Blokovano'] = $inputParams['blocked'];
            }
            else
                $app->abort(400, "Bad Request.");

            // Optional fields
            if($inputParams['name2'] != null)
                if(strlen($inputParams['name2']) <= 100)
                    $sqlParams['Nazev2'] = $inputParams['name2'];
                else
                    $paramsOk = false;

            if($inputParams['name3'] != null)
                if(strlen($inputParams['name3']) <= 100)
                    $sqlParams['Nazev3'] = $inputParams['name3'];
                else
                    $paramsOk = false;

            if($inputParams['name4'] != null)
                if(strlen($inputParams['name4']) <= 100)
                    $sqlParams['Nazev4'] = $inputParams['name4'];
                else
                    $paramsOk = false;

            if($inputParams['skp'] != null)
                if(strlen($inputParams['skp']) <= 50)
                    $sqlParams['SKP'] = $inputParams['skp'];
                else
                    $paramsOk = false;

            if($inputParams['range'] != null)
                if(is_numeric($inputParams['range']))
                    $sqlParams['IdSortiment'] = $inputParams['range'];
                else
                    $paramsOk = false;

            if($inputParams['notice'] != null)
                if(strlen($inputParams['notice']) <= 255)
                    $sqlParams['Upozorneni'] = $inputParams['notice'];
                else
                    $paramsOk = false;

            if($inputParams['note'] != null)
                if(strlen($inputParams['note']) <= 1073741823)
                    $sqlParams['Poznamka'] = $inputParams['note'];
                else
                    $paramsOk = false;

            if($inputParams['muevidence'] != null)
                if(strlen($inputParams['muevidence']) <= 10)
                    $sqlParams['MJEvidence'] = $inputParams['muevidence'];
                else
                    $paramsOk = false;

            if($inputParams['mustocktaking'] != null)
                if(strlen($inputParams['mustocktaking']) <= 10)
                    $sqlParams['MJInventura'] = $inputParams['mustocktaking'];
                else
                    $paramsOk = false;

            if($inputParams['muinput'] != null)
                if(strlen($inputParams['muinput']) <= 10)
                    $sqlParams['MJVstup'] = $inputParams['muinput'];
                else
                    $paramsOk = false;

            if($inputParams['muoutput'] != null)
                if(strlen($inputParams['muoutput']) <= 10)
                    $sqlParams['MJVystup'] = $inputParams['muoutput'];
                else
                    $paramsOk = false;

            if($inputParams['vatinput'] != null)
                if(is_numeric($inputParams['vatinput']))
                    $sqlParams['SazbaDPHVstup'] = $inputParams['vatinput'];
                else
                    $paramsOk = false;

            if($inputParams['vatoutput'] != null)
                if(is_numeric($inputParams['vatoutput']))
                    $sqlParams['SazbaDPHVystup'] = $inputParams['vatoutput'];
                else
                    $paramsOk = false;

            if($inputParams['pdpcode'] != null)
                if(is_numeric($inputParams['pdpcode']))
                    $sqlParams['IDKodPDP'] = $inputParams['pdpcode'];
                else
                    $paramsOk = false;

            if($inputParams['edinput'] != null)
                if(is_numeric($inputParams['edinput']))
                    $sqlParams['SazbaSDVstup'] = $inputParams['edinput'];
                else
                    $paramsOk = false;

            if($inputParams['edoutput'] != null)
                if(is_numeric($inputParams['edoutput']))
                    $sqlParams['SazbaSDVystup'] = $inputParams['edoutput'];
                else
                    $paramsOk = false;

            if($inputParams['mued'] != null)
                if(strlen($inputParams['mued']) <= 10)
                    $sqlParams['MJSD'] = $inputParams['mued'];
                else
                    $paramsOk = false;

            if($inputParams['edcode'] != null)
                if(strlen($inputParams['edcode']) <= 10)
                    $sqlParams['KodSD'] = $inputParams['edcode'];
                else
                    $paramsOk = false;

            if($inputParams['edcalc'] != null)
                if(is_numeric($inputParams['edcalc']))
                    $sqlParams['PrepocetMJSD'] = $inputParams['edcalc'];
                else
                    $paramsOk = false;
            
            if($paramsOk === false)
                $app->abort(400, "Bad Request.");

            $app['db']->beginTransaction();
            $result = $app['db']->insert('TabKmenZbozi', $sqlParams); 
            $newProductId = $app['db']->lastInsertId();

            // If exactly 1 row was affected            
            if($result === 1)
                $app['db']->commit();
            else
            {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response =  new Response(json_encode(array('id' => (int)$newProductId)), 201);
            $response->headers->set('Location', 'products/'.$newProductId);
            return $response;
        });

        // Update all products - method not allowed
        $controllers->put('/products', function (Application $app)
        {
            $app->abort(405, "Method Not Allowed.");
        });
        
        // Update product
        $controllers->put('/products/{id}', function (Application $app, $id) 
        {
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
            if($app['debug']) $app['monolog']->info('DB Select product by ID :'.$qb->getSql());
            $productData = $app['db']->fetchAssoc($qb->getSql(), array($id));
            if(!is_array($productData) || count($productData) <= 0)
                $app->abort(404, "Not Found.");

            // Optional fields
            if($inputParams['group'] != null)
                if(strlen($inputParams['group']) <= 3)
                    $sqlParams['SkupZbo'] = $inputParams['group'];
                else
                    $paramsOk = false;
             
            if($inputParams['regnum'] != null)
                if(strlen($inputParams['regnum']) <= 30)
                    $sqlParams['RegCis'] = $inputParams['regnum'];
                else
                    $paramsOk = false;

            if($inputParams['name'] != null)
                if(strlen($inputParams['name']) <= 100)
                    $sqlParams['Nazev1'] = $inputParams['name'];
                else
                    $paramsOk = false;

            if($inputParams['storagetype'] != null)
                if(is_numeric($inputParams['storagetype']))
                    $sqlParams['DruhSkladu'] = $inputParams['storagetype'];
                else
                    $paramsOk = false;

            if($inputParams['name2'] != null)
                if(strlen($inputParams['name2']) <= 100)
                    $sqlParams['Nazev2'] = $inputParams['name2'];
                else
                    $paramsOk = false;

            if($inputParams['name3'] != null)
                if(strlen($inputParams['name3']) <= 100)
                    $sqlParams['Nazev3'] = $inputParams['name3'];
                else
                    $paramsOk = false;

            if($inputParams['name4'] != null)
                if(strlen($inputParams['name4']) <= 100)
                    $sqlParams['Nazev4'] = $inputParams['name4'];
                else
                    $paramsOk = false;

            if($inputParams['skp'] != null)
                if(strlen($inputParams['skp']) <= 50)
                    $sqlParams['SKP'] = $inputParams['skp'];
                else
                    $paramsOk = false;

            if($inputParams['range'] != null)
                if(is_numeric($inputParams['range']))
                    $sqlParams['IdSortiment'] = $inputParams['range'];
                else
                    $paramsOk = false;

            if($inputParams['notice'] != null)
                if(strlen($inputParams['notice']) <= 255)
                    $sqlParams['Upozorneni'] = $inputParams['notice'];
                else
                    $paramsOk = false;

            if($inputParams['note'] != null)
                if(strlen($inputParams['note']) <= 1073741823)
                    $sqlParams['Poznamka'] = $inputParams['note'];
                else
                    $paramsOk = false;

            if($inputParams['muevidence'] != null)
                if(strlen($inputParams['muevidence']) <= 10)
                    $sqlParams['MJEvidence'] = $inputParams['muevidence'];
                else
                    $paramsOk = false;

            if($inputParams['mustocktaking'] != null)
                if(strlen($inputParams['mustocktaking']) <= 10)
                    $sqlParams['MJInventura'] = $inputParams['mustocktaking'];
                else
                    $paramsOk = false;

            if($inputParams['muinput'] != null)
                if(strlen($inputParams['muinput']) <= 10)
                    $sqlParams['MJVstup'] = $inputParams['muinput'];
                else
                    $paramsOk = false;

            if($inputParams['muoutput'] != null)
                if(strlen($inputParams['muoutput']) <= 10)
                    $sqlParams['MJVystup'] = $inputParams['muoutput'];
                else
                    $paramsOk = false;

            if($inputParams['vatinput'] != null)
                if(is_numeric($inputParams['vatinput']))
                    $sqlParams['SazbaDPHVstup'] = $inputParams['vatinput'];
                else
                    $paramsOk = false;

            if($inputParams['vatoutput'] != null)
                if(is_numeric($inputParams['vatoutput']))
                    $sqlParams['SazbaDPHVystup'] = $inputParams['vatoutput'];
                else
                    $paramsOk = false;

            if($inputParams['pdpcode'] != null)
                if(is_numeric($inputParams['pdpcode']))
                    $sqlParams['IDKodPDP'] = $inputParams['pdpcode'];
                else
                    $paramsOk = false;

            if($inputParams['edinput'] != null)
                if(is_numeric($inputParams['edinput']))
                    $sqlParams['SazbaSDVstup'] = $inputParams['edinput'];
                else
                    $paramsOk = false;

            if($inputParams['edoutput'] != null)
                if(is_numeric($inputParams['edoutput']))
                    $sqlParams['SazbaSDVystup'] = $inputParams['edoutput'];
                else
                    $paramsOk = false;

            if($inputParams['mued'] != null)
                if(strlen($inputParams['mued']) <= 10)
                    $sqlParams['MJSD'] = $inputParams['mued'];
                else
                    $paramsOk = false;

            if($inputParams['edcode'] != null)
                if(strlen($inputParams['edcode']) <= 10)
                    $sqlParams['KodSD'] = $inputParams['edcode'];
                else
                    $paramsOk = false;

            if($inputParams['edcalc'] != null)
                if(is_numeric($inputParams['edcalc']))
                    $sqlParams['PrepocetMJSD'] = $inputParams['edcalc'];
                else
                    $paramsOk = false;

            if(($inputParams['blocked'] != null || $inputParams['blocked'] == 0))
                if(is_numeric($inputParams['blocked']))
                    $sqlParams['Blokovano'] = $inputParams['blocked'];
                else
                    $paramsOk = false;

            // No input data received or bad format data
            if(count($sqlParams) < 1 || $paramsOk === false)
                $app->abort(400, "Bad Request.");

            $app['db']->beginTransaction();
            $result = $app['db']->update('TabKmenZbozi', $sqlParams, array('ID' => $id));

            // If exactly 1 row was affected            
            if($result === 1)
                $app['db']->commit();
            else
            {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response =  new Response(null, 200);
            return $response;
        });

        // Delete all products - method not allowed
        $controllers->delete('/products', function (Application $app)
        {
            $app->abort(405, "Method Not Allowed.");
        });

        // Delete product
        $controllers->delete('/products/{id}', function (Application $app, $id)
        {
            if(empty($id) || !is_numeric($id))
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
            if($app['debug']) $app['monolog']->info('DB Select product by ID :'.$qb->getSql());
            $productData = $app['db']->fetchAssoc($qb->getSql(), array($id));
            if(!is_array($productData) || count($productData) <= 0)
                $app->abort(404, "Not Found.");

            $app['db']->beginTransaction();
            $result = $app['db']->update('TabKmenZbozi', array('Blokovano' => 1), array('ID' => $id));

            // If exactly 1 row was affected            
            if($result === 1)
                $app['db']->commit();
            else
            {
                $app['db']->rollBack();
                $app->abort(500, "Internal Server Error.");
            }

            $response =  new Response(null, 200);
            return $response;
        });

        return $controllers;
    }
}
?>