<?php
namespace Zitkino\Parsers;

use Zitkino\Cinemas\Cinema;
use Zitkino\Screenings\Screenings;

/**
 * Turany parser.
 */
class Turany extends Parser {
	public function __construct(Cinema $cinema) {
		$this->cinema = $cinema;
		$this->getConnection();
		$this->parse();
	}
	
	public function parse(): Screenings {
		return $this->getContentFromDB(14);
	}
}