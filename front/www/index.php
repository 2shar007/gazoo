<?php

set_include_path(implode(';' , array_merge(array(__DIR__ . '/../vendor/'), explode(':', get_include_path()))));

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
date_default_timezone_set('Europe/Berlin');

$loader = require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = true;

// Register Session
$app->register(new Silex\Provider\SessionServiceProvider(), array(
    'session.storage.save_path' => __DIR__ . '/../tmp/'
));

// Register Twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../views',
));

$app['twig'] = $app->share($app->extend('twig', function($twig, $app)
{
    $twig->addGlobal('sitename', 'Kalendy');
    $twig->addGlobal('current_nav', $app['request']->get('_route') );
    $user = $app['session']->get('user');
    $twig->addGlobal('user', $user);
    $following = array();
    if ($user)
    {
        $res = $app['db']->fetchAll('
            SELECT s.* FROM subject AS s
            INNER JOIN user_subject AS us ON us.id_subject = s.id AND us.id_user = ?', array((int)$user['id']));
        if ($res)
        {
            foreach ($res as $r)
                $following[$r['id']] = $r;
        }
    }
    $twig->addGlobal('following', $following);
    $navigation = array('calendars' => 'Show calendars');
    if ($user)
        $navigation['planning'] = 'My planning';
    $twig->addGlobal('navigation', $navigation);
    return $twig;
}));

// Register URL Generator
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

// Register Doctrine
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => include __DIR__ . '/../config/db.php',
));

$app->get('/', function() use ($app)
{
    $calendars = $app['db']->fetchAll('SELECT * FROM subject');
    return $app['twig']->render('index.twig', array('calendars' => $calendars));
})->bind('home');

/*
 * Calendars
 */
$calendar = $app['controllers_factory'];
$calendar->get('/', function () use ($app) {
    $calendars = $app['db']->fetchAll('SELECT * FROM subject ORDER BY category ASC');
    return $app['twig']->render('calendar.index.twig', array('calendars' => $calendars));
})->bind('calendars');

$calendar->get('/{id}', function ($id) use ($app) {
    $calendar = $app['db']->fetchAssoc('SELECT * FROM subject WHERE id = ?', array((int)$id));
    $events = $app['db']->fetchAll('
        SELECT * FROM event AS e
        INNER JOIN subject_event AS se ON se.id_event = e.id AND se.id_subject = ?
        ORDER BY e.start ASC', array((int)$id));
    return $app['twig']->render('calendar.view.twig', array('calendar' => $calendar, 'events' => $events));
})->bind('calendar');

function getAuthSubUrl()
{
  //var_dump($_SERVER);
  $next = 'http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REDIRECT_URL"];
  $scope = 'https://www.google.com/calendar/feeds/';
  $secure = false;
  $session = true;
  return Zend_Gdata_AuthSub::getAuthSubTokenUri($next, $scope, $secure,
      $session);
}
$authSubUrl = getAuthSubUrl();

$client = null;

if (!$app['session']->get('SESSIONTOKEN'))
{
	if (ISSET($_GET['token']))
	{
		$app['session']->set('SESSIONTOKEN', Zend_Gdata_AuthSub::getAuthSubSessionToken($_GET['token']));
		$client = Zend_Gdata_AuthSub::getHttpClient($app['session']->get('SESSIONTOKEN'));
		//outputCalendarList($client);
	}
	else
	{
		echo "<a href=\"$authSubUrl\">login to your Google account</a>";
	}
}
else
{
	 $client = Zend_Gdata_AuthSub::getHttpClient($app['session']->get('SESSIONTOKEN'));
	 //outputCalendarList($client);
}

function outputCalendarList($client)
{
  $gdataCal = new Zend_Gdata_Calendar($client);
  $calFeed = $gdataCal->getCalendarListFeed();
  echo '<h1>' . $calFeed->title->text . '</h1>';
  echo '<ul>';
  foreach ($calFeed as $calendar) {
    echo '<li>' . $calendar->title->text . '</li>';
  }
  echo '</ul>';
} 

function createEvent ($client, $title = 'Tennis with Beth',
    $desc='Meet for a quick lesson', $where = 'On the courts',
    $startDate = '2008-01-20', $startTime = '10:00',
    $endDate = '2008-01-20', $endTime = '11:00', $tzOffset = '+01')
{
  $gdataCal = new Zend_Gdata_Calendar($client);
  $newEvent = $gdataCal->newEventEntry();

  $newEvent->title = $gdataCal->newTitle($title);
  $newEvent->where = array($gdataCal->newWhere($where));
  $newEvent->content = $gdataCal->newContent("$desc");

  $when = $gdataCal->newWhen();
  $when->startTime = "{$startDate}T{$startTime}.000{$tzOffset}:00";
  $when->endTime = "{$endDate}T{$endTime}.000{$tzOffset}:00";
  $newEvent->when = array($when);

  // Upload the event to the calendar server
  // A copy of the event as it is recorded on the server is returned
  $createdEvent = $gdataCal->insertEvent($newEvent);
  return $createdEvent->id->text;
}

function outputCalendarByFullTextQuery($client, $fullTextQuery, $startDate)
{
  $gdataCal = new Zend_Gdata_Calendar($client);
  $query = $gdataCal->newEventQuery();
  $query->setUser('default');
  $query->setVisibility('private');
  $query->setProjection('full');
  $query->setOrderby('starttime');
  $query->setStartMin($startDate);
  $query->setQuery($fullTextQuery);
  $eventFeed = $gdataCal->getCalendarEventFeed($query);
  
  return $eventFeed;
}

function CreateCalendar($client)
{
//Standard creation of the HTTP client
 $gdataCal = new Zend_Gdata_Calendar($client);

 //Get list of existing calendars
 $calFeed = $gdataCal->getCalendarListFeed();

 //Set this to true by default, only gets set to false if calendar is found
 $noAppCal = true;

 //Loop through calendars and check name which is ->title->text
 foreach ($calFeed as $calendar) {
  if($calendar -> title -> text == "App Calendar") {
   $noAppCal = false;
  }
 }

 //If name not found, create the calendar
 if($noAppCal) {

  // I actually had to guess this method based on Google API's "magic" factory
  $appCal = $gdataCal -> newListEntry();
  // I only set the title, other options like color are available.
  $appCal -> title = $gdataCal-> newTitle("App Calendar"); 

  //This is the right URL to post to for new calendars...
  //Notice that the user's info is nowhere in there
  $own_cal = "http://www.google.com/calendar/feeds/default/owncalendars/full";

  //And here's the payoff. 
  //Use the insertEvent method, set the second optional var to the right URL
  $gdataCal->insertEvent($appCal, $own_cal);
 }
 }

$calendar->get('/{id}/{action}', function ($id, $action) use ($app)
{
    $errno = 1;
    $user = $app['session']->get('user');
    $extraData = array();

    if ($action == 'follow')
    {
        if ($id && $user) {
            $errno = !$app['db']->executeUpdate('INSERT INTO user_subject (id_user, id_subject) VALUES (?, ?)', array((int)$user['id'], (int)$id));
            $content = $app['twig']->render('follow.button.twig', array('id' => $id, 'is_following' => true));
            $extraData = array('id' => $id);
        }
    } else if ($action == 'unfollow')
	{
        if ($id && $user) {
            $errno = !$app['db']->executeUpdate('DELETE FROM user_subject  WHERE id_user = ? AND id_subject = ?', array((int)$user['id'], (int)$id));
            $content = $app['twig']->render('follow.button.twig', array('id' => $id, 'is_following' => false));
            $extraData = array('id' => $id);
        }
	}
    return new Response(json_encode($return = array('errno' => $errno, 'content' => $content) + $extraData), 200, array('Content-Type' => 'application/json'));
})->bind('calendar.action');

$app->mount('/calendar', $calendar);

/*
 * Users
 */
$user = $app['controllers_factory'];
$user->post('/login', function (Request $request) use ($app) {
    $exists = false;
    $error = '';

    $mail = $request->get('mail');
    $pwd = $request->get('pwd');
    if ($mail && $pwd)
        $user = $app['db']->fetchAssoc('SELECT * FROM user WHERE mail = ? AND password = ?', array((string)$mail, sha1((string)$pwd)));
    if ($user)
    {
        $app['session']->set('user', $user);
        $return = array('errno' => 0, 'content' => $app['twig']->render('user.logged.twig', array('user' => $user)));
    }
    else
        $return = array('errno' => 1, 'error' => 'Check your credentials');

    return new Response(json_encode($return), 200, array('Content-Type' => 'application/json'));
})->bind('login');

$user->get('/login', function () use($app) {
    return $app['twig']->render('login.form.twig');
});

$user->get('/logout', function () use ($app) {
    $app['session']->set('user', null);
    return $app->redirect($app['url_generator']->generate('home'));
})->bind('logout');

$app->mount('/user', $user);

/*
 * Planning
 */
$app->get('/planning', function () use($app) {
    $user = $app['session']->get('user');
    if (!$user)
        return $app->redirect($app['url_generator']->generate('home'));

    $sql = '
        SELECT e.*, s.name AS subject_name, s.id AS subject_id, s.alias AS subject_alias FROM event AS e
        INNER JOIN subject_event AS se ON e.id = se.id_event
        INNER JOIN subject AS s ON s.id = se.id_subject
        INNER JOIN user_subject AS us ON us.id_subject = se.id_subject
        WHERE us.id_user = ? AND DATEDIFF(e.start,CURDATE())>0 
        ORDER BY e.start ASC';
    $events = $app['db']->fetchAll($sql, array($user['id']));
    return $app['twig']->render('planning.twig', array('events' => $events));
})->bind('planning');

/*
 * Search
 */
$app->post('/search', function (Request $request) use ($app) {
    return $app->redirect($app['url_generator']->generate('search', array('q' => $request->get('q'))));
})->bind('search.post');

$app->get('/search/{q}', function ($q) use ($app) {
   $sqlCommon = '
        FROM event AS e
        LEFT JOIN event_tag AS et ON et.id_event = e.id
        LEFT JOIN tag AS tevent ON tevent.id = et.id_tag
        INNER JOIN subject_event AS se ON se.id_event = e.id
        INNER JOIN subject AS s ON s.id = se.id_subject
        WHERE ';

    $params = array();
    $cpt = 0;
    foreach (explode(' ', $q) as $part) {
        $tag = ':search' . $cpt;
        $sqlCommonParts[] = "e.name LIKE $tag OR tevent.name LIKE $tag OR tevent.description LIKE $tag OR s.name LIKE $tag OR s.description LIKE $tag OR s.category LIKE $tag";
        $params[$tag] = '%'.str_replace(array('%', '_'), array('\%', '\_'), $part).'%';
        $cpt++;
    }
    $dateFilter = "DATEDIFF( `start` , CURDATE( ) ) >0 AND ";
    $sqlCommon .= $dateFilter;
    $sqlCommon .= '(' . implode(') AND (', $sqlCommonParts) . ')';
    $sqlCount = 'SELECT COUNT(DISTINCT s.id)' . $sqlCommon;
    $sqlData = 'SELECT e.*, s.id AS subject_id, s.name AS subject_name, s.description AS subject_description, s.category AS subject_category' . $sqlCommon . ' GROUP BY s.id ORDER BY e.start ASC';

    $total_result = $app['db']->fetchColumn($sqlCount, $params);
    $calendars = $app['db']->fetchAll($sqlData, $params);
    return $app['twig']->render('search.twig', array('calendars' => $calendars, 'total_result' => $total_result, 'search_query' => $q));
})->bind('search');

$app->get('/push/{id}', function ($id) use ($app)
{
	$client = Zend_Gdata_AuthSub::getHttpClient($app['session']->get('SESSIONTOKEN'));
	
	if ($client != null)
		{
			//CreateCalendar($client);
			$events = $app['db']->fetchAll('SELECT name, start, end, description FROM event e
											JOIN subject_event se on e.id = se.id_event
											WHERE se.id_subject = ?', array((int)$id));
			foreach ($events as $r)
			{
				$start = explode(" ", $r['start']);
				$end = $start;
				if ($r['end'] != null && $r['end'] != '0000-00-00 00:00:00')
				{
					$end = explode(" ", $r['end']);
				}
				
				createEvent($client
							, $r['name']
							, $r['description']
							, 'here'
							, $start[0]
							, $start[1]
							, $end[0]
							, $end[1]);
			}
		}
		$ret = null;
	return new Response(json_encode($ret), 200, array('Content-Type' => 'application/json'));;
});

$app->get('/unpush/{id}', function ($id) use ($app)
{
	$client = Zend_Gdata_AuthSub::getHttpClient($app['session']->get('SESSIONTOKEN'));
	
	if ($client != null)
		{
			$events = $app['db']->fetchAll('SELECT name, start, end, description FROM event e
											JOIN subject_event se on e.id = se.id_event
											WHERE se.id_subject = ?', array((int)$id));
			foreach ($events as $r)
			{
				$start = explode(" ", $r['start']);
				$eventfeed = outputCalendarByFullTextQuery($client
							, $r['name']
							, $start[0]);
				foreach($eventfeed as $ev)
				{
					$ev->delete();
				}
			}
		}
		$ret = null;
	return new Response(json_encode($ret), 200, array('Content-Type' => 'application/json'));;
});

$app->run();
