<?php
    $options = array( 'help');
    $optionsWithArgs = array('delimiter','separator','namespace','rowobject','rowfields');

    $basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';
    require_once $basePath . '/maintenance/Maintenance.php';
    require_once $basePath . '/maintenance/commandLine.inc';

    echo "Import Text File...\n\n";
    if ( count( $args ) < 1 || isset( $options['help'] ) ){
        showHelp();
    }
    else{
        $filename = $args[0];
        if ( is_file( $filename ) ){
            $text = file_get_contents( $filename );
            $delimiter = isset( $options['delimiter'] ) ? $options['delimiter'] : '"';
            $separator = isset( $options['separator'] ) ? $options['separator'] : ',';
            $namespace = isset( $options['namespace'] ) ? $options['namespace'] : 'SDImport';
            $rowobject = isset( $options['rowobject'] ) ? $options['rowobject'] : 'Demographic';
            $rowfields = isset( $options['rowfields'] ) ? $options['rowfields'] : array("City","Population");
            if(isJSON($rowfields)){
                  $row=json_decode($rowfields);
                  $rowfields="";
                  $rowfields=$row;
              }
            $data=csv_to_array($filename,trim( $separator ),trim( $delimiter ));
            $dataObj=arraySort($data);
            //print_r($dataObj);
            //echo $rowobject;
            for($i=0;$i<count($dataObj);$i++){
                $title = "";
                for($j=0;$j<count($dataObj[$i]);$j++){
                    $title = array_shift($dataObj[$i][$j]);
                }
                //print_r($dataObj[$i]);
                $obj=array('data' => $dataObj[$i],'meta' => array ('app' => 'SDI','version' => 0.1, 'rowobject' => $rowobject, 'rowfields' => $rowfields));
                //print_r($obj);
                $jsonStr = json_encode( $obj );
                //print_r($jsonStr);
                if ( ! empty( $title ) ) {
                    
                    $fulltitle = $title;
                    if ( $namespace !== "" ) {
                        $fulltitle = $namespace.":".$title;
                    }
                    
                    $status = SDImportData::importJSON( $jsonStr, $fulltitle, true );
                    echo "Data ".$title." completed\n";
                }
            }
            echo "\nHas been successfully completed\n";
        }
        else{
            echo "does not exist.\n";
        }
    }
    /**
        User help manual
    **/
    function showHelp(){
        print <<<EOF
        USAGE: php filename.php <options> <filename>

        <filename> : Path to the file containing page content to import

        Options:

        --help
              Show the user manual.

        --delimiter <delimiter>
              The delimiter parameter sets the field delimiter (a single character).

        --separator <separator>
              The separator parameter sets the surrounding character of each field (a single character).

        --namespace <SDImport/JSONData>
              Select SDImport or JSONData.

        --rowfields <rowfields>
              Example: --rowfields='["Example1","Example2"]'.

        --rowbject <rowobject>
              Example: --rowobject='Example1'.

EOF;
    }
    /**
        This function parse the array with a delimiter and a separator given by the user
    **/
    function csv_to_array($filename, $delimiter, $separator){
        if(!file_exists($filename) || !is_readable($filename))
          return FALSE;
          $header = NULL;
          $data = array();
          if (($handle = fopen($filename, 'r')) !== FALSE){
              while (($row = fgetcsv($handle, 1000, $delimiter, $separator)) !== FALSE){
                  $data[] = $row;
              }
              fclose($handle);
          }
          return $data;
    }
    /**
        Sort the array and group it by title
    **/
    function arraySort($input){
        foreach ($input as $key=>$val) $output[$val[0]][]=$val;
        $output = removeKeys( $output );
        return $output;
    }
    /**
        Remove the keys from the array
    **/
    function removeKeys( array $array ){
        $array = array_values( $array );
        foreach ( $array as &$value ){
            if ( is_array( $value ) ){
                $value = removeKeys( $value );
            }
        }
        return $array;
    }

    function isJSON($string){
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
    }



