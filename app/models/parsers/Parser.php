<?php
namespace Zitkino\Parsers;

use Doctrine\DBAL\Connection;
use DOMDocument, DOMXPath;
use Zitkino\Cinemas\Cinema;
use Zitkino\Exceptions\ParserException;
use Zitkino\Movies\Movie;
use Zitkino\Screenings\Screening;
use Zitkino\Screenings\Screenings;
use Zitkino\Screenings\ScreeningType;

/**
 * Parser.
 */
abstract class Parser {
	/** @var Cinema */
	protected $cinema;
	
	private $url = "";
	
	/** @var \DOMDocument */
	private $document;
	
	/** @var Screenings|array */
	protected $screenings;
	
	/** @var Connection */
	protected $connection;
	
	
	public function getUrl() {
		return $this->url;
	}
	public function getScreenings() {
		if(is_array($this->screenings)) {
			return new Screenings($this->screenings);
		}
		
		return $this->screenings;
	}
	
	public function setUrl($url) {
		$this->url = $url;
	}


	/**
	 * @param Screenings|array $screenings
	 * @return Parser
	 */
	public function setScreenings($screenings) {
		if(!isset($screenings)) {
			$screenings = [];
		}
		
		if(is_array($screenings)) {
			$this->screenings = new Screenings($screenings);
		} else {
			$this->screenings = $screenings;	
		}
		
		return $this;
	}
	
	/**
	 * Initiates DOM document.
	 */
	public function initiateDocument() {
		$this->document = new DOMDocument("1.0", "UTF-8");
		$this->document->formatOutput = true;
		$this->document->preserveWhiteSpace = true;
	}
	
	/**
	 * Downloads data from internet.
	 * @return DOMXPath XPath document for parsing.
	 * @throws \Exception
	 */
	public function downloadData() {
		$handle = curl_init($this->url);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_ENCODING, "UTF-8");
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        
		$html = curl_exec($handle);
		if($html === false) {
			$e = new ParserException(curl_error($handle));
			$e->setUrl($this->getUrl());
			throw $e;
		}
		
		curl_close($handle);
		
		libxml_use_internal_errors(true); // Prevent HTML errors from displaying
		$this->document->loadHTML(mb_convert_encoding($html, "HTML-ENTITIES", "UTF-8"));
		$xpath = new DOMXPath($this->document);
		
		return $xpath;
	}
	
	public function getConnection() {
		$db = new \Lib\database\Doctrine(__DIR__."/../../config/database.ini");
		$this->connection = $db->getConnection();
	}

	/**
	 * Gets movies and other data from the web page.
	 * @return Screenings Collection of screenings.
	 */
	abstract public function parse(): Screenings;
	
	public function getContentFromDB($cinema) {
		$today = date("Y-m-d", strtotime("now"));
		$events = $this->connection->fetchAll("
			SELECT s.*, m.*, l.czech AS dubbing, ls.czech AS subtitles, stype.name as type, st.datetime FROM screenings AS s
			LEFT JOIN movies AS m ON s.movie = m.id
			LEFT JOIN languages AS l ON s.dubbing = l.id
			LEFT JOIN languages AS ls ON s.subtitles = ls.id
			LEFT JOIN screenings_types AS stype ON stype.id = s.type
			LEFT JOIN showtimes AS st ON st.screening = s.id
			WHERE s.cinema = ? AND st.datetime >= ?",
			[$cinema, $today]
		);
		
		foreach($events as $event) {
			$datetimes = [];
			$datetime = \DateTime::createFromFormat("Y-m-d H:i:s", $event["datetime"]);
			$datetimes[] = $datetime;
			
			$movie = new Movie($event["name"]);
			$movie->setLength($event["length"]);
			$movie->setCsfd($event["csfd"]);
			$movie->setImdb($event["imdb"]);
			
			$screening = new Screening($movie, $this->cinema);
			$screening->setType(new ScreeningType($event["type"]));
			$screening->setLanguages($event["dubbing"], $event["subtitles"]);
			$screening->setPrice($event["price"]);
			$screening->setLink($event["link"]);
			$screening->setShowtimes($datetimes);
			
			$movie->addScreening($screening);
			$this->screenings[] = $screening;
		}
		
		$this->setScreenings($this->screenings);
		return $this->screenings;
	}
}