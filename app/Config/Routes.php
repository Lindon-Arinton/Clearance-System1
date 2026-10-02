<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// ---------------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------------
$routes->group('', ['filter' => 'guest'], static function ($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::attempt');
    $routes->get('forgot-password', 'Auth::forgot');
    $routes->get('signup', 'Auth::signup');
    $routes->post('signup', 'Auth::register');
    $routes->get('signup/check', 'Auth::checkAvailability');
});
$routes->post('logout', 'Auth::logout');

// ---------------------------------------------------------------------
// Shared (any signed-in user)
// ---------------------------------------------------------------------
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('notifications', 'Notifications::index');
    $routes->get('notifications/(:num)/open', 'Notifications::open/$1');
    $routes->post('notifications/read-all', 'Notifications::readAll');

    $routes->get('profile', 'Profile::index');
    $routes->post('profile/password', 'Profile::password');
    $routes->post('profile/signature', 'Profile::signature', ['filter' => 'role:teacher']);

    // Private files — authorization is checked per record in the controller.
    $routes->get('files/tuition-receipt/(:num)', 'Files::tuitionReceipt/$1');
    $routes->get('files/reenrollment-receipt/(:num)', 'Files::reenrollmentReceipt/$1');
    $routes->get('files/signature/(:num)', 'Files::signature/$1');
    $routes->get('files/approval-signature/(:num)', 'Files::approvalSignature/$1');
});

// ---------------------------------------------------------------------
// Student workspace
// ---------------------------------------------------------------------
$routes->group('student', ['filter' => ['auth', 'role:student'], 'namespace' => 'App\Controllers\Student'], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->post('clearance/start', 'Clearance::start');
    $routes->get('receipt', 'Clearance::receipt');
    $routes->post('receipt', 'Clearance::uploadReceipt');
    $routes->get('card', 'Clearance::card');
    $routes->get('subjects', 'Clearance::subjects');
    $routes->get('inc', 'Inc::index');
    $routes->post('inc/(:num)/report', 'Inc::report/$1');
    $routes->get('reenrollment', 'Reenrollment::index');
    $routes->post('reenrollment/(:num)/upload', 'Reenrollment::upload/$1');
    $routes->get('history', 'History::index');
    $routes->get('history/(:num)', 'History::show/$1');
});

// ---------------------------------------------------------------------
// Teacher workspace
// ---------------------------------------------------------------------
$routes->group('teacher', ['filter' => ['auth', 'role:teacher'], 'namespace' => 'App\Controllers\Teacher'], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('subjects', 'Subjects::index');
    $routes->get('subjects/(:num)', 'Clearance::offering/$1');
    $routes->get('students', 'Students::index');
    $routes->get('clearance', 'Clearance::index');
    $routes->post('clearance/(:num)/decision', 'Clearance::decide/$1');
    $routes->post('clearance/(:num)/sign', 'Clearance::sign/$1');
    $routes->post('clearance/(:num)/requirements', 'Clearance::addRequirement/$1');
    $routes->get('inc', 'Inc::index');
    $routes->post('inc/(:num)/verify', 'Inc::verify/$1');
    $routes->post('inc/(:num)/return', 'Inc::returnReport/$1');
    $routes->post('inc/(:num)/delete', 'Inc::delete/$1');
});

// ---------------------------------------------------------------------
// Admin / registrar workspace
// ---------------------------------------------------------------------
$routes->group('admin', ['filter' => ['auth', 'role:admin'], 'namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');

    $routes->get('students', 'Students::index');
    $routes->get('students/new', 'Students::create');
    $routes->post('students', 'Students::store');
    $routes->get('students/(:num)', 'Students::show/$1');
    $routes->get('students/(:num)/edit', 'Students::edit/$1');
    $routes->post('students/(:num)', 'Students::update/$1');
    $routes->post('students/(:num)/reset-password', 'Students::resetPassword/$1');
    $routes->post('students/(:num)/approve', 'Students::approve/$1');
    $routes->post('students/(:num)/reject', 'Students::reject/$1');
    $routes->post('students/(:num)/enroll', 'Students::enroll/$1');
    $routes->post('enrollments/(:num)/drop', 'Students::dropEnrollment/$1');

    $routes->get('teachers', 'Teachers::index');
    $routes->get('teachers/new', 'Teachers::create');
    $routes->post('teachers', 'Teachers::store');
    $routes->get('teachers/(:num)', 'Teachers::show/$1');
    $routes->get('teachers/(:num)/edit', 'Teachers::edit/$1');
    $routes->post('teachers/(:num)', 'Teachers::update/$1');
    $routes->post('teachers/(:num)/reset-password', 'Teachers::resetPassword/$1');

    $routes->get('subjects', 'Subjects::index');
    $routes->post('subjects', 'Subjects::store');
    $routes->post('subjects/(:num)', 'Subjects::update/$1');
    $routes->get('offerings', 'Offerings::index');
    $routes->post('offerings', 'Offerings::store');
    $routes->post('offerings/(:num)', 'Offerings::update/$1');
    $routes->post('offerings/(:num)/delete', 'Offerings::delete/$1');

    $routes->get('terms', 'Terms::index');
    $routes->post('terms/years', 'Terms::storeYear');
    $routes->post('terms', 'Terms::store');
    $routes->post('terms/(:num)', 'Terms::update/$1');
    $routes->post('terms/(:num)/current', 'Terms::makeCurrent/$1');

    $routes->get('receipts', 'Receipts::index');
    $routes->post('receipts/(:num)/approve', 'Receipts::approve/$1');
    $routes->post('receipts/(:num)/reupload', 'Receipts::reupload/$1');

    $routes->get('reenrollment', 'Reenrollment::index');
    $routes->post('reenrollment/(:num)/approve', 'Reenrollment::approve/$1');
    $routes->post('reenrollment/(:num)/reupload', 'Reenrollment::reupload/$1');

    $routes->get('clearances', 'Clearances::index');
    $routes->get('clearances/(:num)', 'Clearances::show/$1');

    $routes->get('reports', 'Reports::index');
    $routes->get('reports/export/(:segment)', 'Reports::export/$1');

    $routes->get('settings', 'Settings::index');
    $routes->post('settings', 'Settings::update');
    $routes->post('settings/programs', 'Settings::storeProgram');
    $routes->post('settings/programs/(:num)', 'Settings::updateProgram/$1');
    $routes->post('settings/admins', 'Settings::storeAdmin');
});
