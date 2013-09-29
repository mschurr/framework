<?php
/*
    PHP Network Finger Utility
    @author Matthew Schurr <mschurr@rice.edu>

    You can use this program to retrieve information about students/faculty at Rice using a search query.

    The search returns an array of arrays structured similar to the examples below (note that more than one result can be returned).
    A search with no results returns an empty array.
	
	I recommend locally caching your queries to eliminate load on the server.

    Example Usage:
        $res = FingerUtility::finger("mas20"); # Search by NetID

        Array(
			Array(
				[name] => Schurr, Matthew Alexander
				[class] => sophomore student
				[matric_term] => Fall 2012
				[college] => Duncan College
				[major] => Computer Science
				[address] => Duncan College MS-715, 1601 Rice Blvd, , TX 77005-4401
				[email] => 
				[mailto] => mailto:
			)
		)

        $res = FingerUtility::finger("Devika"); # Search by Name

        Array(
			Array(
				[name] => Subramanian, Dr Devika
				[class] => faculty
				[department] => Computer Science
				[title] => Professor; Professor, ECE
				[mailstop] => Computer Science MS132
				[office] => 3094 Duncan Hall
				[phone] => 713-348-5661
				[homepage] => http://www.cs.rice.edu/~devika/
				[email] => 
				[mailto] => mailto:
			)
		)
*/
			
class Finger
{
	public static function query($query, $host='rice.edu') {
		/* Searches the FINGER server at $host (see http://tools.ietf.org/html/rfc1288) for information related to a search query. Returns an array of arrays. */
		$h = fsockopen($host,79,$errno,$errstr,3);
		
		if(!$h)
			return array();
		
		fputs($h, $query.PHP_EOL);
		stream_set_timeout($h, 3);
		
		$response = '';
		
		while(!feof($h)) {
			$response .= fgets($h, 1024);
		}
		
		fclose($h);
		
		if(strpos($response,"0 RESULTS:")  !== false) {
			return array();
		}
		
		$res = array();
		
		$segments = explode("------------------------------------------------------------",$response);
		
		foreach($segments as $seg) {
			if(strpos($seg,"name:") === false) {
				continue;
			}
			
			$record = array();
			$lines = explode("\n",$seg);
			
			foreach($lines as $line) {
				if(strpos($line,":") === false) {
					continue;
				}
				
				$idx = strpos($line,":");
				$key = str_replace(" ","_",trim(substr($line,0,$idx)));
				$val = trim(substr($line,$idx+1));
				
				$record[$key] = $val;
			}
			
			$res[] = $record;
		}
		
		return $res;
	}
}

