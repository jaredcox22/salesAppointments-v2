<?php

if (isset($_SERVER['HTTP_ORIGIN'])) {
	// Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
	// you want to allow, and if so:
	header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if (array_key_exists('REQUEST_METHOD', $_SERVER) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
		// may also be using PUT, PATCH, HEAD etc
		header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
		header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

	exit(0);
}
if (isset($_GET['date'])) {
	$date = date("Y-m-d", strtotime($_GET['date']));
} else {
	$date = date("Y-m-d");
}
$nextDate = date('Y-m-d', strtotime($date . " +1 day"));

require_once(__DIR__ . "/shared.php");

// Create Appt Times
$range = range(7, 20);
foreach ($range as $index => $value) {
	$apptTimes[$value] = [];
}

//Can't set incompatible reps more than 1 day out
if (strtotime($date) < strtotime("+1 day") && filter_var($_GET['incompatibleRep'], FILTER_VALIDATE_BOOLEAN)) {
	return_no_availability($date);
	exit();
}

//Can't set inspections more than 1 day out
/*if (strtotime($date) > strtotime("+1 day") && $_GET['product1'] == 'Inspection'){
	return_no_availability($date);
	exit();
}*/

// Day of week array
$dow = [
	'0' => 'Sun',
	'1' => 'Mon',
	'2' => 'Tues',
	'3' => 'Wed',
	'4' => 'Thur',
	'5' => 'Fri',
	'6' => 'Sat'
];

$dow = $dow[date('w', strtotime($date))];
//

if (filter_var($_GET['fromWebsite'], FILTER_VALIDATE_BOOLEAN)) {
	$currentDay = date('D');
	$currentTime = date('G');
	$nextDay = date('Y-m-d', strtotime('+1 day'));

	//If request made after 4:00p or on Sunday, no availability for next day.
    if ((($currentTime >= 16) || ($currentDay == 'Sun')) && ($date == $nextDay)) {
		return_no_availability($date);
	} 
}

$numberofProducts = 0;
if (isset($_GET['product1']) && $_GET['product1'] != '' && $_GET['product1'] != 'undefined') {
	$setProduct1 = $_GET['product1'];
	$numberofProducts++;
	$products[] = ['name' => $setProduct1];
}
if (isset($_GET['product2']) && $_GET['product2'] != '' && $_GET['product2'] != 'undefined') {
	$setProduct2 = $_GET['product2'];
	$numberofProducts++;
	$products[] = ['name' => $setProduct2];
}
if (isset($_GET['product3']) && $_GET['product3'] != '' && $_GET['product3'] != 'undefined') {
	$setProduct3 = $_GET['product3'];
	$numberofProducts++;
	$products[] = ['name' => $setProduct3];
}

require_once("/home/shared/functions.php");
require_once("/home/shared/db.php");
require_once(__DIR__ . "/shared.php");


$api_endpoint = 'http://intserver-api';

$db = new db('salesDashboard');
$storedData = $db->query("SELECT `name`,`value`,`dateTime` as `updated` FROM salesDashboard.storedData ORDER BY updated DESC;");

// $db = new db('salesDashboard', 'intserver-productiondev'); //DEVENVITRONMENT
if (strtotime($date) >= strtotime('2023-09-06')) {
    $repAvailibility = $db->query("SELECT `lp_id`, `day`, `time` FROM salesDashboard.schedule WHERE `active` = '1' AND `day` = '$dow' AND `lp_id` != '1218'");
	$dayOfWeek = date('l', strtotime($date));
	if ($dayOfWeek === "Monday" && (strtotime($date) < strtotime('2023-10-16')) ) {
		$newEntries = array(
			array(
				'lp_id' => '1218',
				'day' => 'Mon',
				'time' => '12',
			),
			array(
				'lp_id' => '1218',
				'day' => 'Mon',
				'time' => '14:30',
			),
			array(
				'lp_id' => '1218',
				'day' => 'Mon',
				'time' => '17',
			),
			array(
				'lp_id' => '1218',
				'day' => 'Mon',
				'time' => '19:30',
			),
		);
		$repAvailibility = array_merge($repAvailibility, $newEntries);
	}
	if ($dayOfWeek === "Monday" && (strtotime($date) >= strtotime('2023-10-16')) ) {
		$newEntries = array(
			array(
				'lp_id' => '1218',
				'day' => 'Mon',
				'time' => '12',
			),
			array(
				'lp_id' => '1218',
				'day' => 'Mon',
				'time' => '14:30',
			),
			array(
				'lp_id' => '1218',
				'day' => 'Mon',
				'time' => '16:45',
			),
			array(
				'lp_id' => '1218',
				'day' => 'Mon',
				'time' => '19',
			),
		);
		$repAvailibility = array_merge($repAvailibility, $newEntries);
	}
	if ($dayOfWeek === "Tuesday") {
		$newEntries = array(
			array(
				'lp_id' => '1218',
				'day' => 'Tues',
				'time' => '12',
			),
			array(
				'lp_id' => '1218',
				'day' => 'Tues',
				'time' => '14:30',
			),
			array(
				'lp_id' => '1218',
				'day' => 'Tues',
				'time' => '17',
			),
		);
		$repAvailibility = array_merge($repAvailibility, $newEntries);
	}
	if ($dayOfWeek === "Wednesday") {
			$newEntries = array(
				array(
					'lp_id' => '1218',
					'day' => 'Wed',
					'time' => '9',
				),
				array(
					'lp_id' => '1218',
					'day' => 'Wed',
					'time' => '11:30',
				),
				array(
					'lp_id' => '1218',
					'day' => 'Wed',
					'time' => '14',
				),
				array(
					'lp_id' => '1218',
					'day' => 'Wed',
					'time' => '16:30',
				),
				array(
					'lp_id' => '1218',
					'day' => 'Wed',
					'time' => '19',
				),

			);
			$repAvailibility = array_merge($repAvailibility, $newEntries);
	}
	if ($dayOfWeek === "Thursday") {
		$newEntries = array(
			array(
				'lp_id' => '1218',
				'day' => 'Thurs',
				'time' => '9',
			),
			array(
				'lp_id' => '1218',
				'day' => 'Thurs',
				'time' => '11:30',
			),
			array(
				'lp_id' => '1218',
				'day' => 'Thurs',
				'time' => '14',
			),
			array(
				'lp_id' => '1218',
				'day' => 'Thurs',
				'time' => '16:30',
			),
			array(
				'lp_id' => '1218',
				'day' => 'Thurs',
				'time' => '19',
			),

		);
		$repAvailibility = array_merge($repAvailibility, $newEntries);
	}
	if ($dayOfWeek === "Saturday") {
		$newEntries = array(
			array(
				'lp_id' => '1218',
				'day' => 'Sat',
				'time' => '09:30',
			),
			array(
				'lp_id' => '1218',
				'day' => 'Sat',
				'time' => '12',
			),
		);
		$repAvailibility = array_merge($repAvailibility, $newEntries);
	}
} else {
    $repAvailibility = $db->query("SELECT `lp_id`, `day`, `time` FROM salesDashboard.schedule WHERE `active` = '1' AND `day` = '$dow'");
}

$currentYear = date('Y'); // Get the current year
$nextYear = date('Y', strtotime('+1 year')); // Get the next year

if (strtotime($_GET['date']) >= strtotime('October 27, ' . $currentYear) || strtotime($_GET['date']) <= strtotime('March 10, ' . $currentYear)) {
	$product1 = $_GET['product1'];
	$product2 = $_GET['product2'];
	$product3 = $_GET['product3'];
	if ($product1 == 'Test Prod' || $product2 == 'Test Prod' || $product3 == 'Test Prod') {
		foreach ($repAvailibility as $key => $slot) {
			if ($slot['time'] == 19) {
				unset($repAvailibility[$key]);
			}
		}
	}
	if (strpos(strtolower($product1), 'roof') !== false || strpos(strtolower($product2), 'roof') !== false || strpos(strtolower($product3), 'roof') !== false) {
		foreach ($repAvailibility as $key => $slot) {
			if ($slot['time'] == 19) {
				unset($repAvailibility[$key]);
			}
		}
	};
	if (strpos(strtolower($product1), 'sid') !== false || strpos(strtolower($product2), 'sid') !== false || strpos(strtolower($product3), 'sid') !== false) {
		foreach ($repAvailibility as $key => $slot) {
			if ($slot['time'] == 19) {
				unset($repAvailibility[$key]);
			}
		}
	};
}

$db = new db('salesDashboard');
$availibleProducts = $db->query("SELECT * FROM salesDashboard.products");

foreach ($products as $index => $product) {
	$durs = searchForId($product['name'], 'lp_productid', $availibleProducts);
	if (count($durs) > 0) {
		$products[$index]['duration'] = $durs[0]['apptDuration'];
	}
}

usort($products, function ($a, $b) {
	return $b['duration'] <=> $a['duration'];
});

$apptDuration = $products[0]['duration'] + 30;

foreach ($storedData as $row => $values) {
	$index = $values['name'];
	if (!isset($$index)) {
		$$index = json_decode(base64_decode($values['value']), true);
	}
}

if (isset($_GET['rep']) && $_GET['rep'] != '') {
	if (strpos($_GET['rep'], '~')) {
		$_GET['rep'] = explode('~', $_GET['rep'])[0];
	}
	foreach ($LPSalesmen as $index => $salesman) {
		$first = explode(',', $salesman['name'])[1];
		$last = explode(',', $salesman['name'])[0];
		if (trim($_GET['rep']) != trim("$first $last")) {
			unset($LPSalesmen[$index]);
		} elseif ($salesman['rep_only'] == 1) {
			$repOnly_rep = true;
		}
	}
} else {
	foreach ($LPSalesmen as $index => $salesman) {
		if (array_key_exists('rep_only', $salesman) && $salesman['rep_only'] == 1) {
			unset($LPSalesmen[$index]);
		}
	}
}

if (isset($_GET['zip'])) {
	$query = "Select brn_id from brz_BranchZips LEFT JOIN prd_Products  ON brz_BranchZips.prg_id  = prd_Products.prg_id WHERE zip = '{$_GET['zip']}' and ProductID = '{$product1}'";
	$search = http_build_query([
		'rptSQL' => $query,
	]);
	$branch = curlCall("$api_endpoint/lp/customReport.php?$search")[0]['brn_id'];
	$newReps = array();
	$zip = $_GET['zip'];
	$dow = date('w', strtotime($date));
	$zips = $db->query("SELECT * FROM salesDashboard.detroit_zips WHERE zip = '$zip' AND active = '1'");
	$days = $db->query("SELECT * FROM salesDashboard.detroit_days WHERE dow = '$dow' AND active = '1'");
	if (count($zips) > 0) {
		if ($date == date("Y-m-d")) { // same day appts

			foreach ($LPSalesmen as $rep_index => $rep) {
				$matchFound = false; // Initialize a flag to track if a match is found
			
				foreach ($days as $detroitDayzRep) {
					if ($detroitDayzRep['sales_rep_id'] == $rep['id'] && $rep['brn'] == 'TOL') {
						$matchFound = true; // Set the flag to true if a match is found
						break; // No need to check further once a match is found
					} 
				}

				if (!$matchFound) {
					foreach($zips as $detroitZip) {
						if ($zip == $detroitZip['zip'] && $rep['brn'] === 'DET') {
							$newReps[] = $rep; // $newReps array contains DET only reps
						} elseif ($zip['brn'] === 'TOL') {
							$newReps[] = $rep; // $newReps array contains TOL only reps
						}
					}
				}
			}
			
		} else {
			foreach ($LPSalesmen as $rep_index => $rep) {
				foreach ($days as $rep_day) {
					if ($rep_day['sales_rep_id'] == $rep['id']) {
						$newReps[] = $rep;
					}
				}
			}
		}
		$LPSalesmen = $newReps;
	} elseif (count($zips) == 0 && $branch == 'DET') {
	} elseif (count($days) > 0) {
		$badReps = array();
		foreach ($days as $rep_day) {
			$badReps[] = $rep_day['sales_rep_id'];
		}
		foreach ($LPSalesmen as $rep_index => $rep) {
			if (!in_array($rep['id'], $badReps)) {
				$newReps[] = $rep;
			}
		}
		$LPSalesmen = $newReps;
	}
}
$salesmenCount = 0;
$salesmenSlots = [];
foreach ($apptTimes as $time => $value) {
	$salesmenSlots[$time] = 0;
}

foreach ($LPSalesmen as $i => $salesmen) {
	if (!filter_var($_GET['incompatibleRep'], FILTER_VALIDATE_BOOLEAN)) {
		foreach ($salesmen['allowed_products'] as $j => $value) {
			if ($value == 0) {
				unset($LPSalesmen[$i]['allowed_products'][$j]);
			}else{
				$LPSalesmen[$i]['allowed_products'][$j] = $value;
			}
		}
	}
	if (count($LPSalesmen[$i]['allowed_products']) == 0) {
		unset($LPSalesmen[$i]);
		continue;
	} else {
		$salesmenCount++;
		$availibility = searchForId($LPSalesmen[$i]['id'], 'lp_id', $repAvailibility);
		foreach ($availibility as $a => $time) {
			$availibility[$a]['end'] = date('H:i:s', strtotime("{$time['time']}:00 + {$apptDuration} minutes"));
		}
		if (count($availibility) == 0) {
			unset($LPSalesmen[$i]);
			continue;
		} else {
			$availibility = orderByKeyValue('time', $availibility);
			foreach ($availibility as $a => $value) {
				$time = $value['time'];
				$salesmenSlots[$time]++;
			}
			$LPSalesmen[$i]['availibility'] = $availibility;
		}
		$LPSalesmen[$i]['product_count'] = count($LPSalesmen[$i]['allowed_products']);
		foreach ($products as $product) {
			if (!array_key_exists($product['name'], $LPSalesmen[$i]['allowed_products'])) {
				unset($LPSalesmen[$i]);
				continue;
			} else {
				if (!filter_var($_GET['incompatibleRep'], FILTER_VALIDATE_BOOLEAN)) {
					if ($LPSalesmen[$i]['allowed_products'][$product['name']] == '3' && $numberofProducts == 1) {
						unset($LPSalesmen[$i]);
						continue;
					}
					if ($LPSalesmen[$i]['allowed_products'][$product['name']] == '2' && $numberofProducts > 1) {
						unset($LPSalesmen[$i]);
						continue;
					}
					}
			}
		}
	}
}

usort($LPSalesmen, function ($a, $b) {
	return $a['allowed_products'] <=> $b['allowed_products'];
});

if (isset($branch)) {
	$reps = [];
	foreach ($LPSalesmen as $salesman) {
		if (isset($salesman['brn'])) {
			$reps[$salesman['brn']][] = $salesman;
		} else {
			$reps['unassigned'][] = $salesman;
		}
	}
	if (isset($reps[$branch])) {
		$LPSalesmen = $reps[$branch];
		foreach ($reps as $thisBranch => $repSet) {
			if ($thisBranch != $branch) {
				foreach ($repSet as $rep) {
					$LPSalesmen[] = $rep;
				}
			}
		}
	}
}

$query = "SELECT * FROM rto_RepTimeOff WHERE '{$date}' BETWEEN DateBegin AND DATEADD(minute, Duration, CONVERT(DateTime, CONCAT(CONVERT(DATE, DateBegin), ' ', TimeBegin)))";
$search = http_build_query([
	'rptSQL' => $query,
]);
$personalTimes = curlCall("$api_endpoint/lp/customReport.php?$search");
foreach ($personalTimes as $j => $appt) {
	$startDateTime = strtotime(date('Y-m-d H:i:s', strtotime(explode(' ', $appt['DateBegin'])[0] . " " . $appt['TimeBegin'])));
	$endDateTime = strtotime(date('Y-m-d H:i:s', $startDateTime + ($appt['Duration'] * 60)));
	$startDate = date('Y-m-d', $startDateTime);
	$endDate = date('Y-m-d', $endDateTime);
	$startTime = date('H:i', $startDateTime);
	$endTime = date('H:i', $endDateTime);
	if (strtotime($startDate) < strtotime($date) && strtotime($endDate) > strtotime($date)) {
		//Multi Day Time Off
		$reps = searchForId($appt['slr_id'], 'id', $LPSalesmen, true);
		if (count($reps) > 0) {
			foreach ($reps as $r => $rep) {
				unset($LPSalesmen[$r]);
			}
		}
	} else {
		//Single Day Time Off
		$reps = searchForId($appt['slr_id'], 'id', $LPSalesmen, true);
		// if (strtotime($endDate) > strtotime($date)) {
		//     $endDateTime = strtotime(date("Y-m-d H:i:s", strtotime("23:59 {$date}")));
		//     $endDate = date('Y-m-d', $endDateTime);
		//     $endTime = date('H:i', $endDateTime);
		// }
		if (count($reps) > 0) {
			foreach ($reps as $r => $rep) {
				foreach ($rep['availibility'] as $s => $slot) {
					$start = strtotime($date . " " . $slot['time'] . ":00");
					$end = strtotime($date . " " . $slot['end']);
					if (
						($start <= $startDateTime && $startDateTime < $end) || // starts middle of slot
						($start < $endDateTime && $endDateTime <= $end) || // ends middle of slot
						($startDateTime < $start &&  $end < $endDateTime)
					) {
						unset($LPSalesmen[$r]['availibility'][$s]);
					}
				}
				if (count($LPSalesmen[$r]['availibility']) == 0) {
					unset($LPSalesmen[$r]);
				}
			}
		}
	}
}

$query = "SELECT * FROM lds_Leads WHERE CONVERT(DATE, FollowUpDate) = '{$date}'";
$search = http_build_query([
	'rptSQL' => $query,
]);
$followUps = curlCall("$api_endpoint/lp/customReport.php?$search");

foreach ($followUps as $j => $appt) {
	$startDateTime = strtotime(date('Y-m-d H:i:s', strtotime($appt['FollowUpDate'])));
	$startDate = date('Y-m-d', $startDateTime);
	$startTime = date('H:i', $startDateTime);
	$endTime = date("H:i", strtotime("$startTime + 1 hour"));
	$endDateTime = strtotime(date('Y-m-d H:i:s', strtotime("{$startDate} {$endTime}")));
	$reps = searchForId($appt['slr_id'], 'id', $LPSalesmen, true);
	if (count($reps) > 0) {
		foreach ($reps as $r => $rep) {
			foreach ($rep['availibility'] as $s => $slot) {
				$start = $slot['time'] . ":00";
				$end = $slot['end'];
				$start = strtotime($date . " " . $slot['time'] . ":00");
				$end = strtotime($date . " " . $slot['end']);
				if (
					($start <= $startDateTime && $startDateTime < $end) || // starts middle of slot
					($start < $endDateTime && $endDateTime <= $end) || // ends middle of slot
					($startDateTime < $start &&  $end < $endDateTime)
				) {
					unset($LPSalesmen[$r]['availibility'][$s]);
				}
			}
			if (count($LPSalesmen[$r]['availibility']) == 0) {
				unset($LPSalesmen[$r]);
			}
		}
	}
}

if ($_GET['productid'] != 'Inspection' && isset($_GET['canvass']) && $_GET['canvass'] == 'true' && strtotime($date) >= strtotime("+1 day") && (!isset($_GET['rep']) || $_GET['rep'] == '') && date('w') != 5 && date('w') != 6) {

	$availibleSlots = [];
	foreach ($apptTimes as $time => $value) {
		$availibleSlots[$time] = 0;
	}

	foreach ($LPSalesmen as $index => $rep) {
		foreach ($rep['availibility'] as $availible) {
			$time = $availible['time'];
			$availibleSlots[$time] = $availibleSlots[$time] + 1;
		}
	}

	//Canvass Multiplier
	if($branch == 'DET'){
		$multiplier = 3;
	}else{
		$multiplier = 1.8;
	}

	foreach ($availibleSlots as $time => $count) {
		$availibleSlots[$time] = $count * $multiplier;
	}

	$dispos = implode(',', [
		"'Set'",
		"'Verif'",
		"'Cnf'",
		"'Issue'"
	]);
	$query = "Select app_Appointments.ApptDate as date, Lds_Leads.cst_id as cst_id, Lds_Leads.slr_id as slr_id, app_Appointments.Dsp_ID as dsp_id, Lds_Leads.ApptDuration as apptduration from app_Appointments LEFT JOIN Lds_Leads ON app_Appointments.id = Lds_Leads.app_id WHERE Lds_Leads.srs_id = '1' AND Lds_Leads.src_id = 'Canvass' AND app_Appointments.ApptDate BETWEEN '{$date}' AND '{$nextDate}' AND app_Appointments.Dsp_ID IN ({$dispos}) AND Lds_Leads.brn_id = '{$branch}'";

	$search = http_build_query([
		'rptSQL' => $query,
	]);
	$appts = curlCall("$api_endpoint/lp/customReport.php?$search");

	$takenSlots = [];
	foreach ($apptTimes as $time => $value) {
		$takenSlots[$time] = 0;
	}

	foreach ($appts as $j => $appt) {
		$dateTime = explode(' ', $appt['date']);
		$date = $dateTime[0];
		$time = explode(':', $dateTime[1])[0];
		$appts[$j]['date'] = $date;
		$appts[$j]['time'] = $time;
		$takenSlots[$time] = $takenSlots[$time] + 1;
	}

	$productArray = [
		'0' => "Lds_Leads.Productid",
		'1' => "Lds_Leads.Prod2",
		'2' => "Lds_Leads.Prod3"
	];

	$querys = [];
	foreach (array_combinations($products) as $products) {
		$query = [];
		foreach ($products as $index => $product) {
			$query[] = "{$productArray[$index]} = '{$product['name']}'";
		}
		$querys[] = "(" . implode(' AND ', $query) . ")";
	}

	$querys = "(" . implode(' OR ', $querys) . ")";
	$query = "Select app_Appointments.ApptDate as date, Lds_Leads.cst_id as cst_id, Lds_Leads.slr_id as slr_id, Lds_Leads.slr_id2 as slr_id2, app_Appointments.Dsp_ID as dsp_id, Lds_Leads.ApptDuration as apptduration from app_Appointments LEFT JOIN Lds_Leads ON app_Appointments.id = Lds_Leads.app_id WHERE Lds_Leads.srs_id = '1' AND Lds_Leads.src_id = 'Canvass' AND Lds_Leads.slr_id = 0 AND app_Appointments.ApptDate BETWEEN '{$date}' AND '{$nextDate}' AND app_Appointments.Dsp_ID IN ({$dispos}) AND Lds_Leads.brn_id = '{$branch}' AND ({$querys})";
	$search = http_build_query([
		'rptSQL' => $query,
	]);
	$prodAppts = curlCall("$api_endpoint/lp/customReport.php?$search");

	$takenProdSlots = [];
	foreach ($apptTimes as $time => $value) {
		$takenProdSlots[$time] = 0;
	}

	foreach ($prodAppts as $j => $appt) {
		$dateTime = explode(' ', $appt['date']);
		$date = $dateTime[0];
		$time = explode(':', $dateTime[1])[0];
		$appts[$j]['date'] = $date;
		$appts[$j]['time'] = $time;
		if ($appt['slr_id2'] != 0) {
			$count = 2;
		} else {
			$count = 1;
		}
		$takenProdSlots[$time] = $takenProdSlots[$time] + $count;
	}

	$slotsLeft = [];
	foreach ($apptTimes as $time => $value) {
		$slotsLeft[$time] = 0;
	}

	foreach ($availibleSlots as $hour => $max) {
		$slotsLeft[$hour] = ceil($max - $takenSlots[$hour]);
	}


	$productsLeft = [];
	foreach ($apptTimes as $time => $value) {
		$productsLeft[$time] = 0;
	}
	foreach ($availibleSlots as $hour => $max) {
		$productsLeft[$hour] = floor($max - $takenProdSlots[$hour]);
	}

	foreach ($slotsLeft as $hour => $count) {
		if ($count > 0 && $productsLeft[$hour] > 0) {
			$apptTimes[$hour][]['id'] = '0';
		}
	}

	foreach ($apptTimes as $index => $slots) {
		$apptTimes["$index:00"] = $slots;
		unset($apptTimes[$index]);
	}
	$response = json_encode([
		'date' => $date,
		'availibility' => $apptTimes,
	]);

	print_r($response);
} else {
	if(isset($_GET['firstComeFirstServe']) && $_GET['firstComeFirstServe'] == 'true'){
		$dispos = implode(',', [
			"'Cnf'",
			"'Issue'"			
		]);
		$lockedAppts = " OR (app_Appointments.Dsp_ID IN ('Set','Verif') AND (lds_user_11 = 1 OR (lds_user_18 NOT IN (0, 92))))";
	}else{
		$dispos = implode(',', [
			"'Set'",
			"'Verif'",
			"'Cnf'",
			"'Issue'"
		]);
		$lockedAppts = "";
	}
	$ids = [];
	foreach($LPSalesmen as $salesmen){
		$ids[] = $salesmen['id'];	
	}

	$ids = quotize_array($ids);

	$query = "Select app_Appointments.ApptDate as date, Lds_Leads.cst_id as cst_id, Lds_Leads.slr_id as slr_id, Lds_Leads.slr_id2 as slr_id2, app_Appointments.Dsp_ID as dsp_id, Lds_Leads.ApptDuration as apptduration from app_Appointments LEFT JOIN Lds_Leads ON app_Appointments.id = Lds_Leads.app_id WHERE (slr_id in ({$ids}) OR slr_id2 in ({$ids})) AND app_Appointments.ApptDate BETWEEN '{$date}' AND '{$nextDate}' AND (app_Appointments.Dsp_ID IN ({$dispos}){$lockedAppts})";
	$search = http_build_query([
		'rptSQL' => $query,
	]);
	$appts = curlCall("$api_endpoint/lp/customReport.php?$search");
	foreach ($appts as $j => $appt) {
		$dateTime = explode(' ', $appt['date']);
		$date = $dateTime[0];
		$hourTime = $dateTime[1];
		$hourValue = explode(':', $hourTime)[0];
		$minuteValue = explode(":", $hourTime)[1];
		$secondValue = explode(":", $hourTime)[2];
		if ($minuteValue === "00") {
			$time = $hourValue;
		} else {
			$time = $hourValue . ":" . $minuteValue;
		}
		$appts[$j]['date'] = $date;
		$appts[$j]['time'] = $time;
		$slr_key1 = array_key_first(searchForId($appt['slr_id'],'id',$LPSalesmen,true));
		$LPSalesmen[$slr_key1]['appointments'][] = $appts[$j];
		if($appt['slr_id2'] != 0){
			$slr_key2 = array_key_first(searchForId($appt['slr_id2'],'id',$LPSalesmen,true));
			$LPSalesmen[$slr_key2]['appointments'][] = $appts[$j];
		}	

	}
		foreach ($appts as &$appt) {
			$startDateTime = strtotime($appt['time'] . ":00");
			$apptLength = $apptDuration / 60;
		
			if ($apptLength < 1) {
				$convertedLength = $apptLength * 60; // Convert hours to minutes
			} else {
				$convertedLength = $apptLength * 60; // Convert hours to minutes
			}
		
			$endTime = date("H:i", $startDateTime + $convertedLength * 60);
			$appt['endTime'] = $endTime;
		}
		unset($appt); // Release the reference to the last element
		$returnArray = [];
		foreach ($LPSalesmen as &$salesman) {
			$filteredAvailability = array(); // New array to store filtered availability slots
			foreach ($salesman['availibility'] as $availableSlot) {
				list($hours, $minutes) = explode(':', $availableSlot['time']);
				$availableSlotStartTime = (int)$hours + ($minutes / 60);
				list($hours, $minutes) = explode(':', $availableSlot['end']);
				$availableSlotEndTime = (int)$hours + ($minutes / 60);
				$slotIsAvailable = true; // Assume the slot is available initially
				foreach ($appts as $appt) {
					if($appt['slr_id'] == $availableSlot['lp_id'] || $appt['slr_id2'] == $availableSlot['lp_id']){
						list($hours, $minutes) = explode(':', $appt['time']);
						$apptStartTime = (int)$hours + ($minutes / 60);
						list($hours, $minutes) = explode(':', $appt['endTime']);
						$apptEndTime = (int)$hours + ($minutes / 60);
						$cst_id =  $appt['cst_id'];
						if (isset($_GET['ignoreProspect']) && $_GET['ignoreProspect'] == $cst_id && $_GET['ignoreProspect'] != '') {
							continue;
						} elseif ($availableSlotStartTime < $apptEndTime && $availableSlotEndTime > $apptStartTime) {
							$slotIsAvailable = false; // Slot overlaps with an appointment, mark as unavailable
							break; // No need to check further for this slot
						}
					
						if (!isset($_GET['ignoreProspect']) && $availableSlotStartTime < $apptEndTime && $availableSlotEndTime > $apptStartTime) {
							$slotIsAvailable = false; // Slot overlaps with an appointment, mark as unavailable
							break; // No need to check further for this slot
						}
					}
				}

				if ($slotIsAvailable) {
					$filteredAvailability[] = $availableSlot; // Keep the slot in the filtered array
				}
			}
			$salesman['availibility'] = $filteredAvailability; // Update the availability array
		}
		unset($salesman);

		foreach ($LPSalesmen as $i => $salesmen) {
		foreach ($LPSalesmen[$i]['availibility'] as $j => $availible) {

			$slot = searchForId($availible['time'], 'time', $LPSalesmen[$i]['appointments'], true);
			if (count($slot) > 0) {
				foreach($slot as $islot){
					$cst_id =  $islot['cst_id'];
					if (isset($_GET['ignoreProspect']) && $_GET['ignoreProspect'] == $cst_id && $_GET['ignoreProspect'] != '') {
						continue;
					} else {
						unset($LPSalesmen[$i]['availibility'][$j]);
					}
				}
			}
		}
		if (count($LPSalesmen[$i]['availibility']) == 0) {
			unset($LPSalesmen[$i]);
		} else {
			foreach ($LPSalesmen[$i]['availibility'] as $j => $slot) {
				if ($LPSalesmen[$i]['rep_only'] === "1") {
				$apptTimes[$slot['time']][] = [
					'id' => $LPSalesmen[$i]['id'],
					'name' => $LPSalesmen[$i]['name'],
					'allowed_products' => $LPSalesmen[$i]['allowed_products'],
					'brn' => $LPSalesmen[$i]['brn'],
					'rep_only' => $LPSalesmen[$i]['rep_only']
				];
			} else {
				$apptTimes[$slot['time']][] = [
					'id' => $LPSalesmen[$i]['id'],
					'name' => $LPSalesmen[$i]['name'],
					'allowed_products' => $LPSalesmen[$i]['allowed_products'],
					'brn' => $LPSalesmen[$i]['brn'],
				];
			}
			}
		}
	}
	foreach ($apptTimes as $index => $slots) {
		if (strpos($index, ':') === false) {
			$apptTimes["$index:00"] = $slots;
			unset($apptTimes[$index]);
		} 
	}

	$sortedKeys = [];
	
	foreach ($apptTimes as $key => $value) {
	    $timeParts = explode(':', $key);
	    $sortedKeys[$key] = intval($timeParts[0]) * 60 + intval($timeParts[1]);
	}

	// Sort the array based on the integer keys
	asort($sortedKeys);
	
	// Reconstruct the sorted array using original time format
	$sortedApptTimes = [];
	foreach ($sortedKeys as $key => $value) {
	    $sortedApptTimes[$key] = $apptTimes[$key];
	}

	$response = json_encode([
	    'date' => $date,
	    'availibility' => $sortedApptTimes,
	]);

	if(array_key_exists('numberOfDays', $_GET)){
		$fullResponse[] = json_decode($response);
	}else{
		print_r(($response));
	}
}
// die;

// function quotize_array($array){
// 	$result = [];
// 	foreach($array as $value){
// 		$result[] = "'" . $value . "'";
// 	}
// 	return implode(',',$result);
// }

// function searchForId($value, $key, $array, $returnKey = false)
// {
// 	$returnArray = [];
// 	foreach ($array as $index => $val) {
// 		if ($val[$key] == $value) {
// 			if ($returnKey) {
// 				$returnArray[$index] = $array[$index];
// 			} else {
// 				$returnArray[] = $array[$index];
// 			}
// 		}
// 	}
// 	return $returnArray;
// }
// function orderByKeyValue($key, $array)
// {
// 	$key_values = array_column($array, $key);
// 	array_multisort($key_values, SORT_ASC, $array);
// 	return $array;
// }

// function array_combinations($arr)
// {
// 	$result = array();
// 	$count = count($arr);
// 	if ($count == 1) {
// 		return array($arr);
// 	} else {
// 		$prev = array_combinations(array_slice($arr, 0, -1));
// 		$last = array($arr[$count - 1]);
// 		foreach ($prev as $p) {
// 			for ($i = 0; $i < $count - 1; $i++) {
// 				$copy = $p;
// 				array_splice($copy, $i, 0, $last);
// 				$result[] = $copy;
// 			}
// 			$result[] = array_merge($p, $last);
// 		}
// 		return $result;
// 	}
// }

/* function return_no_availability($date) {
 	global $apptTimes;
 	$times = array();
 	foreach ($apptTimes as $index => $value) {
 		$times[$index] = array();
 	}
     $response = json_encode(array("date" => $date, "availibility" => $times));
     print_r($response);
	 die;
 }*/

// function convertToTime($decimalTime) {
// 	$integerVal = floatval($decimalTime) + 3;
// 	$convertBackToString = strval($integerVal);
// 	$hours = floor($convertBackToString);
// 	$minutes = ($convertBackToString - $hours) * 60;
// 	$formattedTime = sprintf('%02d:%02d:00', $hours, $minutes);
// 	return $formattedTime;
// }
