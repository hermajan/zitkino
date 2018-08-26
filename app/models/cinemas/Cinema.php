<?php
namespace Zitkino\Cinemas;

use Dobine\Entities\DobineEntity;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\MagicAccessors;
use Zitkino\Parsers\Parser;
use Zitkino\Screenings\Screenings;
use Zitkino\Screenings\Showtime;

/**
 * Cinema
 *
 * @ORM\Table(name="cinemas", uniqueConstraints={@ORM\UniqueConstraint(name="id", columns={"id"})}, indexes={@ORM\Index(name="type", columns={"type"})})
 * @ORM\Entity
 */
class Cinema extends DobineEntity {
	use MagicAccessors;
	
	/**
	 * @var string
	 * @ORM\Column(name="name", type="string", length=255, nullable=false)
	 */
	protected $name;
	
	/**
	 * @var string
	 * @ORM\Column(name="short_name", type="string", length=20, nullable=false)
	 */
	protected $shortName;
	
	/**
	 * @var CinemaType
	 * @ORM\ManyToOne(targetEntity="CinemaType")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="type", referencedColumnName="id")
	 * })
	 */
	protected $type;
	
	/**
	 * @var string|null
	 * @ORM\Column(name="address", type="string", length=255, nullable=true)
	 */
	protected $address;
	
	/**
	 * @var string
	 * @ORM\Column(name="city", type="string", length=255, nullable=false, options={"default"="Brno"})
	 */
	protected $city = 'Brno';
	
	/**
	 * @var string|null
	 * @ORM\Column(name="phone", type="string", length=100, nullable=true)
	 */
	protected $phone;
	
	/**
	 * @var string|null
	 * @ORM\Column(name="email", type="string", length=255, nullable=true)
	 */
	protected $email;
	
	/**
	 * @var string|null
	 * @ORM\Column(name="url", type="string", length=1000, nullable=true)
	 */
	protected $url;
	
	/**
	 * @var string|null
	 * @ORM\Column(name="gmaps", type="string", length=1000, nullable=true)
	 */
	protected $gmaps;
	
	/**
	 * @var string|null
	 * @ORM\Column(name="programme", type="string", length=255, nullable=true)
	 */
	protected $programme;
	
	/**
	 * @var string|null
	 * @ORM\Column(name="facebook", type="string", length=255, nullable=true)
	 */
	protected $facebook;
	
	/**
	 * @var string|null
	 * @ORM\Column(name="googlePlus", type="string", length=255, nullable=true)
	 */
	protected $googlePlus;
	
	/**
	 * @var string|null
	 * @ORM\Column(name="instagram", type="string", length=255, nullable=true)
	 */
	protected $instagram;
	
	/**
	 * @var string|null
	 * @ORM\Column(name="twitter", type="string", length=255, nullable=true)
	 */
	protected $twitter;
	
	/**
	 * @var \DateTime|null
	 * @ORM\Column(name="active_since", type="date", nullable=true)
	 */
	protected $activeSince;
	
	/**
	 * @var \DateTime|null
	 * @ORM\Column(name="active_until", type="date", nullable=true)
	 */
	protected $activeUntil;
	
	
	/** @var Screenings */
	public $screenings;
	
	
	public function getScreenings() {
		return $this->screenings;
	}
	
	public function setScreenings() {
		try {
			$parserClass = "\Zitkino\Parsers\\".ucfirst($this->shortName);
			if(class_exists($parserClass)) {
				/** @var Parser $parser */
				$parser = new $parserClass($this);
				
				$this->screenings = $parser->getScreenings();
//				\Tracy\Debugger::barDump([$parser, $this->screenings]);
//				\Tracy\Debugger::barDump($films);
//				$s = [];
				if(isset($this->screenings)) {
//					/** @var Screening $screening */
//					foreach($this->screenings as $screening) {
//////						\Tracy\Debugger::barDump($film);
//						foreach ($screening->getShowtimes() as $showtime) {
//							if($this->checkActualMovie($showtime)) {
//								$s[] = $screening;
//							}
//						}
//					}
//					\Tracy\Debugger::barDump($s);
				}
			} else { $this->screenings = null; }
		} catch(\Error $error) {
			\Tracy\Debugger::barDump($error);
			\Tracy\Debugger::log($error, \Tracy\Debugger::ERROR);
		} catch(\Exception $exception) {
			\Tracy\Debugger::barDump($exception);
			\Tracy\Debugger::log($exception, \Tracy\Debugger::EXCEPTION);
		}
	}
	
	public function hasScreenings() {
		if(isset($this->screenings) and !empty($this->screenings->toArray())) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getSoonestScreenings() {
//		return $this->screenings;
		
		$soonest = [];
		if(isset($this->screenings)) {
			$currentDate = new \DateTime();
			
			foreach($this->screenings as $screening) {
				$nextDate = new \DateTime();
				$nextDate->modify("+1 days");
				
				$showtimes = $screening->getShowtimes();
				if(isset($showtimes)) {
					/** @var Showtime $showtime */
					foreach($showtimes as $showtime) {
						// checks if movie is played from now to +1 day
						if($currentDate < $showtime->getDatetime() and $showtime->getDatetime() < $nextDate) {
							$soonest[] = $screening;
							break;
						}
					}
				}
			}
			\Tracy\Debugger::barDump($soonest);
			
			if(count($soonest) < 5) {
				$soonest = [];
				for($i=0; $i<count($this->screenings->toArray()); $i++) {
					if(isset($this->screenings[$i])) {
						foreach($this->screenings[$i]->getShowtimes() as $showtime) {
							if($currentDate < $showtime->getDatetime()) {
								$soonest[] = $this->screenings[$i];
							}
						}
					}
					
					if(count($soonest) == 5) {
						break;
					}
				}
			}
		}
		
//		if(empty($soonest)) {
//			if(is_null($this->screenings) or empty($this->screenings->toArray())) {
//				$soonest = [];
//			} else {
//				if($this->screenings[0]->getShowtimes()[0]->isActual()) {
//					$soonest = [$this->screenings[0]];
//				} else {
//					$soonest = [];
//				}
//			}
//		}
		
		return new Screenings($soonest);
	}
}
