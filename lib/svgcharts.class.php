<?php 

	class svgCharts {

		public $series;
		public $skins;
		public $grid;
	
		// Charts var
		public $name;
		public $style;

		public function __construct($option, $callback){
			$this->name = (isset($option['name'])) ? $option['name'] : $this->suniqid(6, '_');
			$this->style = $this->setStyle($option);
			$this->grid = new chartGrid();
			$callback($this);
		}
		
		private function setStyle($opt){
			return array(
				'width' => ((isset($opt['width'])) ? $opt['width'] : '920px'),
				'height' => ((isset($opt['height'])) ? $opt['height'] : '400px'),
				'distance' => ((isset($opt['distance'])) ? $opt['distance'] : 10)
			);
		}
	
		public function draw(){			
			echo "<svg name='{$this->name}' width='{$this->style['width']}' height='{$this->style['height']}' class='charts' rel='charts'>";
				echo "<g name='{$this->name}_grid' rel='grid'>";
					$this->grid->draw($this);
				echo "</g>";
				
				$i = 1;
				foreach($this->series as $serie){
					echo "<g name='{$this->name}_serie_".($i++)."' rel='serie'>";
						$serie->draw($this);
					echo "</g>";
				}
			echo "</svg>";
		}
		
		public function serie($data = null){
			if($data!=null){
				$this->series[] = new chartsSeries($data);
				return end($this->series);
			} return false;
		}
		
		// USUAL METHOD
		function suniqid($length, $prefix='') {
			$random = '';
		  	for ($i = 0; $i < $length; $i++) {
		    	$random .= rand(0, 1) ? rand(0, 9) : chr(rand(ord('a'), ord('z')));
		  	} return $prefix.$random;
		}
	}
	
	class chartGrid {
	
		public $chart;
		public $maxValue;
		public $maxLength;
		public $yDistance;
		public $xDistance;
		public $ratio;
	
		public function draw($t){
			$this->chart  = $t;
			$this->maxValue = $this->getMaxValue();
			$this->maxLength = $this->getMaxLength();
			$this->yDistance = $this->getYdistance();
			$this->xDistance = $this->getXdistance();
			$this->ratio = $this->getRatio();
			
			for($i=1;$i<($this->maxValue/$this->chart->style['distance']);$i++){
				echo "<path d='M 0 ".($i*$this->xDistance)." L ".((int) $this->chart->style['width'])." ".($i*$this->xDistance)."' stroke-width='1' fill='none' stroke='#000' shape-rendering='crispEdges' name='{$this->chart->name}_gridhline_{$i}'></path>";
			}
			
			for($i=1;$i<$this->maxLength;$i++){
				echo "<path d='M ".($i*$this->yDistance)." 0 L ".($i*$this->yDistance)." ".((int) $this->chart->style['height'])."' stroke-width='1' fill='none' stroke='#000' shape-rendering='crispEdges' name='{$this->chart->name}_gridvline_{$i}'></path>";
			}
		}
		
		public function getRatio(){
			//arc: 1-(this.height/max)
			return (1 - (((int) $this->chart->style['height'])/$this->maxValue));
		}
		
		public function getYdistance(){
			return ((int) $this->chart->style['width'] / $this->maxLength);
		}
		
		public function getXdistance(){
			while(($this->maxValue%$this->chart->style['distance'])!=0){ $this->maxValue++; }
			return ((int) $this->chart->style['height'] / ($this->maxValue/$this->chart->style['distance']));	
		}
		
		public function getMaxLength(){
			$all = array();
			foreach($this->chart->series as $series){
				$all[] = count($series->getDatas());
			} return (count($all)>0) ? max($all) : 0;
		}
		
		public function getMaxValue(){
			$all = array();
			foreach($this->chart->series as $series){
				foreach($series->getDatas() as $data){
					$all[] = $data;
				}
			} return (count($all)>0) ? max($all) : 0;
		}
	}
	
	class chartsSeries {
	
		public $chart;
		public $datas;
		public $type;
		public $order;
		
		public function __construct($datas){
			$this->datas = $datas;
		}
		
		public function draw($t){
			$this->chart = $t;
			if( is_callable(array($this, 'get'.ucfirst(strtolower($this->type))))){
				call_user_func(array($this, 'get'.ucfirst(strtolower($this->type))));
				echo $this->order['path'];
				echo implode('', $this->order['circle']);
			}
		}
		
		public function placePlot(){
			$i = 0; $plot = array();
			foreach($this->datas as $data){
				$posx = ($this->chart->grid->yDistance-($this->chart->grid->yDistance/2))+($i*$this->chart->grid->yDistance);
				$posy = (((int) $this->chart->style['height'])-($data))+($data*$this->chart->grid->ratio);
				$plot[] = array('x'=>$posx, 'y'=>$posy);
				$this->order['circle'][] = "<circle cx='".$posx."' cy='".$posy."' r='6' stroke='#293134' stroke-width='2' fill='#fff' visibility='visible' z-index='2' rel='seriepoint'></circle>";
			$i++;
			} return $plot;
		}
		
		public function getLine() {
			$i = 0; $d = false;
			foreach($this->placePlot() as $plot){
				if(($i++)==0) $d = 'M'.$plot['x'].','.$plot['y'];
				else $d = $d.' L'.$plot['x'].','.$plot['y'];
			} $this->order['path'] = "<path d='".$d."' stroke='#F00' stroke-width='2' fill='none' z-index='1'></path>";
		}
		
		public function getSpline(){
			$i = 0; $d = false;
			foreach($this->placePlot() as $plot){
				if(($i++)==0) {
					$mx = ((int) $plot['x']) - $plot
					
					// $mx = (((parseInt(plot[k].x)-plot[parseInt(k)+1].x)/2)+parseInt(plot[parseInt(k)+1].x)-8);
					//$d = "M"+plot[k].x+","+plot[k].y+" C"+mx+","+plot[k].y+" "+mx+","+plot[parseInt(k)+1].y+" "+plot[parseInt(k)+1].x+","+plot[parseInt(k)+1].y+"";
				} else {
					//if(isset($plot[parseInt(k)+1]!='undefined'){
					//	$mx = (((parseInt(plot[k].x)-plot[parseInt(k)+1].x)/2)+parseInt(plot[parseInt(k)+1].x));
					//	$d = d+" C"+mx+","+plot[k].y+" "+mx+","+plot[parseInt(k)+1].y+" "+plot[parseInt(k)+1].x+","+plot[parseInt(k)+1].y+"";
					// } 
				}
			} $this->order['path'] = "<path d='".$d."' stroke='#F00' stroke-width='2' fill='none' z-index='1'></path>";
		}
		
		public function getDatas() { return $this->datas; }
		public function line(){ $this->type = 'line'; }
		public function spline(){ $this->type = 'spline'; }
	
	}