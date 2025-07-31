<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Index::index');
$routes->get('/login', 'Login::index');
$routes->get('/admin/teacher-registration', 'AdminLogin::teacherRegistrationPage');
$routes->get('/signup', 'Signup::index');
$routes->get('/home', 'Home::index');
$routes->get('/admin', 'AdminLogin::index');
$routes->get('/admin/dashboard', 'AdminDashboard::index');
$routes->get('/logout', 'Logout::index');
$routes->get('/admin/students', 'AdminStudents::index');
$routes->get('/admin/students/new', 'AdminStudents::newPage');
$routes->get('/admin/students/edit/(:any)', 'AdminStudents::editPage/$1');
$routes->get('/admin/students/reports/(:any)', 'AdminStudents::reportsPage/$1');

$routes->get('/admin/teachers', 'AdminTeachers::index');
$routes->get('/admin/teachers/new', 'AdminTeachers::newPage');
$routes->get('/admin/teachers/edit/(:any)', 'AdminTeachers::editPage/$1');

$routes->get('/admin/grades', 'AdminGrades::index');
$routes->get('/admin/grades/setRoute/(:any)', 'AdminGrades::setRoutePage/$1');

$routes->get('/admin/classes', 'AdminClasses::index');
$routes->get('/admin/classes/(:any)/students', 'AdminClasses::studentsPage/$1');
$routes->get('/admin/classes/reports/(:any)', 'AdminClasses::reportsPage/$1');

$routes->get('/admin/topics', 'AdminTopics::index');
$routes->get('/admin/topics/new', 'AdminTopics::newPage');
$routes->get('/admin/topics/edit/(:any)', 'AdminTopics::editPage/$1');
$routes->get('/admin/topics/(:any)/questions', 'AdminTopics::questionsPage/$1');

$routes->get('/admin/questions', 'AdminQuestions::index');
$routes->get('/admin/questions/new', 'AdminQuestions::newPage');
$routes->get('/admin/questions/edit/(:any)', 'AdminQuestions::editPage/$1');

$routes->get('/admin/onboarding', 'AdminOnboarding::index');

$routes->get('/evaluation', 'Home::evaluationPage');

$routes->get('/report-questions', 'Reports::reportMissingQuestions');

$routes->get('/page-selection', 'Home::pageSelection');

$routes->get('/history', 'Home::historyPage');

$routes->post('/evaluate-session', 'Home::evaluateSession');

$routes->post('/admin/login', 'AdminLogin::login');
$routes->post('/admin/grades/saveNew', 'AdminGrades::saveNew');
$routes->post('/admin/grades/update', 'AdminGrades::update');
$routes->post('/admin/grades/delete', 'AdminGrades::delete');
$routes->post('/admin/grades/save-settings', 'AdminGrades::saveSettings');
$routes->post('/admin/students/saveNew', 'AdminStudents::saveNew');
$routes->post('/admin/students/update', 'AdminStudents::update');
$routes->post('/admin/students/delete', 'AdminStudents::delete');
$routes->post('/admin/students/send-missed-questions-email', 'AdminStudents::sendMissedQuestionsEmail');
$routes->post('/admin/topics/saveNew', 'AdminTopics::saveNew');
$routes->post('/admin/topics/update', 'AdminTopics::update');
$routes->post('/admin/topics/delete', 'AdminTopics::delete');
$routes->post('/admin/grades/updateRoute', 'AdminGrades::updateRoute');
$routes->post('/admin/questions/saveNew', 'AdminQuestions::saveNew');
$routes->post('/admin/questions/update', 'AdminQuestions::update');
$routes->post('/admin/questions/delete', 'AdminQuestions::delete');
$routes->post('/admin/questions/import-csv', 'AdminQuestions::importCsv');
$routes->post('/admin/topics/remove-question', 'AdminTopics::removeQuestion');
$routes->post('/admin/questions/updateTopics', 'AdminQuestions::updateTopics');
$routes->post('/submit-answer', 'Home::submitAnswer');
$routes->post('/admin/classes/saveNew', 'AdminClasses::saveNew');
$routes->post('/admin/classes/delete', 'AdminClasses::delete');
$routes->post('/admin/classes/update', 'AdminClasses::update');
$routes->post('/admin/dashboard/viewReport', 'AdminDashboard::viewReport');
$routes->post('/class/send-email', 'AdminClasses::sendEmailToParents');
$routes->post('/admin/topics/create-from-wizard', 'AdminTopics::createTopicFromWizard');

$routes->post('/admin/teachers/saveNew', 'AdminTeachers::saveNew');
$routes->post('/admin/teachers/update', 'AdminTeachers::update');
$routes->post('/admin/teachers/delete', 'AdminTeachers::delete');
$routes->post('/admin/teachers/register', 'AdminTeachers::register');

$routes->post('/login', 'Login::login');
$routes->post('/signup-user', 'Signup::signupUser');