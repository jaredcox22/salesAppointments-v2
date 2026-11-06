<?php
	$start_time = microtime(true);


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
require_once("/home/shared/functions.php");
require_once("/home/shared/db.php");


$db = new db('salesDashboard');
$api_endpoint = 'http://intserver-api';


$storedData = $db->query("SELECT `name`,`value`,`dateTime` as `updated` FROM salesDashboard.storedData ORDER BY updated DESC;");
foreach ($storedData as $row => $values) {
	$index = $values['name'];
	if (!isset($$index)) {
		$$index = json_decode(base64_decode($values['value']), true);
	}
}

foreach ($LPSalesmen as $index => $salesman) {
	if ((array_key_exists('rep_only', $salesman) && $salesman['rep_only'] == 1) || count($salesman['allowed_products']) == 0 || !$salesman['name']) {
		unset($LPSalesmen[$index]);
	} else {
		$LPSalesmen[$index]['product_count'] = count($salesman['allowed_products']);
	}
}


$reps = [];
foreach ($LPSalesmen as $salesman) {
	$reps[$salesman['id']] = $salesman;
}

$LPSalesmen = $reps;

$lds_id = $_GET['lds_id'];
$appointment = lpQuery("SELECT * FROM lds_Leads WHERE id = '{$lds_id}'");
$customer = lpQuery("SELECT * FROM cst_Customers WHERE id = '{$appointment['cst_id']}'");
$date = date("Y-m-d", strtotime($appointment['ApptDate']));
$time = date("G:i", strtotime($appointment['ApptDate']));
$zip = $customer['zip'];
$detroitZips = $db->query("SELECT zip FROM detroit_zips WHERE active = '1'");
foreach ($detroitZips as $zip) {
	$dZips[] = $zip['zip'];
}
if (in_array($zip, $dZips)) {
	$thisBranch = 'DET';
} else {
	$thisBranch = 'TOL';
}
$reps = $db->query("SELECT DISTINCT(lp_id) FROM salesDashboard.salesman WHERE `value` = '1';");
$dow = date('w', strtotime($date));
$detroitReps = $db->query("SELECT sales_rep_id FROM detroit_days WHERE dow = '$dow' AND active = '1'");
foreach ($reps as $rep) {

	if (array_search($rep['lp_id'], array_column($detroitReps, 'sales_rep_id'))) {
		$branches['DET'][] = $rep['lp_id'];
	} else {
		$branches['TOL'][] = $rep['lp_id'];
	}
}
if (in_array($zip, $dZips)) {
	$branch = 'DET';
} else {
	$branch = 'TOL';
}


$availibleReps = $branches[$thisBranch];

if ($appointment['slr_id'] != 0) {
	result($appointment['slr_id']);
} else {

	$products = [];
	if ($appointment['Productid']) {
		$products[] = $appointment['Productid'];
	}
	if ($appointment['Prod2']) {
		$products[] = $appointment['Prod2'];
	}
	if ($appointment['Prod2']) {
		$products[] = $appointment['Prod3'];
	}

	$siblingAppointments = lpQuery("SELECT * FROM lds_Leads WHERE ApptDate = '{$appointment['ApptDate']}' AND id != '{$lds_id}' AND dsp_id NOT LIKE '%CXL%' AND dsp_id NOT IN ('CTC', 'OVB', 'UTC')", false);

	$moveable = [];
	foreach ($siblingAppointments as $index => $sibling) {
		if ($sibling['Confirmed'] == 0 && $sibling['slr_id'] != 0 && $sibling['lds_user_11'] != 1 && ($sibling['lds_user_18'] == 92 || $sibling['lds_user_18'] == 0 || !$sibling['lds_user_18']) && !$sibling['FollowUpDate']) {
			$moveable[] = $sibling;
		}
	}
	if (count($moveable) > 0) {
		foreach ($moveable as $index => $appt) {
			$good = true;
			$slr_id = $appt['slr_id'];
			foreach ($products as $product) {
				if ($product != '' && !array_key_exists($product, $LPSalesmen[$slr_id]['allowed_products'])) {
					$good = false;
					break;
				} else if ($LPSalesmen[$appt['slr_id']]['allowed_products'][$product] == 0) {
					$good = false;
					break;
				} else if (count($products) == 1 && $LPSalesmen[$slr_id]['allowed_products'][$product] == 3) {
					$good = false;
					break;
				} else if (count($products) > 1 && $LPSalesmen[$slr_id]['allowed_products'][$product] == 2) {
					$good = false;
					break;
				} else if (in_array($slr_id, $allowedProducts)) {
					$good = false;
					break;
				}
			}
			if ($good) {
				$moveable[$index]['rep'] = $LPSalesmen[$appt['slr_id']];
			} else {
				unset($moveable[$index]);
			}
		}
	}

	$apptSchedule = $db->query("SELECT data from apptSchedule WHERE date = '$date' ORDER BY updated DESC LIMIT 1");
	if (is_array($apptSchedule) && count($apptSchedule) > 0) {
		$apptSchedule = json_decode(base64_decode($apptSchedule[0]['data']), true);
	} else {
		$url = "$api_endpoint/lp/getApptSchedule.php?start=$date&end=$date";
		$found = 'false';
		$attempts = 0;

		while ($found == 'false' && $attempts < 5) {
			$attempts++;
			$response = curlCall($url);
			if (is_array($response) && count($response) > 0) {
				$found = 'true';
				$apptSchedule = $response;
				break;
			}
		}
	}

	$dateTime = strtotime($date);
	foreach ($apptSchedule as $i => $appt) {
		$start = $appt['start'];
		$start = date('Y-m-d', strtotime($start));
		$start = strtotime($start);
		if ($start > $dateTime) {
			unset($apptSchedule[$i]);
		}
	}

	$availible = curlCall("https://api.integrityprodserver.com/salesAppointments/getSalesAppointments.php?" . http_build_query([
		'date' => $date,
		'product1' => $appointment['Productid'],
		'product2' => $appointment['Prod2'],
		'product3' => $appointment['Prod3'],
		"zip" => $customer['Zip'],
		"forceFresh" => "true"
	]))['availibility'];

	if (array_key_exists($time, $availible) && count($availible[$time]) > 0) {

		$slot = $availible[$time][0];
		$rep = $slot['id'];
		$lead = [
			'custid' => $appointment['cst_id'],
			'lds_id' => $appointment['id'],
			'slr_id' => $rep
		];
		if (!$_GET['checkOnly']) {
			result(update($lead, "http://intserver-api/lp/updateLead.php"));
		} else {
			result(["slot available {$lead['slr_id']}"]);
		}
	} elseif (count($moveable) > 0) {
		usort($moveable, function ($a, $b) {
			return $a['rep']['product_count'] - $b['rep']['product_count'];
		});
		$slot = $moveable[0];
		$rep = $slot['slr_id'];

		$lead = [
			'custid' => $slot['cst_id'],
			'lds_id' => $slot['id'],
			'slr_id' => '0'
		];
		if (!$_GET['checkOnly']) {
			update($lead, "http://intserver-api/lp/updateLead.php");
		}
		$lead = [
			'custid' => $appointment['cst_id'],
			'lds_id' => $appointment['id'],
			'slr_id' => $rep
		];
		if (!$_GET['checkOnly']) {
			result(update($lead, "http://intserver-api/lp/updateLead.php"));
		} else {
			result(['slot available']);
		}
	} else {
		result(['no slots available']);
	}
}




function lpQuery($query, $reduce = true)
{
	if (strpos($query, ' ') !== false) {
		$query = urlencode($query);
	}
	$data = curlCall("http://intserver-api/lp/customReport.php?rptSQL=" . $query);
	if ($reduce) {
		if (!is_array($data) || count($data) > 1) {
			return $data;
		} elseif (count($data) == 1) {
			return $data[array_key_first($data)];
		}
	} else {
		return $data;
	}
}

function result($data)
{
	if (!isJSON($data)) {
		$data = json_encode($data);
	}
	print_r($data);
	die;
}

function isJSON($string)
{
	return is_string($string) && is_array(json_decode($string, true)) ? true : false;
}

function update($paramArray, $url)
{
	$params = http_build_query($paramArray);
	$url = "{$url}?{$params}";
	return curlCall($url);
}
