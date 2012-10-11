<?php
ActOnDigitPress(false);

function getTime(){
	$__RETURN__ = file_get_contents('http://www.timeapi.org/utc/now?format=%25I+%25M+%25p');
}

function getDate(){
	$__RETURN__ = file_get_contents('http://www.timeapi.org/utc/now?format=%25A+%25B+%25d+%25Y');
}

function greet($person){
	echo 'Hello ' . $person;
}

$a = true;
while($a == true){
	echo "Press one for the first menu, two for the second menu, and 3 for the current time, followed by pound.";
	$number = getDigits();
	if($number == 1){
		echo "First Menu";
		echo "Press one for first sub menu, two for second sub menu followed by pound or hash.";
		$secondNumber = getDigits();
		if($secondNumber == 1){
			echo "First Sub Menu in first menu.";
		}
		else if($secondNumber == 2){
			echo "Second Sub Menu in first menu.";
		}
		else {
			echo "Not a valid entry.";
		}
	}
	else if($number == 2){
		echo "Second Menu";
		echo "Press one for first sub menu, two for second sub menu.";
		$secondNumber = getDigits();
		if($secondNumber == 1){
			echo "First sub menu in second menu.";
		}
		else if($secondNumber == 2){
			echo "Second sub menu in third menu.";
		}
		else {
			echo "Not a valid entry.";
		}
	}
	else if($number == 3){
		greet('Kyle');
		echo 'Today is ' .
			getDate() .
			'. The time is ' . getTime() . '.';
	}
	else {
		echo "N/A";
	}
}
?>