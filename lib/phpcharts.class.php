<?php

	class SERIES {
		const TYPE = 'line|spline|bar|squareline|area';
	}

	class chartSkins {
		
		public $name;
		public $plotStyle;
		public $lineStyle;
		public $areaStyle;
		
		public function __construct($name, $style){
			$this->name = $name;
			if(isset($style['plot'])) $this->plotStyle = $style['plot']; 
			if(isset($style['line'])) $this->lineStyle = $style['line'];
			if(isset($style['area'])) $this->areaStyle = $style['area'];
		}
		
		public function get(){
			print_r(get_defined_vars());	
		}
	}
	
	class chartSeries {
		
		public $name;
		public $datas;
		public $type;
		public $js;
		public $plotStyle;
		public $lineStyle;
		public $areaStyle;
		
		public function __construct($name, $datas){
			$this->name = $name;
			$this->datas = $datas;
		}
		
		public function __call($name, $args){
			if(in_array(strtolower($name), explode('|', SERIES::TYPE))) $this->setType(strtolower($name));
			return $this;
		}
		
		public function css(array $style){
			if(isset($style['plot'])) $this->plotStyle = $style['plot']; 
			if(isset($style['line'])) $this->lineStyle = $style['line'];
			if(isset($style['area'])) $this->areaStyle = $style['area'];
			return $this;
		}
		
		public function skin(/* mixed */ $name){
			if(is_object($name)){
				if(isset($name->plotStyle)) $this->plotStyle = $name->plotStyle; 
				if(isset($name->lineStyle)) $this->lineStyle = $name->lineStyle;
				if(isset($name->areaStyle)) $this->areaStyle = $name->areaStyle;
			} return $this;
		}
		
		// JS INTERACTION
		public function onHover(){
			$this->js = true;
		}
		
		public function getType(){ return $this->type; }
		public function setType($type){ $this->type = $type; }
		public function getName() { return $this->name; }
		public function setName($name) { $this->name = $name; }
		public function getDatas() { return $this->data; }
		public function setDatas($datas) { $this->data = $datas; }
		public function addData($data) { return $this->data[] = $data; }
	}

	class chart {
		
		public $name;
		public $series;
		public $skins;
		
		private $grid = array();
		private $css  = array();
		private $svg  = array();

		public function __construct($name, $callback){
			$this->name = ($name!=null) ? $name : $this->suniqid(5, '_');
			if(is_callable($callback)) $callback($this);
		}
		
		final private function __draw(){
			$width = ($this->css['width']) ? : 920;
			$height = ($this->css['height']) ? : 400;
			
			$this->svg[] = "<svg name='".$this->name."' width='".$width."px' height='".$height."px' class='charts' rel='charts'>";
			$this->svg[] = "<g name='".$this->name."_grid' rel='grid'>";
			$maxValue = ($this->getMax()!=null) ? $this->getMax() : $this->getMaxValue();
			$maxLength = $this->getMaxLength();
			$yDistance = $this->getYdistance($maxLength);
			$xDistance = $this->getXdistance($maxValue);
			$ratio = $this->getRatio($maxValue);
					
			$visibility = (isset($this->grid['visibility'])) ? $this->grid['visibility'] : 'visible';
					
			for($i=1;$i<($maxValue/10);$i++){
				$this->svg[] = "<path d='M 0 ".($i*$xDistance)." L ".($width)." ".($i*$xDistance)."' stroke-width='1' fill='none' stroke='#191e20' shape-rendering='crispEdges' name='".$this->name."_gridhline_{$i}' visibility='".$visibility."'></path>";
			}
			
			for($i=1;$i<$maxLength;$i++){
				$this->svg[] = "<path d='M ".($i*$yDistance)." 0 L ".($i*$yDistance)." ".($height)."' stroke-width='1' fill='none' stroke='#000' shape-rendering='crispEdges' name='".$this->name."_gridvline_{$i}' visibility='hidden'></path>";
			}
			$this->svg[] = "</g>";
			
			$i = 0;
			foreach($this->series as $key => $serie){
				$this->svg[] = "<g name='".$this->name."_serie_".$serie->name."' rel='serie'>";
				list($plot, $gplot) = $this->placePlot($serie->datas, $serie->plotStyle, $yDistance, $ratio);
				$this->svg[] = $this->placeArea($plot, $serie->type, $serie->areaStyle);
				$this->svg[] = $this->placeLine($plot, $serie->type, $serie->lineStyle);
				$this->svg[] = implode('', $gplot);
				$this->svg[] = "</g>";
			}
			
			$this->svg[] = "</svg>";
			return implode('', $this->svg);
		}
		
		public function __toString(){
			return $this->__draw();
		}
		
		public function draw(){
			echo $this->__draw();
		}
		
		public function setStyle(array $opt){
			$this->css = $opt;	
		}
		
		public function setGrid(array $opt){
			$this->grid = $opt;
		}	
		
		public function setMax($n){
			$this->grid['max'] = $n;
		}
		
		public function getMax(){
			return (isset($this->grid['max'])) ? $this->grid['max'] : null;
		}
		
		public function setDistance($n){
			$this->grid['distance'] = $n;
			return $this;
		}
		
		public function getDistance(){
			return (isset($this->grid['distance'])) ? $this->grid['distance'] : 10;
		}

		/*mixed, [mixed]*/
		public function serie(){
			$n_args = func_num_args();
			$args = func_get_args();
			
			if($n_args==2) {
				if(is_string($args[0]) && is_array($args[1])) return ($this->series[$args[0]] = new chartSeries($args[0], $args[1]));
			} else {
				if(is_string($args[0])) return $this->addStringSingleSerie($args[0]);
			}
		}
		
		/* string, array */
		public function skin($name, array $css){
			return ($this->skins[$name] = new chartSkins($name, $css));
		}
		
		private function addStringSingleSerie($serie){
			if(preg_match('/(.*)\:\[([0-9\, ]+)\]/i', $serie, $m)){
				$name = $m[1];
				$datas = explode(',', str_replace(' ', '', $m[2]));
				return ($this->series[$name] = new chartSeries($name, $datas));
			} else if(preg_match('/\[([0-9\, ]+)\]/i', $serie, $m)){
				$name = count($this->series);
				$datas = explode(',', str_replace(' ', '', $m[1]));
				return ($this->series[$name] = new chartSeries($name, $datas));
			}
		}
		
		private function placePlot($datas, $style, $y, $rt){
			$stroke = (isset($style['stroke'])) ? $style['stroke'] : '#000';
			$strokeWidth = (isset($style['stroke-width'])) ? $style['stroke-width'] : '2';
			$fill = (isset($style['fill'])) ? $style['fill'] : '#000';
			$r = (isset($style['r'])) ? $style['r'] : '6';
			$visibility = (isset($style['visibility'])) ? $style['visibility'] : 'visible';
			
			$height = ($this->css['height']) ? : 400;

			$i = 0; $plot = $svg = array();
			foreach($datas as $data){
				$posx = ($y-($y/2))+($i*$y);
				$posy = (($height)-($data))+($data*$rt);
				$plot[] = array('x'=>$posx, 'y'=>$posy);
				$svg[]  = "<circle cx='".$posx."' cy='".$posy."' r='".$r."' stroke='".$stroke."' stroke-width='".$strokeWidth."' fill='".$fill."' visibility='".$visibility."' rel='seriepoint'></circle>";
			$i++;
			}
			return array($plot, $svg);
		}
		
		private function getMaxValue(){
			$all = array();
			
			if(is_array($this->series)){
				foreach($this->series as $series){
					foreach($series->datas as $data){
						$all[] = $data;
					}
				}
			} return (count($all)>0) ? (max($all)+$this->getDistance()) : 0;
		}
		
		private function getMaxLength(){
			$all = array();
			
			if(is_array($this->series)){
				foreach($this->series as $series){
					$all[] = count($series->datas);
				} 
			} return (count($all)>0) ? max($all) : 0;
		}
		
		private function getRatio($max){
			$height = ($this->css['height']) ? : 400;
			return (1-(($height)/$max));
		}
		
		private function getYdistance($max){
			$width = ($this->css['width']) ? : 920;
			return ($width/$max);
		}
		
		private function getXdistance($max){
			$height = ($this->css['height']) ? : 400;
			while(($max%$this->getDistance())!=0){ $max++; }
			return ($height/($max/$this->getDistance()));	
		}
		
		private function placeLine($plots, $type, $style){
			if($type=='line') return $this->getLine($plots, $style);
			if($type=='spline') return $this->getSpline($plots, $style);
		}
		
		private function placeArea($plots, $type, $style){
			if($type=='line') return $this->getLineArea($plots, $style);
			if($type=='spline') return $this->getSplineArea($plots, $style);
		}
		
		private function getLineArea($plots, $style) {
			$stroke = (isset($style['stroke'])) ? $style['stroke'] : '#000';
			$strokeWidth = (isset($style['stroke-width'])) ? $style['stroke-width'] : '0';
			$fill = (isset($style['fill'])) ? $style['fill'] : '#000';
			$r = (isset($style['r'])) ? $style['r'] : '6';
			$visibility = (isset($style['visibility'])) ? $style['visibility'] : 'visible';
			$opacity = (isset($style['opacity'])) ? $style['opacity'] : '0';
			
			$i = 0; $d = false;
			foreach($plots as $plot){
				if(($i++)==0) $d = 'M'.$plot['x'].','.$plot['y'];
				else $d = $d.' L'.$plot['x'].','.$plot['y'];
			} 
			
			$d = str_replace('M', 'L', $d);
			$d = "M ".$plots[0]['x'].",".$this->css['height']." ".$d;
			$d = $d." L".$plots[count($plots)-1]['x'].",".$this->css['height']."";
			return "<path d='".$d."' stroke='".$stroke."' stroke-width='".$strokeWidth."' fill='".$fill."' fill-opacity='".$opacity."'></path>";
		}
		
		private function getSplineArea($plots, $style){
			$stroke = (isset($style['stroke'])) ? $style['stroke'] : '#000';
			$strokeWidth = (isset($style['stroke-width'])) ? $style['stroke-width'] : '0';
			$fill = (isset($style['fill'])) ? $style['fill'] : '#000';
			$r = (isset($style['r'])) ? $style['r'] : '6';
			$visibility = (isset($style['visibility'])) ? $style['visibility'] : 'visible';
			$opacity = (isset($style['opacity'])) ? $style['opacity'] : '0';
		
			$d = false; $j = 0;
			for($i=0;$i<count($plots);$i++){
				if($i==0) {
					$mx = (($plots[$i]['x']-$plots[$i+1]['x'])/2)+$plots[$i+1]['x'];
					$d  = "M".$plots[$i]['x'].",".$plots[$i]['y']." C".($mx).",".$plots[$i]['y']." ".($mx).",".$plots[$i+1]['y']." ".$plots[$i+1]['x'].",".$plots[$i+1]['y']."";
				} else {
					if(isset($plots[$i+1])){
						$mx = (($plots[$i]['x']-$plots[$i+1]['x'])/2)+$plots[$i+1]['x'];
						$d = $d." C".($mx).",".$plots[$i]['y']." ".($mx).",".$plots[$i+1]['y']." ".$plots[$i+1]['x'].",".$plots[$i+1]['y']."";
					}
				} $j++;
			}
			
			$d = str_replace('M', 'L', $d);
			$d = "M ".$plots[0]['x'].",".$this->css['height']." ".$d;
			$d = $d." L".$plots[count($plots)-1]['x'].",".$this->css['height']."";
			return "<path d='".$d."' stroke='".$stroke."' stroke-width='".$strokeWidth."' fill='".$fill."' fill-opacity='".$opacity."'></path>";
		}
		
		private function getLine($plots, $style) {
			$stroke = (isset($style['stroke'])) ? $style['stroke'] : '#000';
			$strokeWidth = (isset($style['stroke-width'])) ? $style['stroke-width'] : '2';
			$fill = (isset($style['fill'])) ? $style['fill'] : '#000';
			$r = (isset($style['r'])) ? $style['r'] : '6';
			$visibility = (isset($style['visibility'])) ? $style['visibility'] : 'visible';
			
			$i = 0; $d = false;
			foreach($plots as $plot){
				if(($i++)==0) $d = 'M'.$plot['x'].','.$plot['y'];
				else $d = $d.' L'.$plot['x'].','.$plot['y'];
			} return "<path d='".$d."' stroke='".$stroke."' stroke-width='".$strokeWidth."' fill='".$fill."'></path>";
		}
		
		private function getSpline($plots, $style){
			$stroke = (isset($style['stroke'])) ? $style['stroke'] : '#000';
			$strokeWidth = (isset($style['stroke-width'])) ? $style['stroke-width'] : '2';
			$fill = (isset($style['fill'])) ? $style['fill'] : '#000';
			$r = (isset($style['r'])) ? $style['r'] : '6';
			$visibility = (isset($style['visibility'])) ? $style['visibility'] : 'visible';
		
			$d = false; $j = 0;
			for($i=0;$i<count($plots);$i++){
				if($i==0) {
					$mx = (($plots[$i]['x']-$plots[$i+1]['x'])/2)+$plots[$i+1]['x'];
					$d  = "M".$plots[$i]['x'].",".$plots[$i]['y']." C".($mx).",".$plots[$i]['y']." ".($mx).",".$plots[$i+1]['y']." ".$plots[$i+1]['x'].",".$plots[$i+1]['y']."";
				} else {
					if(isset($plots[$i+1])){
						$mx = (($plots[$i]['x']-$plots[$i+1]['x'])/2)+$plots[$i+1]['x'];
						$d = $d." C".($mx).",".$plots[$i]['y']." ".($mx).",".$plots[$i+1]['y']." ".$plots[$i+1]['x'].",".$plots[$i+1]['y']."";
					}
				} $j++;
			} return "<path d='".$d."' stroke='".$stroke."' stroke-width='".$strokeWidth."' fill='".$fill."'></path>";
		}
			
		private function suniqid($length, $prefix='') {
			$random = '';
		  	for ($i = 0; $i < $length; $i++) {
		    	$random .= rand(0, 1) ? rand(0, 9) : chr(rand(ord('a'), ord('z')));
		  	} return $prefix.$random;
		}
	}
	
	// EXEMPLE	
	// $chart->serie('name:[10,20,30,40]')->line(); // WORK
	// $chart->serie('[10,20,30,40]')->line(); // WORK
?>