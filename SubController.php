<?php 

class SubController extends CI_Controller{
   
   private static $dependencies = array();



   function __construct(){

   	// error_reporting(E_ALL);
   	
   	parent::__construct();

   	// echo 'Sub Called ... ';

   	$cls = get_class($this);

   	// echo $cls;
   	if (!isset(self::$dependencies[$cls])){
      self::$dependencies[$cls] = $this;
   	}

   	$this->InitInjectors();

   	$this->ReInitDependencies();

   }

   private function InitInjectors(){

   	 $reflect = new ReflectionClass($this);

   	 $comment = $reflect->getDocComment(); //print_r($reflect);

   	 // echo $comment;

   	 $this->InjectDependencies($comment);

   }
   

   private function InjectDependencies($comment){

   	$r = explode('@Inject(', $comment);
   	
   	if (count($r) > 1){

   		// echo $r[1];
      
      $r = explode(')',$r[1]);

      // print_r($r);

      // echo $r[0];

      $r = explode(',', $r[0]);

      $this->InjectDependencyArray($r);

   	}
   	
   }

   private function InjectDependencyArray($arr){
   	// print_r($arr);
      foreach ($arr as $k=>$dep){

          $this->DecodeDependency($dep);

      }
   }

   private function DecodeDependency($dep){


    
      $r = explode(' as ', $dep);
      if (count($r) > 1){
      	if (!isset(self::$dependencies[$r[1]])){
         $this->LoadDependency($r[0],$r[1]);
      	}
      }else{
      	// echo $dep;
      	$r = explode('/', $dep);
      	$end_ = end($r);
      	if (!isset(self::$dependencies[$end_])){
         $this->LoadDependency($dep,$end_);
      	}
      }
   }

   // private function LoadDependencyWithAlias($depPart,$alias){
   // }

   private function LoadDependency($dep,$alias=''){
     
     $r = explode('/', $dep);
     if (count($r) > 1){

     	// $name = $r[0];
     	// $value = $r[1];

     	$name = array_shift($r);
     	$value = implode('/', $r);

     	$name = trim($name);
     	$value = trim($value);

     	// echo $name;

     	// echo $value;

        if ($name == 'models'){

        	//load model
        	$this->LoadModel($value,$alias);

        }else if ($name == 'controllers'){

        	//load controller
        	
        	$this->LoadController($value,$alias);


        }else if ($name == 'views'){	

        	//load view

        	$this->LoadViewClass($value,$alias);

        }else{ //load others

        }

     } 

   }


   private function LoadModel($model,$alias=''){
   	 	$this->load->model($model,$alias);
        self::$dependencies[$alias] = $this->$alias;
   }

   private function LoadController($ctrl,$alias=''){
     // echo 'ctrls...';
     $path = "application/controllers/$ctrl.php";
     
     if (file_exists($path)){

		    // echo $path;
		    require_once($path);

		 	$r = explode('/', $ctrl);
		 	$cls = end($r);
		    $obj = new $cls();


		     // if (!empty($alias)){
		     	$this->$alias = $obj;
                self::$dependencies[$alias] = $obj;		     	
		     // }else{
		     // 	$this->$ctrl = $obj;
		     // }


     }

   }



   private function LoadViewClass($view,$alias=''){
      
      $obj = new ViewClassHelper();
      $obj->InitViewLoader($this->load,$view);

      self::$dependencies[$alias] = $obj;		



   }

   private function ReInitDependencies(){
   	foreach (self::$dependencies as $dep=>$obj){
      $this->$dep = $obj;
   	}
   }



}



class ViewClassHelper{
   
   private $viewLoader = null;
   private $data = array();
   private $viewPath = '';

   function __set($k,$v){
    $this->data[$k] = $v;
   }
   

   function InitViewLoader($obj,$path){
      $this->viewLoader = $obj;
      $this->viewPath = $path;
   }


   function Render(){
   	return $this->viewLoader->view($this->viewPath,$this->data,true);
   }


}

