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
            $qb = $app['db']->createQueryBuilder();
            $sqlParams = Array();

            $qb->from('TabCisOrg');
            
            // Name
            if(!empty($request->get('name')))
            {
                $qb->andWhere('TabCisOrg.Nazev LIKE ?');
                $sqlParams[] = '%'.$request->get('name').'%';
                $qb->orWhere('TabCisOrg.DruhyNazev LIKE ?');
                $sqlParams[] = '%'.$request->get('name').'%';
            }

            // Status
            if(!empty($request->get('status')) || $request->get('status') == '0')
            {
                $qb->andWhere('TabCisOrg.Stav = ?');
                $sqlParams[] = $request->get('status');
            }

            // Get total rows count
            $qb->select('COUNT(TabCisOrg.ID) AS totalcount');
            $app['db']->prepare($qb->getSql());
            $app['monolog']->info('DB Select client whole list rows - TOTAL COUNT:'.$qb->getSql());

            $totalRows = $app['db']->fetchAll($qb->getSql(), $sqlParams);
            $result->totalrows = $totalRows[0]['totalcount'];

            // if(count($sqlParams) > 0)
            //     $totalRows = $app['db']->fetchAll('SELECT COUNT(TabCisOrg.ID) AS totalcount FROM TabCisOrg WHERE (TabCisOrg.Nazev LIKE ?) OR (TabCisOrg.DruhyNazev LIKE ?)', $sqlParams);
            // else
            //     $totalRows = $app['db']->fetchAll('SELECT COUNT(TabCisOrg.ID) AS totalcount FROM TabCisOrg');

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
            $app['monolog']->info('DB Select client list :'.$qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            foreach($listData as $row)
            {
                $newRow = new \stdClass();
                $newRow->id = $row['ID'];
                $newRow->orgnum = $row['CisloOrg'];
                $newRow->parentid = $row['NadrizenaOrg'];
                $newRow->name = $row['Nazev'];
                $newRow->name2 = $row['DruhyNazev'];
                $newRow->email = '';
                $newRow->phone = '';
                $newRow->contact = $row['Kontakt'];
                $newRow->web = '';
                $newRow->status = $row['Stav'];
                $result->rows[] = $newRow;
            }

            //Construct response
	        $app['monolog']->info('Response: data:'.json_encode($result));
            return $app->json($result);
        });
        
        // Get detail of client
        $controllers->get('/clients/{id}', function (Application $app, $id) 
        {
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
            $app['monolog']->info('DB Select client detail :'.$qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            foreach($listData as $row)
            {
                $newRow = new \stdClass();
                $newRow->id = $row['ID'];
                $newRow->orgnum = $row['CisloOrg'];
                $newRow->parentid = $row['NadrizenaOrg'];
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
                $newRow->status = $row['Stav'];
                $result = $newRow;
            }

            //Construct response
	        $app['monolog']->info('Response: data:'.json_encode($result));
            return $app->json($result);
        });

        // Create new client
        $controllers->post('/clients', function (Application $app) 
        {
            $newClientId = null;
            $request = $app['request_stack']->getCurrentRequest();

            // Check data
            $sqlParams = Array();
            // Required fields
            if(
                $request->get('orgnum') != null && is_numeric($request->get('orgnum')) &&
                $request->get('name') != null && strlen($request->get('name')) <= 100 &&
                $request->get('name2') != null && strlen($request->get('name2')) <= 100 &&
                $request->get('street') != null && strlen($request->get('street')) <= 100 &&
                $request->get('streetorinumber') != null && strlen($request->get('streetorinumber')) <= 15 &&
                $request->get('streetdesnumber') != null && strlen($request->get('streetdesnumber')) <= 15 &&
                $request->get('city') != null && strlen($request->get('city')) <= 100 &&
                is_numeric($request->get('status'))
                )
            {
                $sqlParams['CisloOrg'] = $request->get('orgnum');
                $sqlParams['Nazev'] = $request->get('name');
                $sqlParams['DruhyNazev'] = $request->get('name2');
                $sqlParams['Ulice'] = $request->get('street');
                $sqlParams['OrCislo'] = $request->get('streetorinumber');
                $sqlParams['PopCislo'] = $request->get('streetdesnumber');
                $sqlParams['Misto'] = $request->get('city');
                $sqlParams['Stav'] = $request->get('status');
            }
            else
                $app->abort(404, "Invalid request.");

            // Optional fields
            if(
                ($request->get('parentid') == null || is_numeric($request->get('parentid'))) &&
                ($request->get('zip') == null || strlen($request->get('zip')) <= 10) &&
                ($request->get('contact') == null || strlen($request->get('contact')) <= 40) &&
                ($request->get('ic') == null || strlen($request->get('ic')) <= 20) &&
                ($request->get('dic') == null || strlen($request->get('dic')) <= 15)
            )
            {
                $sqlParams['NadrizenaOrg'] = $request->get('parentid');
                $sqlParams['PSC'] = $request->get('zip');
                $sqlParams['Kontakt'] = $request->get('contact');
                $sqlParams['ICO'] = $request->get('ic');
                $sqlParams['DIC'] = $request->get('dic');
            }
            else
                $app->abort(404, "Invalid request.");

            //TODO: Check if client already exists

            $app['db']->insert('TabCisOrg', $sqlParams);
            $newClientId = $app['db']->lastInsertId();

            //TODO: result 201 + Location header = /customers/<id>
            $response =  new Response('Client created with id '.$newClientId, 201);
            $response->headers->set('Location', 'clients/'.$newClientId);
            return $response;
        });

        // Update client
        $controllers->put('/clients', function (Application $app, Request $request) 
        {
            // Check data
            $sqlParams = Array();

            // Required fields
            if(
                $request->get('orgnum') != null && is_numeric($request->get('orgnum')) &&
                $request->get('name') != null && strlen($request->get('name')) <= 100 &&
                $request->get('name2') != null && strlen($request->get('name2')) <= 100 &&
                $request->get('street') != null && strlen($request->get('street')) <= 100 &&
                $request->get('streetorinumber') != null && strlen($request->get('streetorinumber')) <= 15 &&
                $request->get('streetdesnumber') != null && strlen($request->get('streetdesnumber')) <= 15 &&
                $request->get('city') != null && strlen($request->get('city')) <= 100 &&
                $request->get('status') != null && is_numeric($request->get('status'))
                )
            {
                $sqlParams['CisloOrg'] = $request->get('orgnum');
                $sqlParams['Nazev'] = $request->get('name');
                $sqlParams['DruhyNazev'] = $request->get('name2');
                $sqlParams['Ulice'] = $request->get('street');
                $sqlParams['OrCislo'] = $request->get('streetorinumber');
                $sqlParams['PopCislo'] = $request->get('streetdesnumber');
                $sqlParams['Misto'] = $request->get('city');
                $sqlParams['Stav'] = $request->get('status');
            }
            else
                $app->abort(404, "Invalid request.");

            // Optional fields
            if(
                ($request->get('parentid') == null || is_numeric($request->get('parentid'))) &&
                ($request->get('zip') == null || strlen($request->get('zip')) <= 10) &&
                ($request->get('contact') == null || strlen($request->get('contact')) <= 40) &&
                ($request->get('ic') == null || strlen($request->get('ic')) <= 20) &&
                ($request->get('dic') == null || strlen($request->get('dic')) <= 15)
            )
            {
                $sqlParams['NadrizenaOrg'] = $request->get('parentid');
                $sqlParams['PSC'] = $request->get('zip');
                $sqlParams['Kontakt'] = $request->get('contact');
                $sqlParams['ICO'] = $request->get('ic');
                $sqlParams['DIC'] = $request->get('dic');
            }
            else
                $app->abort(404, "Invalid request.");

            $app['db']->insert('TabCisOrg', $sqlParams);
            $app['db']->commit();

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
            $app['monolog']->info('DB Select product whole list rows - TOTAL COUNT:'.$qb->getSql());
            $totalRows = $app['db']->fetchAll($qb->getSql(), $sqlParams);
            $result->totalrows = $totalRows[0]['totalcount'];

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
            $app['monolog']->info('DB Select client list :'.$qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            foreach($listData as $row)
            {
                $newRow = new \stdClass();
                $newRow->id = $row['ID'];
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
	        $app['monolog']->info('Response: data:'.json_encode($result));
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
            $app['monolog']->info('DB Select product detail :'.$qb->getSql());
            $listData = $app['db']->fetchAll($qb->getSql(), $sqlParams);

            foreach($listData as $row)
            {
                $newRow = new \stdClass();
                $newRow->id = $row['ID'];
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
	        $app['monolog']->info('Response: data:'.json_encode($result));
            return $app->json($result);
        });
        return $controllers;
    }
}
?>