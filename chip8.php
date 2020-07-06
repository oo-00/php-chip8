<?php

final class chip8 {
	protected $argv;
	public $sdl;
	public $renderer;
	public $pixels;
	public $pixelf;
	public $event;
	public $timer;
	public $sound;
	public $keypad;
	public $keydown;
	public $keyup;
	public $keymap;
	public $memory = [];
	public $lmemory = [];
	public $V = [];
	public $I = 0;
	public $cpu = 0x200; //cpu starts from 0x200
	public $stack = [];
	public $load = 0;
	public $stime;
	public $close = 0;
	public $time = 0;

	public function __construct($args) {
		$this->stime = time();
		$this->argv = $args;
		$this->sdl = SDL_CreateWindow('PHP Chip 8', SDL_WINDOWPOS_UNDEFINED, SDL_WINDOWPOS_UNDEFINED, 640, 325, SDL_WINDOW_SHOWN);
		$this->renderer = SDL_CreateRenderer($this->sdl, 0, SDL_RENDERER_SOFTWARE);
		$this->event = new SDL_Event;
		$this->pixels = [];
		$this->pixelf = [];
		SDL_SetRenderDrawColor($this->renderer, 0, 0, 0, 255);
		SDL_RenderClear($this->renderer);
		for($i=0;$i<16;$i++) {
			$this->V[$i] = 0;
		}
		$this->timer = 0;
		$this->sound = 0;
		$this->memory =
		[
		  0xF0, 0x90, 0x90, 0x90, 0xF0, // 0
		  0x20, 0x60, 0x20, 0x20, 0x70, // 1
		  0xF0, 0x10, 0xF0, 0x80, 0xF0, // 2
		  0xF0, 0x10, 0xF0, 0x10, 0xF0, // 3
		  0x90, 0x90, 0xF0, 0x10, 0x10, // 4
		  0xF0, 0x80, 0xF0, 0x10, 0xF0, // 5
		  0xF0, 0x80, 0xF0, 0x90, 0xF0, // 6
		  0xF0, 0x10, 0x20, 0x40, 0x40, // 7
		  0xF0, 0x90, 0xF0, 0x90, 0xF0, // 8
		  0xF0, 0x90, 0xF0, 0x10, 0xF0, // 9
		  0xF0, 0x90, 0xF0, 0x90, 0x90, // A
		  0xE0, 0x90, 0xE0, 0x90, 0xE0, // B
		  0xF0, 0x80, 0x80, 0x80, 0xF0, // C
		  0xE0, 0x90, 0x90, 0x90, 0xE0, // D
		  0xF0, 0x80, 0xF0, 0x80, 0xF0, // E
		  0xF0, 0x80, 0xF0, 0x80, 0x80  // F
		];


		$this->keymap =
		[
			"49"=>0x01, "50"=>0x02, "51"=>0x03, "52"=>0x0C,
			"113"=>0x04, "119"=>0x05, "101"=>0x06, "114"=>0x0D,
			"97"=>0x07, "115"=>0x08, "100"=>0x09, "102"=>0x0E,
			"122"=>0x0A, "120"=>0x00, "99"=>0x0B, "118"=>0x0F
		];

	}

	public function run() {
		//system('stty cbreak -echo');
		//$stdin = fopen('php://stdin', 'r');
		$this->loadProgram();
		while(1) {
			$instruction = dechex(($this->memory[$this->cpu] << 8) + $this->memory[$this->cpu + 1]);
		//	echo $instruction;
			$this->step();
	//		$c = ord(fgetc($stdin));
			while( SDL_PollEvent( $this->event ) ){
		    /* We are only worried about SDL_KEYDOWN and SDL_KEYUP events */

		    switch($this->event->type){
		      case SDL_KEYDOWN:
						if(isset($this->keymap[$this->event->key->keysym->sym])) {
		        	$this->keydown = $this->keymap[$this->event->key->keysym->sym];
							$this->keyup = "";
						}
		        break;

		      case SDL_KEYUP:
						$this->keyup = $this->keydown;
						$this->keydown = "";
		        break;
					case 512:
						if($this->event->window->event == 14) {
							die();
						}
						if($this->event->window->event == 15 && $this->close == 1) {
							die();
						}
						break;
		      default:
		        break;
		    }
		  }
			if(time()-$this->stime > 3 && $this->load == 0) {
				$this->load = 1;
				$this->memory =
				[
				  0xF0, 0x90, 0x90, 0x90, 0xF0, // 0
				  0x20, 0x60, 0x20, 0x20, 0x70, // 1
				  0xF0, 0x10, 0xF0, 0x80, 0xF0, // 2
				  0xF0, 0x10, 0xF0, 0x10, 0xF0, // 3
				  0x90, 0x90, 0xF0, 0x10, 0x10, // 4
				  0xF0, 0x80, 0xF0, 0x10, 0xF0, // 5
				  0xF0, 0x80, 0xF0, 0x90, 0xF0, // 6
				  0xF0, 0x10, 0x20, 0x40, 0x40, // 7
				  0xF0, 0x90, 0xF0, 0x90, 0xF0, // 8
				  0xF0, 0x90, 0xF0, 0x10, 0xF0, // 9
				  0xF0, 0x90, 0xF0, 0x90, 0x90, // A
				  0xE0, 0x90, 0xE0, 0x90, 0xE0, // B
				  0xF0, 0x80, 0x80, 0x80, 0xF0, // C
				  0xE0, 0x90, 0x90, 0x90, 0xE0, // D
				  0xF0, 0x80, 0xF0, 0x80, 0xF0, // E
				  0xF0, 0x80, 0xF0, 0x80, 0x80  // F
				];
				$this->timer = 0;
				$this->sound = 0;
				$this->stack = [];
				$this->I = [];
				$this->V = [];
				SDL_SetRenderDrawColor($this->renderer, 0, 0, 0, 255);
				SDL_RenderClear($this->renderer);
				$this->pixels = [];
				$this->pixelf = [];
				$this->cpu = 0x200;
				$this->loadProgram();
			}
		}
	}

	public function loadProgram() {
		if($this->load == 0) {
			$file = "00E0A2486000611E6200D202D21272083240120A6000613E6202A24AD02ED12E720ED02ED12EA258600B6108D01F700AA267D01F700AA276D01F7003A285D01F700AA294D01F1246FFFFC0C0C0C0C0C0C0C0C0C0C0C0C0C0FE818181818181FE8080808080808081818181818181FF81818181818181000080800000008080808080808080FE818181818181FE808080808080807E8181818181817E8181818181817EFF";
			$n = 0;
			$i = 0;
			for($i=0,$max=strlen($file);$i<$max;$i++) {
				$this->memory[0x200 + $n] = hexdec($file[$i].$file[$i+1]);
				$i++;
				$n++;
			}
		} else {
			$file = file_get_contents($this->argv[1]);
			for($i=0,$max=strlen($file);$i<$max;$i++) {
				$this->memory[0x200 + $i] = (ord($file[$i]) & 0xFF);
			}
		}
	}

	public function step() {
		$ctime = floor(microtime(true)*100);
		//echo $ctime; die();
		if($this->timer > 0 && $this->time<$ctime) {
			$this->timer--;
			$this->time = $ctime;
		} else if($this->timer > 0 ){
			//echo $this->timer." > 0 && ".$this->time."<$ctime\n";
			usleep(500);
		}
		if($this->sound > 0 && $this->time<$ctime) {
			$this->sound--;
			$this->time = $ctime;
		} else if($this->sound > 0 ){
			//echo $this->timer." > 0 && ".$this->time."<$ctime\n";
			`aplay beep.wav 2>/dev/null >/dev/null &`;
			$this->sound = 0;
		}

//		$this->keyup = "";
		$instruction = ($this->memory[$this->cpu] << 8) + $this->memory[$this->cpu + 1];
		if($this->load == 1) {
			echo dechex($instruction)." - ".$this->cpu."\n";
		}
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
						SDL_SetRenderDrawColor($this->renderer, 0, 0, 0, 255);
						SDL_RenderClear($this->renderer);
						$this->pixels = [];
						$this->pixelf = [];
						break;
					case 0x00EE:
						// Return from subroutine
						$this->cpu = array_pop($this->stack);
						break;
					default:
						throw new RuntimeException("Unsupported instruction at 0x".dechex($this->cpu)." -> 0x".dechex($instruction));
				}
				break;
			case 0x1000:
				// Jump to nnn
				if($nnn == $this->cpu-2) {
					usleep(50000);
				}
				$this->cpu = $nnn;
				break;
			case 0xB000:
				$this->cpu = $nnn+$this->V[0];
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
				break;
			case 0x4000:
				// skip if vx!=kk
				if($this->V[$x] != $kk) {
					$this->cpu += 2;
				}
				break;
			case 0x6000:
				$this->V[$x] = $kk;
				break;
			case 0x7000:
				$this->V[$x] += $kk;
				while($this->V[$x] > 255) {
					$this->V[$x] -= 256;
				}
				break;
			case 0x8000:
				switch ($instruction & 0xF00F) {
					case 0x8000:
						$this->V[$x] = $this->V[$y];
						break;

					case 0x8001:
						$this->V[$x] = ($this->V[$x] | $this->V[$y]);
						break;
					case 0x8002:
						$this->V[$x] = $this->V[$x] & $this->V[$y];
						break;
					case 0x8003:
						$this->V[$x] = $this->V[$x] ^ $this->V[$y];
						break;
					case 0x8004:
						$this->V[$x] += $this->V[$y];
						if($this->V[$x] > 255) {
							$this->V[$x] -= 256;
							$this->V[15] = 1;
						} else {
							$this->V[15] = 0;
						}
						break;
					case 0x8005:
						$this->V[$x] -= $this->V[$y];
						if($this->V[$x] < 0) {
							$this->V[$x] += 256;
							$this->V[15] = 0;
						} else {
							$this->V[15] = 1;
						}
						break;
					case 0x8007:

						$this->V[$x] = $this->V[$y]-$this->V[$x];
						if($this->V[$x] < 0) {
							$this->V[$x] += 256;
							$this->V[15] = 0;
						} else {
							$this->V[15] = 1;
						}
						break;
					case 0x8006:
					$bin = decbin($this->V[$x]);
						if(substr($bin, -1) == 1) { $this->V[15] = 1; } else { $this->V[15] = 0; }
						$this->V[$x] = $this->V[$x] >> 1;
						break;
					case 0x800E:
					$bin = str_pad(decbin($this->V[$x]), 8, "0", STR_PAD_LEFT);
						if(substr($bin, 0, 1) == 1) { $this->V[15] = 1; } else { $this->V[15] = 0; }
						$this->V[$x] = $this->V[$y] << 1;

						break;
					default:
						throw new RuntimeException("Unsupported instruction at 0x".dechex($this->cpu)." -> 0x".dechex($instruction)." | $instruction");
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
				$this->V[$x] = rand(0,255) & $kk;
				break;
			case 0xD000:
				//display/collision
				$collision = $this->sdlSprite($this->V[$x],$this->V[$y],$this->I,$n);
								SDL_RenderPresent($this->renderer);
				usleep(14000);
				$this->V[0xF] = $collision ? 1 : 0;
				break;
			case 0xE000:
				switch ($instruction & 0xF0FF) {
					case 0xE09E:
						if($this->V[$x] == $this->keydown) { // if VX == keypress
							$this->cpu += 2;
						}
						usleep(20000);
						break;
					case 0xE0A1:
						if($this->V[$x] != $this->keydown) { // if VX != keypress
							$this->cpu += 2;
						}
						//usleep(20000);
						break;
					default:
						throw new RuntimeException("Unsupported instruction at 0x".dechex($this->cpu)." -> 0x".dechex($instruction));
				}
				break;
			case 0xF000:
				switch ($instruction & 0xF0FF) {
					case 0xF00A:
						if($this->keyup == "") {
							$this->cpu -= 2;
							sleep(1);
						} else {
							$this->V[$x] = $this->keyup;
						}
						break;
					case 0xF01E:
						$this->I += $this->V[$x];
						break;
					case 0xF055:
						for($i=0;$i<=$x;$i++) {
							$this->memory[$this->I+$i] = $this->V[$i];
						}
						$this->I = $this->I+$x+1;
						break;
					case 0xF015:
						$this->timer = $this->V[$x];
						break;
					case 0xF007:
						$this->V[$x] = $this->timer;
						break;
					case 0xF018:
						$this->sound = $this->V[$x];
						break;
					case 0xF065:
						for($cv=0;$cv<=$x;$cv++) {
							$this->V[$cv] = $this->memory[$this->I+$cv];
						}
						$this->I += $x+1;
						break;
					case 0xF029:
						$this->I = $this->V[$x]*5;
						//echo dechex($this->memory[$this->I]).",".$this->memory[$this->I+1].",".$this->memory[$this->I+2].",".$this->memory[$this->I+3].",".$this->memory[$this->I+4];
						break;
					case 0xF033:
					//echo $this->V[$x]."\n";
						$this->memory[$this->I] = floor($this->V[$x]/100);
						$this->memory[$this->I+1] = floor($this->V[$x]/10) % 10;
						$this->memory[$this->I+2] = ($this->V[$x] % 100) % 10;
						break;
					default:
						throw new RuntimeException("Unsupported instruction at 0x".dechex($this->cpu)." -> 0x".dechex($instruction)." | $instruction");
				}
				break;
			default:
				throw new RuntimeException("Unsupported instruction at 0x".dechex($this->cpu)." -> 0x".dechex($instruction));
		}
	}

	public function sdlSprite($x, $y, $address, $nbytes) {
		$collision = false;
		for($line=0;$line<$nbytes;$line++) {
		//	echo "$address $line \n";
			$bits = $this->memory[$address + $line]; // get sprite line bits
			$bits = str_pad(base_convert($bits, 10, 2), 8, 0, STR_PAD_LEFT);

			for($bit=0;$bit<8;$bit++) {
				if(isset($bits[$bit])) {
					$tb = $bits[$bit];
				} else {
					$tb = 0;
				}
			//	echo "$tb $x+$bit $y+$line\n";
				//if($bits & 1) {
				if($tb != 0) {

			//		if($this->display->get($x+$bit,$y+$line)) {
					if(isset($this->pixelf[$x+$bit][$y+$line])) {
						if($this->pixelf[$x+$bit][$y+$line] == 1) {
							$col = 1;
						} else {
							$col = 0;
						}
					} else {
						$col = 0;
					}
					if($col == 1) {
						$collision = true;
						SDL_SetRenderDrawColor($this->renderer, 0, 0, 0, 255);
						$this->pixelf[$x+$bit][$y+$line] = 0;
						SDL_RenderFillRect($this->renderer, $this->pixels[$x+$bit][$y+$line]);
						//SDL_RenderPresent($this->renderer);
					} else {
						SDL_SetRenderDrawColor($this->renderer, 255, 255, 255, 255);
						if(!isset($this->pixels[$x+$bit][$y+$line])) {
							$this->pixels[$x+$bit][$y+$line] = new SDL_Rect($x*10+$bit*10, ($y+$line)*10, 10, 10);
						}
						$this->pixelf[$x+$bit][$y+$line] = 1;
						SDL_RenderFillRect($this->renderer, $this->pixels[$x+$bit][$y+$line]);
						//SDL_RenderPresent($this->renderer);
					}
				//	$bits >>= 1;
				}
			}
		//	echo $this->display->frame();
			//
		}
		return $collision;
	}
}

$vm = new chip8($argv);
$vm->run();

?>
