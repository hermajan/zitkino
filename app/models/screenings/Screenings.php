<?php
namespace Zitkino\Screenings;

use Doctrine\Common\Collections\ArrayCollection;
use Zitkino\Movies\Movies;

class Screenings extends ArrayCollection {
	public function __construct($screenings) {
		if(!isset($screenings)) {
			$screenings = [];
		}
		
		parent::__construct($screenings);
	}
	

	public function getMovies() {
		$movies = [];
		
		/** @var Screening $screening */
		foreach($this->toArray() as $screening) {
			$movies[] = $screening->movie;
		}
		
		return new Movies($movies);
	}
	
	public function hasTypes() {
		foreach($this->toArray() as $screening) {
			$type = $screening->type;
			if(isset($type) and $type !== "2D") { return true; }
		}
		return false;
	}
	
	public function hasLanguages() {
		foreach($this->toArray() as $screening) {
			$dubbing = $screening->dubbing;
			$subtitles = $screening->subtitles;
			if(isset($dubbing) or isset($subtitles)) { return true; }
		}
		return false;
	}
	
	public function hasPrices() {
		foreach($this->toArray() as $screening) {
			$price = $screening->price;
			if(isset($price)) { return true; }
		}
		return false;
	}
}
