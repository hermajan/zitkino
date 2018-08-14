<?php
namespace Zitkino\Parsers;

use Zitkino\Movies\Movie;
use Zitkino\Movies\Screening;
use Zitkino\Movies\Showtime;

/**
 * Rubin parser.
 */
class Rubin extends Parser {
	public function __construct() {
		$this->setUrl("http://www.kdrubin.cz/program");
		$this->initiateDocument();
		
		$movies = $this->parse();
		$this->setMovies($movies);
	}

	/**
	 * @return Movie[]
	 * @throws \Exception
	 */
	public function parse() {
		$xpath = $this->downloadData();
		
		$events = $xpath->query("//div[@id='itemListLeading']//div[@class='K2ItemsRow']");
		foreach($events as $event) {
			$nameQuery = $xpath->query(".//h2[@class='tagItemTitle']/a", $event);
			$nameString = $nameQuery->item(0)->nodeValue;
			
			if(strpos($nameString, "Letní kino") !== false) {
				$nameArray = explode(" - ", trim($nameString));
				$name = $nameArray[1];
				
				$linkString = $nameQuery->item(0)->getAttribute("href");
				$link = "http://www.kdrubin.cz".$linkString;
				
				$datetimeQuery = $xpath->query(".//span[@class='catItemDateCreated']", $event);
				$datetimeString = $datetimeQuery->item(0)->nodeValue;
				$dateArray = explode(",", $datetimeString);
				
				$months = ["červenec", "červen", "srpen", "září"];
				$monthsNumbers = [7, 6, 8, 9];
				$date = trim(str_replace($months, $monthsNumbers, $dateArray[1]));
				
				$time = trim($dateArray[2]);
				$datetime = \DateTime::createFromFormat("d. m Y H:i", $date.$time);
				
				$movie = new Movie($name);
				$screening = new Screening();
				$screening->setLink($link);
				
				$showtime = new Showtime();
				$showtime->setDatetime($datetime);
				$screening->addShowtime($showtime);
				
				$movie->addScreening($screening);
				$this->movies[] = $movie;
			}
		}
		
		return $this->movies;
	}
}
