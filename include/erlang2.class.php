<?php

/*
 * Erlang-C Class version 2.0 (11/19/2011)
 * Author James Dimitrov (james@jaymzzz.com)
 *
 *
 * Most of the code was taken from and cleaned up to work with PHP:
 * http://www-etud.iro.umontreal.ca/~chanwyea/callcenter/ccoptim/html/umontreal/iro/lecuyer/ccoptim/approx/mmc/ErlangC.html
 *
 * CHANGES
 * ===================
 * v 2.0 - Added ability to display or supress error messages
 *       - Added ability to update values without having to call the class all over again
 *       - Remove redundant error checking
 *       - Renamed all functions for easier reading and understanding
 *       - Added debug options
 *
 * v 1.1.1 - Removed predefined periods and opted for setting a numeric period in seconds
 *         - Added ability to have max inbound capacity
 *
 * v 1.1 - Added ability to set period per hour or second
 *
 * v 1.0 - Initial Release

 */

class Erlang
{

//Definition of primary variables:
//$inbound_calls - (incoming calls: default value is calls/period defined)
//$average_talk_time - (Average talk time in seconds)
//$period_in_seconds - (Length of period in seconds ( 3600 = hour, 1800 = 30 min, 1200 = 20 min, etc.)
//$max_queue_length - set at 999, but should be the max queue length
//$silent - supress all error messages

    private $inbound_calls;
    private $average_talk_time;
    private $max_queue_length;
    private $silent = 1;
    private $period_in_seconds;
    private $debug = 0;

    function set_parameters($inbound_calls = -1, $average_talk_time = -1, $max_queue_length = 999, $period_in_seconds = 3600)
    {

        $this->inbound_calls = $inbound_calls / $period_in_seconds;
        $this->average_talk_time = $period_in_seconds / $average_talk_time / $period_in_seconds;
        $this->max_queue_length = $max_queue_length;
        $this->period_in_seconds = $period_in_seconds;

        if ($this->check_all_parameters()) {

            if ($this->debug == 1) {
                echo "Inbound Calls $this->inbound_calls<br />
                      Average Talk Time: $this->average_talk_time<br />
                      Max Queue Lenth: $this->max_queue_length <br />
                      Period in Seconds: $this->period_in_seconds<br />";
            }

            return TRUE;
        }
        $this->set_error('Error updating Parameters in update_parameters()');
        return FALSE;
    }

    function set_error($message)
    {

        if ($this->debug == 1) {
            echo "Sending error message: $error<br />";
        }

        if ($this->silent != 0) {
            echo $message;
        }

        die();
    }

    function check_value($value)
    {

        if ($this->debug == 1) {
            echo "Entering check_value($value)<br />";
        }

        if (!is_numeric($value) || $value <= 0) {
            return FALSE;
        }

        return TRUE;
    }

    function set_debug($value)
    {
        $this->debug = $value;
    }

    function set_silent($value)
    {
        $this->silent = $value;
    }

//Function to check the default values for each method call. Just to be on the safe side
    function check_all_parameters()
    {

        if ($this->debug == 1) {
            echo "Checking all parameters <br />";
        }

        if (!$this->check_value($this->inbound_calls)) {
            $this->set_error('Inbound calls must be a positive value');
        }

        if (!$this->check_value($this->average_talk_time)) {
            $this->set_error('Average talk time must be a positive value');
        }

        if (!$this->check_value($this->max_queue_length)) {
            $this->set_error('Max queue length must be a positive value');
        }

        if (!$this->check_value($this->period_in_seconds)) {
            $this->set_error('Max queue length must be a positive value');
        }

        return TRUE;
    }

//Function to get the probability ratio of a delay given the number of agents specified
    function calculate_delay($number_of_agents)
    {

        if ($this->debug == 1) {
            echo "Entering calculate_delay($number_of_agents)<br />";
        }

        if (!$this->check_value($number_of_agents)) {
            $this->set_error('Number of agents must be a positive value in calculate_delay()');
        }

//More calls than can possibly be handled
        if ($this->inbound_calls / $this->average_talk_time >= $number_of_agents) {
            return 1;
        }

//Load Defaults
        $total_load = 0;
        $temp_load = 1;

//We need the maximum number of customers possible
        $max_queue = $number_of_agents + $this->max_queue_length;

//calculate the load based on the number of agents
        for ($x = 1; $x < $number_of_agents; $x++) {

//Load gets smaller as agents increase
            $temp_load = $temp_load * $this->inbound_calls / ($x * $this->average_talk_time);
            $total_load += $temp_load;
        }
//Add a buffer to the total Load
        $agent_load = $total_load + 1;

//calculate load for waiting clients which cannot be handled by agents
        for ($x = $number_of_agents; $x <= $max_queue; $x++) {

            $temp_load = $temp_load * $this->inbound_calls / ($number_of_agents * $this->average_talk_time);
            if ($temp_load <= 0) {
                break;
            }
            $total_load += $temp_load;
        }
//Add another buffer to the total load
        $total_load++;

//get the load ratio
        $ratio = 1 / $total_load;

//return the value
        return 1 - $ratio * $agent_load;
    }

//Calcualte the service level based on the number of agents and wait time in seconds
    function calculate_service_level($number_of_agents, $wait_time)
    {

        if ($this->debug == 1) {
            echo "Entering calculate_service_level ($number_of_agents, $wait_time) <br />";
        }

//We need a real number of agents and wait time
        if (!$this->check_value($number_of_agents)) {
            $this->set_error('Number of agents must be a positive value in calculate_service_level()');
        }

        if (!$this->check_value($wait_time)) {
            $this->set_error('Wait time must be a positive value in calculate_service_level()');
        }
//Return numeric service Level between 1 and 0;
        return max(0, 1.0 - ($this->calculate_delay($number_of_agents) / exp(($number_of_agents * $this->average_talk_time - $this->inbound_calls) * $wait_time)));
    }

//calculate the required agents based on wait time in seconds and service level ratio
    function calculate_required_agents($wait_time, $service_level)
    {

        if ($this->debug == 1) {
            echo "Entering calculate_required_agents($wait_time, $service_level)<br />";
        }

//We need a real wait time
        if (!$this->check_value($wait_time)) {
            $this->set_error('Wait time must be a positive value in calculate_required_agents()');
        }

//We need a service level between 1 and 0
        if ($service_level < 0 || $service_level > 1) {
            $this->set_error('The target service level must be in beween 0 and 1 in calculate_required_agents()');
        }

//If there are no calls
        if ($this->inbound_calls == 0) {
            return 0;
        }
//If there is no service the number is HUGE
        if ($this->average_talk_time == 0) {
            return "999999999999";
        }

//Check to see if it can be handled by 1 agent
        if ($this->calculate_service_level('1', $wait_time) >= $service_level) {
            return 1;
        }

//Calculcate the Load on the agents
        $load = $this->inbound_calls / $this->average_talk_time;

//Perform a binary search on the most-convenient ratio of service level to agents
        $value_1 = floor($load);
        $value_2 = ceil($load + sqrt($load));
        while ($this->calculate_service_level($value_2, $wait_time) < $service_level) {

            $value_1 = $value_2;
            $value_2 = max($value_2 + 1, $value_2 + sqrt($load));
        }

        $value = 1;

        while ($value_2 - $value_1 > 1) {
            $value = round(($value_1 + $value_2) / 2);
//check the service level of the current value
            $check_service_level = $this->calculate_service_level($value, $wait_time);

//If the service level of val is better than the minimum requirement, we use it
            if ($check_service_level >= $service_level) {
                $value_2 = $value;
            } else {
                $value_1 = $value;
            }
        }
        return floor($value_2);
    }

//Calculate the average wait time based on the number of agents
    function calculate_average_wait_time($number_of_agents)
    {

        if ($this->debug == 1) {
            echo "Entering calculate_average_wait_time($number_of_agents)<br />";
        }

//We need a real number of agents
        if (!$this->check_value($number_of_agents)) {
            $this->set_error('The number of agents cannot be negative in calculate_average_wait_time()');
        }

//It is impossible for this number of agents to process the incoming calls
        if ($this->inbound_calls >= $this->average_talk_time * $number_of_agents) {
            return "9999999999";
        }

//return the Average Wait time
        return $this->calculate_delay($number_of_agents) / ($this->average_talk_time * $number_of_agents - $this->inbound_calls);
    }

//Calculate the excess time, i.e. the average amount of time over the SLA wait limit
    function calculate_time_over_sla($number_of_agents, $wait_time)
    {

        if ($this->debug == 1) {
            echo "Entering calculate_time_over_sla($number_of_agents, $wait_time)<br />";
        }

//We need a real number of agents and wait time
        if (!$this->check_value($number_of_agents)) {
            $this->set_error('Number of agents must be a positive value in calculate_time_over_sla()');
        }

        if (!$this->check_value($wait_time)) {
            $this->set_error('Wait time must be a positive value in calculate_time_over_sla()');
        }

//It is impossible for this number of agents to process the incoming calls
        if ($this->inbound_calls >= $this->average_talk_time * $number_of_agents) {
            return "9999999999";
        }

//Return the Excess wait time
        return $this->calculate_delay($number_of_agents) / exp($this->average_talk_time * $number_of_agents * $wait_time - $this->inbound_calls * $wait_time) / ($this->average_talk_time * $number_of_agents - $this->inbound_calls);
    }

}


