<?php
namespace Zitkino\Parsers;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Zitkino\Cinemas\Cinema;
use Zitkino\Exceptions\ParserException;
use Zitkino\Movies\Movie;
use Zitkino\Place;
use Zitkino\Screenings\Screening;

/**
 * Art parser.
 */
class Art extends Parser {
	/**
	 * Art constructor.
	 * @param ParserService $parserService
	 * @param Cinema $cinema
	 */
	public function __construct(ParserService $parserService, Cinema $cinema) {
		parent::__construct($parserService, $cinema);
		$this->setUrl("https://kinoart.cz/cs/program/");
	}
	
	/**
	 * @throws ParserException
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function parse(): void {
		$xpath = $this->getXpath();
		
		$days = $xpath->query("//div[@class='events-calendar']//div[@class='grid events-calendar__day']");
		foreach($days as $day) {
			$dateQuery = $xpath->query(".//h2[@class='events-calendar__day-date']", $day);
			$dateArray = explode(" ", $dateQuery->item(0)->nodeValue);
			
			$months = [
				1 => "ledna", 2 => "února", 3 => "března", 4 => "dubna", 5 => "května", 6 => "června",
				7 => "července", 8 => "srpna", 9 => "září", 10 => "října", 11 => "listopadu", 12 => "prosince"
			];
			
			$key = array_search(trim($dateArray[1]), $months);
			if($key === false) {
				$month = null;
			} else {
				$month = $key;
			}
			
			$events = $xpath->query(".//div[@class='events-calendar__events']//div[@class='events-calendar__event']", $day);
			foreach($events as $event) {
				$nameQuery = $xpath->query(".//h3[contains(@class, 'events-calendar__event-title')]//a", $event);
				$name = $nameQuery->item(0)->nodeValue;
				
				$link = $nameQuery->item(0)->getAttribute("href");
				
				$placeQuery = $xpath->query(".//p[@class='events-calendar__event-time--desktop']//a[@class='boxed boxed--custom']", $event);
				$placeName = $placeQuery->item(0)->nodeValue;
				$placeLink = $placeQuery->item(0)->getAttribute("href");
				
				if(isset($month)) {
					$timeQuery = $xpath->query(".//p[@class='events-calendar__event-time--desktop']//a[@class='boxed boxed--black']", $event);
					$time = $timeQuery->item(0)->nodeValue;
					
					$datetime = \DateTime::createFromFormat("j.m H:i", trim($dateArray[0].$month." ".$time));
					$datetimes = [$datetime];
				} else {
					$datetimes = null;
				}
				
				$languagesQuery = $xpath->query(".//div[@class='credits__event-movie-languages']//a", $event);
				$dubbing = null;
				$dubbingLanguages = [];
				$subtitles = null;
				switch($languagesQuery->length) {
					case 0:
						$dubbing = null;
						break;
					case 1:
						$dubbing = $languagesQuery->item(0)->nodeValue;
						break;
					default:
						for($i = 0; $i < $languagesQuery->length; $i++) {
							if($i == $languagesQuery->length - 1) {
								$subtitles = $languagesQuery->item($i)->nodeValue;
								break;
							}
							
							$dubbingLanguages[] = $languagesQuery->item($i)->nodeValue;
						}
						
						$dubbing = implode(", ", $dubbingLanguages);
						break;
				}
				
				$lengthQuery = $xpath->query(".//div[@class='credits__countries-year']//p[@class='credits__duration']", $event);
				$lengthString = $lengthQuery->item(0)->nodeValue ?? null;
				$length = $lengthString ? str_replace("min", "", intval($lengthString)) : null;
				
				$movie = $this->parserService->getMovieFacade()->getByName($name);
				if(!isset($movie)) {
					$movie = new Movie($name);
					$movie->setLength($length);
					$this->parserService->getMovieFacade()->save($movie);
				}
				
				$place = $this->parserService->getPlaceFacade()->getByName($placeName);
				if(!isset($place)) {
					$place = new Place($placeName);
					$place->setCinema($this->cinema);
				}
				$place->setLink($placeLink);
				$this->parserService->getEntityManager()->persist($place);
				$this->parserService->getEntityManager()->flush($place);
				
				$screening = new Screening($movie, $this->cinema);
				$screening->setPlace($place);
				$screening->setLanguages($dubbing, $subtitles);
				$screening->setLink($link);
				$screening->setShowtimes($datetimes);
				
				$this->parserService->getEntityManager()->persist($screening);
				$this->cinema->addScreening($screening);
			}
		}
		
		$this->cinema->setParsed(new \DateTime());
		$this->parserService->getEntityManager()->persist($this->cinema);
		$this->parserService->getEntityManager()->flush();
	}
}
