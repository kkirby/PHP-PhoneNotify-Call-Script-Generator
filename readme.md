#PHP-PhoneNotify Call Script Generator

##What is this?
This is a simple script that will take PHP code and
convert it into PhoneNotify Call Script Commands

##How does it do it?
Using [Nikic's PHP Parser](https://github.com/nikic/PHP-Parser)
(This person is brilliant) we can take PHP code and re-process it
into another language. The language of choice was PhoneNotify's
Call Script syntax.

##Why?
Have you seen a PhoneNotify call script? It's a mess! No visible logical
flow what so ever.

##How do I use it?
Check out the test.php script. It loads up the parser and generates
the call script from the code inside script.php.

##What's supported?
1. Echo
2. if/else conditionals comparing by equals and not equals.
3. Variable assignment
4. Nested Expressions

    $a = getLength($digits = getDigits());
    if(getLength($digits = getDigits()) == 5){
        // Do something with $digits
    }

5. While loops
6. file\_get_contents

##What's not supported but my have support in the future?
1. Double and/or conditionals
2. User defined functions
3. Native Variable Increasing

    $i++
4. echo with variables

##What's not supported and never will be?
1. Classes
2. All of PHPs built in functions, for obvious reasons.
3. Variable concatenating

##What functions can I use?
1. file\_get_contents
2. getDigits
3. getLength

##What about all the other PhoneNotify functions?
Those are supported too, you just have to use them
as per PhoneNotify's documentation. For example,
in PhoneNotify's documentation, getDigits is to be
used like so:

    ~\GetDigits(MyVariable)~
    
However, when writing the PHP variant, the code would
look like:

    $MyVariable = getDigits();
    
If you wanted to use the EndCall method, the PHP variant
would be:

    EndCall()

But EndCall isn't actually supported by my parser. Instead
all unknown function calls are converted to look like
PhoneNotify methods. Take this example of a bogus function:

    MyBogusFunction()
    
This would result in the following PhoneNotify script:

    ~\MyBogusFunction()~
    
##Notes
This program is highly experimental and still in development.
I make no guarantee to its performance.

I don't plan on maintaining this code; updates to this project
will be little to none. I wrote this up quickly
so I could write complex PhoneNotify call scripts with
logical visual flow.

##License
This work is licensed under the Creative Commons Attribution-NonCommercial 3.0 Unported License. To view a copy of this license, visit http://creativecommons.org/licenses/by-nc/3.0/ or send a letter to Creative Commons, 444 Castro Street, Suite 900, Mountain View, California, 94041, USA.

You can use my work for commercial purposes, but you cannot take my work
and sell for a profit. I want my software to be free and stay free.