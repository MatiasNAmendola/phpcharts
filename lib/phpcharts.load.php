<?php 
	require_once 'phpcharts.class.php';

	function getRandPoint($n) { 
		$a = array(); 
		for($i=0;$i<$n;$i++){
			$a[] = rand(rand(0,20), rand(50,500));
		} return $a;
	}

	if(isset($_REQUEST['name'])){
		if($_REQUEST['name']=='spline'){
			print new chart('fsvg', function($chart){				
				$chart->setStyle(array('width'=>$_REQUEST['width']+'px', 'height'=>$_REQUEST['height']+'px'));
				$chart->setGrid(array('distance'=>30, 'visibility'=>'hidden'));
				
				$white = $chart->skin('green', array(
					'plot' => array('stroke'=>'#fff', 'stroke-width'=>0, 'fill'=>'#fff', 'r'=>4),
					'line' => array('stroke'=>'#fff', 'stroke-width'=>2, 'fill'=>'none'),
					'area' => array('fill'=>'none', 'opacity'=>0.2)
				));
				
				$black = $chart->skin('green', array(
					'plot' => array('stroke'=>'#000', 'stroke-width'=>0, 'fill'=>'#000', 'r'=>4),
					'line' => array('stroke'=>'#000', 'stroke-width'=>2, 'fill'=>'none'),
					'area' => array('fill'=>'none', 'opacity'=>0.2)
				));
						
				$n = rand(10,40);
				$serie1 = $chart->serie('test1', getRandPoint($n))->skin($white)->spline();
			});
		} else if($_REQUEST['name']=='line'){
			print new chart('fsvg', function($chart){				
				$chart->setStyle(array('width'=>$_REQUEST['width']+'px', 'height'=>$_REQUEST['height']+'px'));
				$chart->setGrid(array('distance'=>30, 'visibility'=>'hidden'));
				
				$white = $chart->skin('green', array(
					'plot' => array('stroke'=>'#fff', 'stroke-width'=>0, 'fill'=>'#fff', 'r'=>4),
					'line' => array('stroke'=>'#fff', 'stroke-width'=>2, 'fill'=>'none'),
					'area' => array('fill'=>'none', 'opacity'=>0.2)
				));
				
				$black = $chart->skin('green', array(
					'plot' => array('stroke'=>'#000', 'stroke-width'=>0, 'fill'=>'#000', 'r'=>4),
					'line' => array('stroke'=>'#000', 'stroke-width'=>2, 'fill'=>'none'),
					'area' => array('fill'=>'none', 'opacity'=>0.2)
				));
						
				$n = rand(10,40);
				$serie1 = $chart->serie('test1', getRandPoint($n))->skin($white)->line();
			});
		}
	}
?>