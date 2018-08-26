<?php
namespace Zitkino\Parsers;

use Zitkino\Cinemas\Cinema;
use Zitkino\Movies\Movie;
use Zitkino\Screenings\Screening;
use Zitkino\Screenings\Screenings;

/**
 * Kinokavarna parser.
 */
class Kinokavarna extends Parser {
	public function __construct(Cinema $cinema) {
		$this->cinema = $cinema;
		$this->setUrl("http://www.kinokavarna.cz/program.html");
		$this->initiateDocument();
		
		$this->parse();
	}
	
	public function parse(): Screenings {
		$xpath = $this->downloadData();
		
		$events = $xpath->query("//div[@id='content-in']/div[@class='aktuality']");
		foreach($events as $event) {
			$nameQuery = $xpath->query(".//h4", $event);
			$nameString = $nameQuery->item(0)->nodeValue;
			
			$dateQuery = $xpath->query(".//h4//span", $event);
			$date = $dateQuery->item(0)->nodeValue;
			
			$timeQuery = $xpath->query(".//p[@class='start']", $event);
			$timeReplacing = ["Začátek: ", "od "];
			$timeString = str_replace($timeReplacing, "", $timeQuery->item(0)->nodeValue);
			$time = str_replace(".", ":", mb_substr($timeString, 0, 5));
			
			$name = mb_substr($nameString, strlen($date));
			$badNames = ["", "ZAVŘENO", "Zavřeno", "STÁTNÍ SVÁTEK- ZAVŘENO"];
			if(($time == " ") and (in_array($name, $badNames) or (strpos($name, "OTEVÍRACÍ DOBA-") !== false))) {
				continue;
			}
			
			$link = "http://www.kinokavarna.cz/program.html";
			
			$infoQuery = $xpath->query(".//p[2]", $event);
			$info = explode(",", $infoQuery->item(0)->nodeValue);
			
			if(isset($info[3])) {
				$length = (int)str_replace(" min.", "", $info[3]);
			} else {
				$length = null;
			}
			
			$dubbing = null;
			$subtitles = null;
			foreach($infoQuery as $lang) {
				if(strpos($lang->nodeValue, ", ČR,") !== false) {
					$dubbing = "česky";
					break;
				}
				if(strpos($lang->nodeValue, "čes. tit") !== false) {
					$subtitles = "české";
					break;
				}
			}
			
			$datetimes = [];
			$datetime = \DateTime::createFromFormat("j.n.Y", $date);
			$datetime->setTime(intval(substr($time, 0, 2)), intval(substr($time, 3, 2)));
			$datetimes[] = $datetime;
			
			$priceString = mb_substr($timeString, 6, 11);
			if(!empty($priceString)) {
				$price = (int)str_replace("Vstupné: ", "", $priceString);
			} else {
				$price = null;
			}
			
			
			$movie = new Movie($name);
			$movie->setLength($length);
			
			$screening = new Screening($movie, $this->cinema);
			$screening->setLanguages($dubbing, $subtitles);
			$screening->setPrice($price);
			$screening->setLink($link);
			$screening->setShowtimes($datetimes);
			
			$this->screenings[] = $screening;
		}
		
		$this->setScreenings($this->screenings);
		return new Screenings($this->screenings);
	}
}
