<?php
ActOnDigitPress(false);
$a = true;
while($a == true){
	echo "Press one for the first menu, two for the second menu followed by pound or hash.";
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
	else {
		echo "N/A";
	}
}
?>