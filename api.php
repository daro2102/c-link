<?php

require_once('config.php');
require_once('DatabaseMySql.php');

DB::connect(DB_HOST, DB_PORT, DB_NAME, DB_USERNAME, DB_PASSWORD);

session_start();


define('OK_RESULT', 0);

define('INVALID_DATA_ERROR', 1);
define('INCORRECT_DATA_ERROR', 2);
define('INTERNAL_DATA_ERROR', 3);

define('NO_LOGGED_ERROR', 100);



//result(OK_RESULT, $_POST);


// wstępna walidacja przesłanych danych
if( !isset($_POST['device']) || strlen($_POST['device']) == 0 || !isset($_POST['mode']) || strlen($_POST['mode']) == 0 )
	result(INVALID_DATA_ERROR);




$device = $_POST['device'];
$mode = $_POST['mode'];


// Logowanie użytkownika
if($mode == 'login') {
	
	// walidacja danych potrzebych do logowania
	if( !isset($_POST['login']) || !isset($_POST['password']) )
		result(INVALID_DATA_ERROR);
	
	
	// addslashes dla zabezpieczenia przed 'sql injection'
	$login = addslashes($_POST['login']);
	// md5 bo tak są przechowywane w bazie hasła, przy okazji takze chroni przed 'sqj injection'
	$password = md5($_POST['password']);
	
	$rowClient = DB::query("SELECT * FROM client_list WHERE cl_login='".$login."' AND cl_password='".$password."'", $resultClient);
	
	// nie znaleziono takiego uzytkownika, więc 'niepoprawne dane'
	if($rowClient == 0)
		result(INCORRECT_DATA_ERROR);
	
	
	// zapamietywanie id urzadzenia, dla sprawdzania logowania
	$_SESSION['device'] = $device;
	
	// pobranie danych aut użytkownika
	$rowCar = DB::query("SELECT * FROM car_client_list WHERE ccl_cl_id='". $resultClient[0]['cl_id'] ."'", $resultCar);
	
	$clientCars = array();
	
	for($i=0;$i<$rowCar;$i++) {
		
		$clientCars[] = array(
			'id' => $resultCar[$i]['ccl_id'],
			'mark' => $resultCar[$i]['ccl_mark'],
			'model' => $resultCar[$i]['ccl_model'],
			'year' => $resultCar[$i]['ccl_year'],
			'nextinspection' => $resultCar[$i]['ccl_nextinspection']
		);
		
	}
	
	result(OK_RESULT, array(
		'id' => $resultClient[0]['cl_id'],
		'name' => $resultClient[0]['cl_name'],
		'surname' => $resultClient[0]['cl_surname'],
		'cars' => $clientCars
	));
	
} elseif($mode == 'getCarData') {
	
	// sprawdzanie czy uzytkownik jest zalogowany
	if( isset($_SESSION['device']) && $_SESSION['device'] != $device )
		result(NO_LOGGED_ERROR);
	
	// walidacja danych
	if( !isset($_POST['car_id']) )
		result(INVALID_DATA_ERROR);
	
	$carId = intval( $_POST['car_id'] );
	
	$rowCar = DB::query("SELECT * FROM car_client_list WHERE ccl_id='".$carId."'", $resultCar);
	
	// nie znaleziono takiego samochodu, więc 'niepoprawne dane'
	if($rowCar == 0)
		result(INCORRECT_DATA_ERROR);
	
	// pobieranie nadchodzących wizyt
	$rowVisitFuture = DB::query("SELECT * FROM visit_list WHERE vl_ccl_id='".$carId."' AND vl_date>CURRENT_DATE ORDER BY vl_date ASC", $resultVisitFuture);
	
	$carVisitFuture = array();
	
	for($i=0;$i<$rowVisitFuture;$i++) {
		
		$carVisitFuture[] = array(
			'id' => $resultVisitFuture[$i]['vl_id'],
			//'replacement_car' => $resultVisitFuture[$i]['vl_rl_id'],
			'date' => $resultVisitFuture[$i]['vl_date'],
			'confirmed' => $resultVisitFuture[$i]['vl_confirmed']
		);
		
	}
	
	
	
	// pobieranie historii wizyt
	$rowVisitPast = DB::query("SELECT * FROM visit_list WHERE vl_ccl_id='".$carId."' AND vl_date<=CURRENT_DATE ORDER BY vl_date DESC", $resultVisitPast);
	
	$carVisitPast = array();
	
	for($i=0;$i<$rowVisitPast;$i++) {
		
		$carVisitPast[] = array(
			'id' => $resultVisitPast[$i]['vl_id'],
			//'replacement_car' => $resultVisitPast[$i]['vl_rl_id'],
			'date' => $resultVisitPast[$i]['vl_date'],
			//'mileage' => $resultVisitPast[$i]['vl_mileage'],
			'description' => $resultVisitPast[$i]['vl_description']
		);
		
	}
	
	
	result(OK_RESULT, array(
		'id' => $resultCar[0]['ccl_id'],
		'mark' => $resultCar[0]['ccl_mark'],
		'model' => $resultCar[0]['ccl_model'],
		'year' => $resultCar[0]['ccl_year'],
		'nextinspection' => $resultCar[0]['ccl_nextinspection'],
		'visit_future' => $carVisitFuture,
		'visit_past' => $carVisitPast
	));
	
	
} elseif($mode == 'getVisitFutureData') {
	
	// sprawdzanie czy uzytkownik jest zalogowany
	if( isset($_SESSION['device']) && $_SESSION['device'] != $device )
		result(NO_LOGGED_ERROR);
	
	// walidacja danych
	if( !isset($_POST['visit_id']) )
		result(INVALID_DATA_ERROR);
	
	$visitId = intval( $_POST['visit_id'] );
	
	$rowVisit = DB::query("SELECT * FROM visit_list WHERE vl_id='".$visitId."'", $resultVisit);
	
	// nie znaleziono takiej wizyty, więc 'niepoprawne dane'
	if($rowVisit == 0)
		result(INCORRECT_DATA_ERROR);
	
	// pobieranie danych wizyty
	$replacementCar = '';
	
	// czy wybrane auto zastępcze
	if( $resultVisit[0]['vl_rl_id'] != '1' ) {
		
		$rowReplacementCar = DB::query("SELECT * FROM replacementcar_list WHERE rl_id='".$resultVisit[0]['vl_rl_id']."'", $resultReplacementCar);
		
		// nie znaleziono takiego samochodu zastępczego, więc 'niepoprawne dane'
		if($rowReplacementCar == 0)
			result(INCORRECT_DATA_ERROR);
		
		
		$replacementCar = array(
			'id' => $resultReplacementCar[0]['rl_id'],
			'mark' => $resultReplacementCar[0]['rl_mark'],
			'model' => $resultReplacementCar[0]['rl_model'],
		);
	
	}
	
	
	result(OK_RESULT, array(
		'id' => $resultVisit[0]['vl_id'],
		'date' => $resultVisit[0]['vl_date'],
		'confirmed' => $resultVisit[0]['vl_confirmed'],
		'replacement_car' => $replacementCar
	));
	
	
} elseif($mode == 'getVisitPastData') {
	
	// sprawdzanie czy uzytkownik jest zalogowany
	if( isset($_SESSION['device']) && $_SESSION['device'] != $device )
		result(NO_LOGGED_ERROR);
	
	// walidacja danych
	if( !isset($_POST['visit_id']) )
		result(INVALID_DATA_ERROR);
	
	$visitId = intval( $_POST['visit_id'] );
	
	$rowVisit = DB::query("SELECT * FROM visit_list WHERE vl_id='".$visitId."'", $resultVisit);
	
	// nie znaleziono takiej wizyty, więc 'niepoprawne dane'
	if($rowVisit == 0)
		result(INCORRECT_DATA_ERROR);
	
	// pobieranie danych wizyty
	$replacementCar = '';
	
	// czy wybrane auto zastępcze
	if( $resultVisit[0]['vl_rl_id'] != '1' ) {
		
		$rowReplacementCar = DB::query("SELECT * FROM replacementcar_list WHERE rl_id='".$resultVisit[0]['vl_rl_id']."'", $resultReplacementCar);
		
		// nie znaleziono takiego samochodu zastępczego, więc 'niepoprawne dane'
		if($rowReplacementCar == 0)
			result(INCORRECT_DATA_ERROR);
		
		
		$replacementCar = array(
			'id' => $resultReplacementCar[0]['rl_id'],
			'mark' => $resultReplacementCar[0]['rl_mark'],
			'model' => $resultReplacementCar[0]['rl_model'],
		);
	
	}
	
	
	result(OK_RESULT, array(
		'id' => $resultVisit[0]['vl_id'],
		'date' => $resultVisit[0]['vl_date'],
		'mileage' => $resultVisit[0]['vl_mileage'],
		'description' => $resultVisit[0]['vl_description'],
		'replacement_car' => $replacementCar
	));
	
	
} elseif($mode == 'getSetDefaultVisitData') {
	
	// sprawdzanie czy uzytkownik jest zalogowany
	if( isset($_SESSION['device']) && $_SESSION['device'] != $device )
		result(NO_LOGGED_ERROR);
	
	// walidacja danych
	if( !isset($_POST['car_id']) )
		result(INVALID_DATA_ERROR);
	
	$carId = intval( $_POST['car_id'] );
	
	$rowCar = DB::query("SELECT * FROM car_client_list WHERE ccl_id='".$carId."'", $resultCar);
	
	// nie znaleziono takiego samochodu, więc 'niepoprawne dane'
	if($rowCar == 0)
		result(INCORRECT_DATA_ERROR);
	
	// pobranie domyślnej daty wizyty
	$visitDate = $resultCar[0]['ccl_nextinspection'];
	
	// pobranie listy dostępnych samochodów zastępczych w danym dniu	
	$rowReplacementCar = DB::query("SELECT * FROM replacementcar_list rl, visit_list vl WHERE vl.vl_date<>'".$visitDate."' AND rl.rl_id=vl.vl_rl_id AND rl.rl_id<>1", $resultReplacementCar);
	
	$replacementCars = array();
	
	for($i=0;$i<$rowReplacementCar;$i++) {
		
		$replacementCars[] = array(
			'id' => $resultReplacementCar[0]['rl_id'],
			'mark' => $resultReplacementCar[0]['rl_mark'],
			'model' => $resultReplacementCar[0]['rl_model'],
		);
	
	}
	
	result(OK_RESULT, array(
		'date' => $visitDate,
		'replacement_cars' => $replacementCars
	));
	
	
} elseif($mode == 'getSetCustomVisitData') {
	
	// sprawdzanie czy uzytkownik jest zalogowany
	if( isset($_SESSION['device']) && $_SESSION['device'] != $device )
		result(NO_LOGGED_ERROR);
	
	// walidacja danych
	if( !isset($_POST['date']) )
		result(INVALID_DATA_ERROR);
	
	$visitDate = addslashes($_POST['date']);
	
	// pobranie listy dostępnych samochodów zastępczych w danym dniu	
	$rowReplacementCar = DB::query("SELECT * FROM replacementcar_list rl, visit_list vl WHERE vl.vl_date<>'".$visitDate."' AND rl.rl_id=vl.vl_rl_id AND rl.rl_id<>1", $resultReplacementCar);
	
	$replacementCars = array();
	
	for($i=0;$i<$rowReplacementCar;$i++) {
		
		$replacementCars[] = array(
			'id' => $resultReplacementCar[0]['rl_id'],
			'mark' => $resultReplacementCar[0]['rl_mark'],
			'model' => $resultReplacementCar[0]['rl_model'],
		);
	
	}
	
	result(OK_RESULT, array(
		'date' => $visitDate,
		'replacement_cars' => $replacementCars
	));
	
	
} elseif($mode == 'setFormVisitData') {
	
	// sprawdzanie czy uzytkownik jest zalogowany
	if( isset($_SESSION['device']) && $_SESSION['device'] != $device )
		result(NO_LOGGED_ERROR);
	
	// walidacja danych
	if( !isset($_POST['car_id']) || !isset($_POST['date']) || !isset($_POST['replacementcar_id']) )
		result(INVALID_DATA_ERROR);
	
	$carId = intval($_POST['car_id']);
	$visitDate = addslashes($_POST['date']);
	$replacementCarId = intval($_POST['replacementcar_id']);
	
	// upewnienie się czy dostępne samochód zastępczy, jeśli wybrany
	if($replacementCarId > 1) {
		
		$rowReplacementCar = DB::query("SELECT * FROM replacementcar_list rl, visit_list vl WHERE vl.vl_date=='".$visitDate."' AND rl.rl_id=vl.vl_rl_id AND rl.rl_id='".$replacementCarId."'", $resultReplacementCar);
		
		if($rowReplacementCar > 0)
			result(INTERNAL_DATA_ERROR);
		
	}
	
	// zapisanie w bazie danych wizyty
	DB::query("INSERT INTO visit_list (vl_ccl_id, vl_rl_id, vl_date) VALUES ('".$carId."', '".$replacementCarId."', '".$visitDate."')");
	
	result(OK_RESULT, array(
		'car_id' => $carId
	));
	
	
} else
	result(INVALID_DATA_ERROR);










// funkcja zwracają dane do aplikacji
function result($code, $data='') {
	
	echo json_encode(array( 
		'code' => $code,
		'data' => $data
	));
	die();
	
}


?>