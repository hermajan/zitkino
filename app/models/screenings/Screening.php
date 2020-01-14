<?php
namespace Zitkino\Screenings;

use Dobine\Entities\Identifier;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zitkino\Cinemas\Cinema;
use Zitkino\Movies\Movie;
use Zitkino\Place;

/**
 * Screening
 *
 * @ORM\Table(name="screenings", indexes={@ORM\Index(name="movie", columns={"movie"}), @ORM\Index(name="cinema", columns={"cinema"}), @ORM\Index(name="type", columns={"type"}), @ORM\Index(name="dubbing", columns={"dubbing"}), @ORM\Index(name="subtitles", columns={"subtitles"})})
 * @ORM\Entity
 */
class Screening {
	use Identifier;
	
	/**
	 * @var Movie
	 * @ORM\ManyToOne(targetEntity="\Zitkino\Movies\Movie")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="movie", referencedColumnName="id", nullable=false)
	 * })
	 */
	protected $movie;
	
	/**
	 * @var Cinema
	 * @ORM\ManyToOne(targetEntity="\Zitkino\Cinemas\Cinema")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="cinema", referencedColumnName="id", nullable=false)
	 * })
	 */
	protected $cinema;
	
	/**
	 * @var ScreeningType|null
	 * @ORM\ManyToOne(targetEntity="ScreeningType")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="type", referencedColumnName="id", nullable=true)
	 * })
	 */
	protected $type;
	
	/**
	 * @var Place|null
	 * @ORM\ManyToOne(targetEntity="\Zitkino\Place")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="place", referencedColumnName="id", nullable=true)
	 * })
	 */
	protected $place;
	
	/**
	 * @var string|null
	 * @ORM\Column(name="dubbing", type="string", length=255, nullable=true)
	 */
	protected $dubbing;
	
	/**
	 * @var string|null
	 * @ORM\Column(name="subtitles", type="string", length=255, nullable=true)
	 */
	protected $subtitles;
	
	/**
	 * @var int|null
	 * @ORM\Column(name="price", type="integer", nullable=true)
	 */
	protected $price;
	
	/**
	 * @var string|null
	 * @ORM\Column(name="link", type="string", length=1000, nullable=true)
	 */
	protected $link;
	
	/**
	 * @var Collection
	 * @ORM\OneToMany(targetEntity="\Zitkino\Screenings\Showtime", mappedBy="screening", cascade={"persist", "remove"})
	 */
	protected $showtimes;
	
	public function __construct(Movie $movie, Cinema $cinema) {
		$this->movie = $movie;
		$this->cinema = $cinema;
		$this->showtimes = new ArrayCollection();
	}
	
	public function __toString() {
		return $this->getMovie()->getId()."-".$this->getCinema()."-".$this->getType()."-".$this->getDubbing()."-".$this->getSubtitles();
	}
	
	/**
	 * @return Movie
	 */
	public function getMovie(): Movie {
		return $this->movie;
	}
	
	/**
	 * @return Cinema
	 */
	public function getCinema(): Cinema {
		return $this->cinema;
	}
	
	/**
	 * @return int|null
	 */
	public function getPrice(): ?int {
		return $this->price;
	}
	
	/**
	 * @param int|string|null $price
	 * @return Screening
	 */
	public function setPrice($price): Screening {
		if(isset($price) and !empty($price)) {
			$this->price = intval($price);
		} else {
			$this->price = null;
		}
		
		return $this;
	}
	
	public function fixPrice() {
		if(!isset($this->price) or !is_numeric($this->price)) {
			return null;
		} else {
			if($this->price == 0) {
				return "zdarma";
			} else {
				return $this->price." Kč";
			}
		}
	}
	
	/**
	 * @return null|string
	 */
	public function getLink(): ?string {
		return $this->link;
	}
	
	/**
	 * @param null|string $link
	 * @return Screening
	 */
	public function setLink(?string $link): Screening {
		$this->link = $link;
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getDubbing() {
		return $this->dubbing;
	}
	
	/**
	 * @param string|null $dubbing
	 * @return Screening
	 */
	public function setDubbing($dubbing): Screening {
		$this->dubbing = $dubbing;
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getSubtitles() {
		return $this->subtitles;
	}
	
	/**
	 * @param string|null $subtitles
	 * @return Screening
	 */
	public function setSubtitles($subtitles): Screening {
		$this->subtitles = $subtitles;
		return $this;
	}
	
	/**
	 * @return ScreeningType|null
	 */
	public function getType(): ?ScreeningType {
		return $this->type;
	}
	
	/**
	 * @param ScreeningType|null $type
	 * @return Screening
	 */
	public function setType(?ScreeningType $type): Screening {
		$this->type = $type;
		return $this;
	}
	
	/**
	 * @return Place|null
	 */
	public function getPlace(): ?Place {
		return $this->place;
	}
	
	/**
	 * @param Place|null $place
	 * @return Screening
	 */
	public function setPlace(?Place $place): Screening {
		$this->place = $place;
		return $this;
	}
	
	/**
	 * @param string|null $dubbing
	 * @param string|null $subtitles
	 * @return Screening
	 */
	public function setLanguages($dubbing, $subtitles): Screening {
		$this->dubbing = $dubbing;
		$this->subtitles = $subtitles;
		return $this;
	}
	
	/**
	 * @return Collection
	 */
	public function getShowtimes(): Collection {
		return $this->showtimes;
	}
	
	/**
	 * @param \DateTime[] $datetimes
	 * @param bool $actual
	 */
	public function setShowtimes($datetimes, $actual = true) {
		foreach($datetimes as $datetime) {
			$showtime = new Showtime($this, $datetime);
			
			if($actual === true) {
				if($showtime->isActual()) {
					$this->addShowtime($showtime);
				}
			} else {
				if($actual === false) {
					$this->addShowtime($showtime);
				}
			}
		}
	}
	
	public function addShowtime($showtime) {
		$this->showtimes[] = $showtime;
	}
}
