<?php

require __DIR__ . '/vendor/autoload.php';
final class chip8 {
	protected $argv;
	public $display;
	public $memory = [];
	public $V = [];
	public $I = 0;
	public $cpu = 0x200; //cpu starts from 0x200
	public $stack = [];

	public function __construct($args) {
		$this->argv = $args;
		$this->display = new \Drawille\Canvas();
	}

	public function run() {
		$this->loadProgram();
		while(1) {
			$this->step();
		}
	}

	public function loadProgram() {
		$file = file_get_contents($this->argv[1]);
		for($i=0,$max=strlen($file);$i<$max;$i++) {
			$this->memory[0x200 + $i] = (ord($file[$i]) & 0xFF);
		}
	}

	public function step() {
		$instruction = ($this->memory[$this->cpu] << 8) + $this->memory[$this->cpu + 1];
		$this->cpu += 2;

		// Extract common operands
		$x = ($instruction & 0x0F00) >> 8;
		$y = ($instruction & 0x00F0) >> 4;
		$kk = $instruction & 0x00FF;
		$nnn = $instruction & 0x0FFF;
		$n = $instruction & 0x000F;

		switch ($instruction & 0xF000) {
			case 0x0000:
				switch ($instruction) {
					case 0x00E0:
						// Clear display dummy operand
						break;
					case 0x00EE:
						// Return from subroutine
						$this->cpu = array_pop($this->stack);
						break;
				}
				break;
			case 0x1000:
				// Jump to nnn
				$this->cpu = $nnn;
				break;
			case 0xB000:
				$this->cpu = $nnn+$this-V[0];
			case 0x2000:
				//call subr at nnn
				$this->stack[] = $this->cpu;
				$this->cpu = $nnn;
				break;
			case 0x3000:
				// skip if vx=kk
				if($this->V[$x] === $kk) {
					$this->cpu += 2; // instr is 3 bytes
				}
				break;
			case 0x5000:
				if($this->V[$x] == $this->V[$y]) {
					$this->cpu += 2;
				}
			case 0x4000:
				// skip if vx!=kk
				if($this->V[$x] !== $kk) {
					$this->cpu += 2;
				}
				break;
			case 0x6000:
				$this->V[$x] = $kk;
				break;
			case 0x7000:
				$this->V[$x] += $kk;
				break;
			case 0x8000:
				switch ($instruction) {
					case 0x8004:
						$this->V[$x] += $this->V[$y];
						break;
					case 0x8001:
						$this->V[$x] = $this->V[$x] || $this-V[$y];
						break;
					case 0x8002:
						$this->V[$x] = $this->V[$x] && $this-V[$y];
						break;
					case 0x8003:
						$this->V[$x] = $this->V[$x] XOR $this-V[$y];
						break;
					default:
						$this->V[$x] = $this->V[$y];
						break;
				}
				break;
			case 0x9000:
				if($this->V[$x] !== $this->V[$y]) {
					$this->cpu += 2;
				}
				break;
			case 0xA000:
				$this->I = $nnn;
				break;
			case 0xC000:
				$this->V[$x] = (rand(1,5) * (0xFF+1)) & $kk;
				break;
			case 0xD000:
				//display/collision
				$collision = $this->drawSprite($this->V[$x], $this->V[$y], $this->I, $n);
				$this->V[0xF] = $collision ? 1 : 0;
				break;
			case 0xF000:
				switch ($instruction & 0xF0FF) {
					case 0xF01E:
						$this->I += $this->V[$x];
						break;
					case 0xF055:
						$this->V[$x] = $this->V[0];
						break;

				}
				break;
			default:
				throw new RuntimeException("Unsupported instruction at 0x".dechex($this->cpu)." -> 0x".dechex($instruction));
		}
	}

	public function drawSprite($x, $y, $address, $nbytes) {
		$collision = false;
		for($line=0;$line<$nbytes;$line++) {
			$bits = $this->memory[$address + $line]; // get sprite line bits

			system("clear");

			for($bit=7;$bit>=0;$bit--) {
				if($bits & 1) {
					if($this->display->get($x+$bit,$y+$line)) {
						$collision = true;
					} else {
						$this->display->set($x+$bit,$y+$line);
					}
					$bits >>= 1;
				}
			}
			echo "\n".$this->display->frame();
			return $collision;
		}
	}
}

$vm = new chip8($argv);
$vm->run();

//var_dump($vm->memory);

?>
