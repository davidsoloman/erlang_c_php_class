<?php
 
/*
 *
 * This is an example of how to call the Erlang class
 *
 *
 *
 */
 
require ("include/erlang2.class.php");
 
$erlang = new Erlang();
 
//Debugging is disabled by default, and are the error messages. If these are needed, they can be enabled below
$erlang->set_silent(0);
$erlang->set_debug(1);
 
////////////////EXAMPLE 1///////////////////
 
//In this example, there are 3 calls per 20 minute interval with a 10 minute call time
$erlang->set_parameters(3,600,1200);
 
//The number of techs needed to answer 95% of calls within 10 seconds
echo $erlang->calculate_required_agents(10,0.95) . "<br>";
 
//The number of techs needed to answer 75% of calls within 30 seconds
echo $erlang->calculate_required_agents(30,0.75). "<br>";
 
////////////////EXAMPLE 2///////////////////
 
//In this example, there are 45 calls per hour with a 5:20 minute call time
$erlang->set_parameters(45,320,3600);
 
//The number of techs needed to answer 95% of calls within 10 seconds
echo $erlang->calculate_required_agents(10,0.95) . "<br>";
 
//The number of techs needed to answer 75% of calls within 30 seconds
echo $erlang->calculate_required_agents(30,0.75) . "<br>";
