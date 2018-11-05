<?php

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';
require_once $basePath . '/maintenance/Maintenance.php';


class ImportJSONData extends Maintenance {


	public function __construct() {
		parent::__construct();
		$this->addDescription( "\n" .
			"Script for importing data stored in JSON stores\n"
		);
		$this->addDefaultParams();
	}

    /**
	 * @see Maintenance::addDefaultParams
	 */
	protected function addDefaultParams() {
		$this->addOption( 'delimiter', 'The delimiter parameter sets the field delimiter (a single character)', false, true, "d" );
		$this->addOption( 'separator', 'The separator parameter sets the surrounding character of each field (a single character)', false, true, "s" );
		$this->addOption( 'namespace', 'Namespace where to store data (main namespace, empty one, by default)', false, true, "n" );
		$this->addOption( 'rowfields', 'Comma-separated list of fields to consider', false, true, "f" );
		$this->addOption( 'rowobject', 'Subobject row property', false, true, "r" );
        $this->addOption( 'user', 'Username to which edits should be attributed. ' .'Default: "Maintenance script"', false, true, 'u' );
        $this->addOption( 'single', 'Enable single mode import', false, false, 'i' );
        $this->addOption( 'overwrite', 'Whether to overwrite existing content', false, false, 'w' );        
        // $this->addArg( 'file', 'Data files to be imported' );
    }

    /**
	 * @see Maintenance::execute
	 */
	public function execute() {
    
        $delimiter = $this->getOption( "delimiter", '"' );
        $separator = $this->getOption( "separator", ',' );
        $namespace = $this->getOption( "namespace", '' ); 
        $rowobject = $this->getOption( "rowobject", null ); 
        $rowfields = $this->getOption( "rowfields", null );
        $userName = $this->getOption( 'user', false );
        $single = $this->getOption( 'single', false );
        $overwrite = $this->getOption( 'overwrite', true );

        // Get all the arguments. A loop is required since Maintenance doesn't
        // suppport an arbitrary number of arguments.
        $files = [];
        $i = 0;
        while ( $arg = $this->getArg( $i++ ) ) {
                if ( file_exists( $arg ) ) {
                        $files[$arg] = file_get_contents( $arg );
                } else {
                        $this->error( "Fatal error: The file '$arg' does not exist!", 1 );
                }
        };

        $count = count( $files );
        $this->output( "Importing $count pages...\n" );

        if ( $userName === false ) {
                $user = User::newSystemUser( 'Maintenance script', [ 'steal' => true ] );
        } else {
                $user = User::newFromName( $userName );
        }

        if ( !$user ) {
                $this->error( "Invalid username\n", true );
        }
        if ( $user->isAnon() ) {
                $user->addToDatabase();
        }

        // TODO: Need to review this in a more efficient way
        foreach ( $files as $file => $text ) {

            if( $this->isJSON($rowfields) ){
                  $row=json_decode($rowfields);
                  $rowfields="";
                  $rowfields=$row;
            }
            
            $data = $this->csv_to_array( $text, trim( $separator ), trim( $delimiter ) );
            
            $dataObj = $this->arraySort( $data );
            
            for( $i=0; $i<count($dataObj); $i++ ){
                
                $title = "";
                
                for( $j=0; $j<count($dataObj[$i]); $j++ ){
                    $title = array_shift( $dataObj[$i][$j] );
                }
                //print_r($dataObj[$i]);
                
                $metaObj = array( 'app' => 'SDI','version' => 0.1 );
                
                if ( $rowobject ) {
                    $metaObj["rowobject"] = $rowobject;
                }
                
                if ( $rowfields ) {
                    $metaObj["rowfields"] = $rowfields;          
                }
                
                if ( $single ) {
                    $metaObj["single"] = true;
                }
                
                $obj = array('data' => $dataObj[$i],'meta' => $metaObj );
    
                //print_r($obj);
                $jsonStr = json_encode( $obj );
                //print_r($jsonStr);
                if ( ! empty( $title ) ) {
                    
                    $fulltitle = $title;
                    if ( $namespace !== "" ) {
                        $fulltitle = $namespace.":".$title;
                    }
                    
                    $status = SDImportData::importJSON( $jsonStr, $fulltitle, $overwrite );
                    echo "Data ".$fulltitle." completed\n";
                }
            }
            echo "\nHas been successfully completed\n";
        }
    
    }

    /**
        This function parse the array with a delimiter and a separator given by the user
    **/
    private function csv_to_array( $text, $delimiter, $separator ){
        
        // Splitting lines
        $lines = preg_split( '/$\R?^/m', $text );
        
        foreach ( $lines as $line ) {
            $data[] = str_getcsv( $text, $delimiter, $separator );
        }

        return $data;
    }
    /**
        Sort the array and group it by title
    **/
    private function arraySort($input){
        
        foreach ($input as $key=>$val) $output[$val[0]][]=$val;
        $output = $this->removeKeys( $output );
        return $output;
    
    }
    /**
        Remove the keys from the array
    **/
    private function removeKeys( array $array ){
        
        $array = array_values( $array );
        foreach ( $array as &$value ){
            if ( is_array( $value ) ){
                $value = removeKeys( $value );
            }
        }
        
        return $array;
    
    }

    private function isJSON($string){
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
    }

}


$maintClass = 'ImportJSONData';
require_once( DO_MAINTENANCE );

