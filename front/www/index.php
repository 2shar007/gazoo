<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
date_default_timezone_set('Europe/Berlin');

require_once __DIR__ . '/../vendor/autoload.php';

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
        ORDER BY e.start DESC', array((int)$id));
    return $app['twig']->render('calendar.view.twig', array('calendar' => $calendar, 'events' => $events));
})->bind('calendar');

$calendar->get('/{id}/{action}', function ($id, $action) use ($app) {
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
    } else if ($action == 'unfollow') {
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
        WHERE us.id_user = ?
        ORDER BY e.start DESC';
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

$app->run();
