<?php
namespace Zitkino\Parsers;

use Zitkino\Cinemas\Cinema;
use Zitkino\Movies\Screenings;

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
		$this->getContentFromDB(14);
	}
}
