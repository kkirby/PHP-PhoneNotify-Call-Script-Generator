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
2. Echo with variable and method call support.

    echo "Hello $name, did you know that your account balance is" . file\_get_contents('http://myserver.com') . '?';

3. if/else conditionals comparing by equals and not equals.
4. Variable assignment
5. Nested Expressions

    $a = getLength($digits = getDigits());
    if(getLength($digits = getDigits()) == 5){
        // Do something with $digits
    }

6. While loops
7. file\_get_contents

##What's not supported but my have support in the future?
1. Double and/or conditionals
2. Native Variable Increasing

    $i++
    
3. Do/While loops
4. break/continue statements in while loops.

##What's not supported and never will be?
1. Classes
2. All of PHPs built in functions, for obvious reasons.
3. Variable concatenating as arguments to functions and variable assignment.
4. Variable reassignment to another variable.

##What functions can I use?
1. file\_get_contents
2. getDigits
3. getLength

##Can I make my own functions?
Surprisingly, yes, you can! The implementation to create
user functions is incredibly limited due to the fact
that you cannot assign a variable with the value of another
variable. However, if you look passed that, then
everything else seems to look pretty delicious.

To make a function, simply define it like you would
any other PHP function. Then, later in your PHP
code, you can make a call to your PHP function.

There are however some pitfalls in my implementation.

Being able to return a value using the _return_ construct is
not implemented. The reason for this is because variables cannot be
assigned to other variables. To work around this,
instead of using _return_, assign the value you wish to
return to the variable $\__RETURN__.

Recursively nesting functions is also not supported. Take this example

    function one(){
        /**
         * This is not okay because this
         * will call three, wich will call
         * two, which will lead us back to one.
         * This will create an infinite loop.
         * In a more robust language, this is
         * not a problem. However, PhoneNotify's
         * CallScript language is far from it.
         */
        three();
    }
    
    function two(){
        // This is okay.
        one();
    }
    
    function three(){
        // This is okay.
        two();
    }
    
    three();

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
    

##What if I want to assign the value of variable to that of another variable?
The only work around that I've seen is to create a loopback
server that prints the value of whatever is passed to _GET and
then make a remote call to your server.

Your server would have the following code:

    echo $_GET['input'];

End then your Call Script would have the following command:

    $myFirstVar = 'Hello.';
    $myNewVar = file\_get_contents('http://myserver/loopback.php?input=[myFirstVar]');

###Why don't you just do this for us on the backend and use your own server?
Because making a remote call isn't light and will slow down the
execution of the script.
    
##Notes
This program is highly experimental and still in development.
I make no guarantee to its performance.

I don't plan on maintaining this code; updates to this project
will be little to none. I wrote this up quickly
so I could write complex PhoneNotify call scripts with
logical visual flow.

Lastly, I am fully 100% aware that my code is far from beautiful.
I wrote it as quickly as possible and I don't expect to get any
awards for Code Cleanliness. Also, I apologize if you decide to
look at my code and you are quickly disgusted or confused. Maybe
in some free time I will go back through and clean things up.

##License
This work is licensed under the Creative Commons Attribution-NonCommercial 3.0 Unported License. To view a copy of this license, visit http://creativecommons.org/licenses/by-nc/3.0/ or send a letter to Creative Commons, 444 Castro Street, Suite 900, Mountain View, California, 94041, USA.

You can use my work for commercial purposes, but you cannot take my work
and sell for a profit. I want my software to be free and stay free.