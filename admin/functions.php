<?php

function connectDB()
{
	$user = "ssadmin@sstrackingsqltest";
	$passwd = "M1cr0s0ft";
	// SQL Server Extension Sample Code:
	$connectionInfo = array("UID" => $user, "pwd" => $passwd, "Database" => "sstrackingsql_test", "LoginTimeout" => 30, "Encrypt" => 1, "TrustServerCertificate" => 0);
	$serverName = "tcp:sstrackingsqltest.database.windows.net,1433";
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	return $conn;
}


function verifyMSSales($enrollment, $link)
{
	$query = "select Id from msinfo where msinfo.enrollment = '$enrollment'";
	$result = sqlsrv_query($link, $query);
	$registros = sqlsrv_has_rows($result);
	if($registros == 0)return 0;
	else return 1;
	
}

function verifyCIF($enrollment, $link)
{
	$query = "select enrollment from cifinfo where cifinfo.enrollment = '$enrollment'";
	$result = sqlsrv_query($link, $query);
	$registros = sqlsrv_has_rows($result);
	if($registros == 0)return 0;
	else return 1;
}

function getTrackingNumber($enrollment, $link)
{
	$query = "select trackingNumber, appliance, serialNumber, carrier, country, expectedShipDate from msiron where enrollmendId  = '$enrollment'";
	$result = sqlsrv_query($link, $query);
	$registros = sqlsrv_has_rows($result);
	
	$stmt = sqlsrv_query($link, $query);
			
	while($row = sqlsrv_fetch_array($stmt))
	{	
		$trackingNum = $row['trackingNumber'];
		if($trackingNum == '')
		{
			
			echo "We are processing your unit<br></br>";
			echo "We expect to ship your unit on <b>". $row['expectedShipDate']->format('F d Y')."</b><br></br>";
		}
		else
		{
			echo "Your StorSimple model: <b>" . $row['appliance'] . "</b> was shipped!!</br>";
			$courier = $row['carrier'];
			if($courier == "DHL")
			{
				$trackingstring = "http://www.dhl.com/en/express/tracking.html?AWB=".$trackingNum."&brand=DHL";
			}
			else $trackingstring = "https://www.fedex.com/apps/fedextrack/?action=track&tracknumbers=".$trackingNum."&locale=en_US&cntry_code=us";
			
			$trackingstring = str_replace(";",",",$trackingstring);
			
			echo "You can track your shipment via <b>" . $row['carrier'] . ". </b>";
			echo "Tracking Number: <b><a href='$trackingstring' target='new'>" . str_replace(";", ",", $trackingNum) . "</a></b><br></br>";
		}
	}
	
	
}


function cleanTables($link)
{
	$query1 = "DELETE FROM dbo.msinfo";
	$query2 = "DELETE FROM dbo.msiron";
	$query3 = "DELETE FROM dbo.cifinfo";
	$result = sqlsrv_query($link, $query1);
	if($result == false) echo "Error while cleaning up MS Table<br\>";
	
	$result2 = sqlsrv_query($link, $query2);
	if($result2 == false) echo "Error while cleaning up Iron Table<br\>";

	$result3 = sqlsrv_query($link, $query3);
	if($result3 == false) echo "Error while cleaning up CIF Table<br\>";
	
		
}


function loadMSSales($link, $fileinput)
{

	$file = fopen($fileinput['tmp_name'],"r");
	$fieldseparator = "*";
	$lineseparator = "\n";
	if (!$file) {
			echo "Error opening data file.\n";
			exit;
	}

	$size = filesize($fileinput['tmp_name']);
	//echo "</br>File Size: $size</br>";
	
	if (!$size) {
			echo "File is empty.\n";
			exit;
	}
	
	$csvcontent = fread($file,$size);
	
	$lines = 0;
	$queries = "";
	$linearray = array();
	$errors = 0;
	$linesOK = 0;

	foreach(explode($lineseparator,$csvcontent) as $line) 
	{
		
		//echo $line . "</br></br>";
		$line = str_replace("'","",$line);
		$line = str_replace(",", "", $line);
		$linearray = explode($fieldseparator,$line);	
		
		//$linearray[3] = str_replace(",", " ", $linearray[3]); //remove commas from Customer Name
		//print_r($linearray) . "</br></br>";
		
		$linemysql = implode("','",$linearray);
		
		$linemysql = utf8_encode($linemysql);
		//echo "</br></br>" . $linemysql . "</br></br>";
		
		$query = "INSERT INTO dbo.msinfo (
			enrollment,
			tpid,
			month, 
			customerName,
			asapType,
			appliance,
			quantity,
			actuallyShipped,
			revenue,
			cifform,
			shipped,
			notes,
			area,
			subsidiary,
			district,
			premier,
			premierApproved,
			supportLevel,
			salesDate,
			referralDate,
			msxID,
			billingCoverageStartDate,
			billingCoverageEndDate,
			coverageMonths,
			commitmentPeriod,
			amendmentInfo,
			amendmentBoolean,
			referralCancelled,
			aspireUnit,
			sellerAlias,
			lss,
			reasonForMissingAmendment
			)
		values ('$linemysql')";
		
		
		
		$stmt = sqlsrv_query($link, $query);
		if($stmt == true)
		{
			//echo "<br\><br\>Insert OK!!!!</br></br>";
			$linesOK++;
			
		}
		else
		{ 
			//print_r(sqlsrv_errors());
			//echo "<b>Query: $query</b>";
			//echo "<br/><br/>";
			$errors++;
		}
		
		//echo $query . "<br/><br/><br/>";
		//break;	
	}
			
	echo "<br />MS Sales Lines Added: <b>$linesOK</b><br />";
	echo "MS Sales Errors: <b>$errors</b><br /><br />";
	

	
	
	fclose($file);
	
	
}


function convertDate($fecha)
{
	$fecha = DateTime::createFromFormat('m/d/Y', $fecha);
	if ($fecha) {
    return $fecha -> format('Y-m-d');
	}
	else return "19000101";
}


function loadIronReport($link, $fileinput)
{
	$file = fopen($fileinput['tmp_name'],"r");
	$fieldseparator = "*";
	$lineseparator = "\r\n";
	if (!$file) {
			echo "Error opening Iron data file.\n";
			exit;
	}

	$size = filesize($fileinput['tmp_name']);
	//echo "</br>File Size: $size</br>";
	
	if (!$size) {
			echo "File is empty.\n";
			exit;
	}
	
	$csvcontent = fread($file,$size);
	
	$lines = 0;
	$queries = "";
	$linearray = array();
	$errors = 0;
	$linesOK = 0;

	foreach(explode($lineseparator,$csvcontent) as $line) 
	{
		$line = str_replace("'","",$line);
		$linearray = explode($fieldseparator,$line);
		
			
		/* Check for invalid or empty dates in Array items: 4, 17, 18, 22, 23  */
		
		$linearray[4] = convertDate($linearray[4]);
		$linearray[17] = convertDate($linearray[17]);
		$linearray[18] = convertDate($linearray[18]);
		$linearray[22] = convertDate($linearray[22]);
		$linearray[23] = convertDate($linearray[23]);		
		
		
		/* END Check Date */
		$linemysql = implode("','",$linearray);
		$linemysql = utf8_encode($linemysql);

		$query = "INSERT INTO dbo.msiron (
			orderID,
			msspo,
			enrollmendId,
			orderReceivedDate,
			ackDate,
			customerName,
			skuOrdered,
			appliance,
			quantity,
			serviceSKU,
			serviceLevel,
			address,
			city,
			state,
			postal,
			country,
			serialNumber,
			expectedShipDate,
			actualShipDate,
			incoterms,
			carrier,
			trackingNumber,
			arrivalDate,
			deliveryDate		
			)
		values ('$linemysql')";
		
		
		//echo "Query: <b>$query</b><br><br>";
		$stmt = sqlsrv_query($link, $query);
		if($stmt == true)
		{
			//echo "<br\><br\>Insert OK!!!!</br></br>";
			$linesOK++;
			
		}
		else
		{ 
			//print_r(sqlsrv_errors());
			//echo "<b>Query: $query</b>";
			//echo "<br/><br/>";
			$errors++;
		}
		
		//echo $query . "<br/><br/><br/>";
		//break;	
	}
			
	echo "Iron Lines Added: <b>$linesOK</b><br />";
	echo "Iron Errors: <b>$errors</b><br /><br />";	
	fclose($file);
}

function recordLastUpdate($link, $errorsMS, $errorsIron)
{
	$lastUpdate = date("d-m-Y");
	$queryMS = "INSERT INTO lastUpdateMS (errors, lastUpdate) VALUES ('$errorsMS', '$lastUpdate')";
	$queryIron = "INSERT INTO lastUpdateIron (errors, lastUpdate) VALUES ('$errorsIron', '$lastUpdate')";
	$stmtMS = sqlsrv_query($link, $queryMS);
	$stmtIron = sqlsrv_query($link, $queryIron);
	//if($stmt) echo "Log succesfull!<br>";
	
	
}


function loadCIF($link, $fileinput)
{

	$file = fopen($fileinput['tmp_name'],"r");
	$fieldseparator = "*";
	$lineseparator = "\r\n";
	if (!$file) {
			echo "Error opening data file.\n";
			exit;
	}

	$size = filesize($fileinput['tmp_name']);
	//echo "</br>File Size: $size</br>";
	
	if (!$size) {
			echo "File is empty.\n";
			exit;
	}
	
	$csvcontent = fread($file,$size);
	
	$lines = 0;
	$queries = "";
	$linearray = array();
	$errors = 0;
	$linesOK = 0;

	foreach(explode($lineseparator,$csvcontent) as $line) 
	{
		
		//echo $line . "</br></br>";
		$line = str_replace("'","",$line);
		$line = str_replace(",", "", $line);
		$linearray = explode($fieldseparator,$line);	
		
		//$linearray[3] = str_replace(",", " ", $linearray[3]); //remove commas from Customer Name
		//print_r($linearray) . "</br></br>";
		
		$linemysql = implode("','",$linearray);
		
		$linemysql = utf8_encode($linemysql);
		//echo "</br></br>" . $linemysql . "</br></br>";
		
		$query = "INSERT INTO dbo.cifinfo (
			modifiedDate,
			enrollment,
			sku,
			requestType,
			ssappliance,
			quantity,
			support,
			supportSKU,
			approvedPremierCountries,
			desiredDeliveryDate,
			desiredDeliverDate_7days,
			multipleShipTo,
			unitPrice,
			shippingCondition,
			IOR,
			poa,
			poalink,
			PODate,
			collectiveNumber,
			soldToPO,
			soldTOline,
			soldToName1,
			soldToName2,
			soldToStreet1,
			soldToStreet2,
			soldToPostalCode,
			soldToCity,
			soldToState,
			soldToCountry,
			soldToContactName,
			soldToPhone,
			soldToEmail,
			billToName1,
			billToName2,
			billToStreet1,
			billToStreet2,
			billToPostalCode,
			billToCity,
			billToState,
			billToCountry,
			billToTaxID,
			billToName,
			billToPhone,
			billToEmail,
			shipTo,
			shipToName1,
			shipToName2,
			shipToStreet1,
			shipToStreet2,
			shipToPostalCode,
			shipToCity,
			shipToState,
			shipToCountry,
			shipToName,
			shipToPhone,
			shipToEmail,
			shipToBusinessRegNo,
			shipToEORI,
			salesPerson,
			salePersonEmail,
			technicalPerson,
			technicalEmail,
			technicalPhone,
			region,
			status,
			stageName,
			closeDate,
			leadSource,
			salesRequestedShipDate,
			forecastCategory,
			notes,
			referredDate,
			additionalInfo,
			uniqueID,
			endDeploymentAddress
			)
		values ('$linemysql')";
		
		
		
		$stmt = sqlsrv_query($link, $query);
		if($stmt == true)
		{
			//echo "<br\><br\>Insert OK!!!!</br></br>";
			$linesOK++;
			
		}
		else
		{ 
			//print_r(sqlsrv_errors());
			//echo "<b>Query: $query</b>";
			//echo "<br/><br/>";
			$errors++;
		}
		
		//echo $query . "<br/><br/><br/>";
		//break;	
	}
			
	echo "<br />CIF lines Added: <b>$linesOK</b><br />";
	echo "CIF Errors: <b>$errors</b><br /><br />";
	

	
	
	fclose($file);
	
	
}
?>