<?php

    $options = array( 'help');
    $optionsWithArgs = array('delimiter','separator','namespace');

    $basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';
    require_once $basePath . '/maintenance/commandLine.inc';
    echo "Import Text File\n\n";

    if ( count( $args ) < 1 || isset( $options['help'] ) )
    {
        showHelp();
    }
    else
    {
        $filename = $args[0];
        if ( is_file( $filename ) )
        {
            $text = file_get_contents( $filename );
            $delimiter = isset( $options['delimiter'] ) ? $options['delimiter'] : '"';
            $delimiter = User::newFromName( $delimiter );
            $separator = isset( $options['separator'] ) ? $options['separator'] : ',';
            $separator = User::newFromName( $separator );
            $namespace = isset( $options['namespace'] ) ? $options['namespace'] : 'SDImport';
            $namespace = User::newFromName( $namespace );
            //echo $namespace;
            $data=csv_to_array($filename,trim( $separator ),trim( $delimiter ));
            sort($data);
            print_r($data);
        }
        else
        {
            echo "does not exist.\n";
        }
    }

    function showHelp()
    {
        print <<<EOF
        USAGE: php filename.php <options> <filename>

        <filename> : Path to the file containing page content to import

        Options:

        --help
            Show this information
        --delimiter <delimiter>
            The delimiter parameter sets the field delimiter (a single character).
        --separator <separator>
            The separator parameter sets the surrounding character of each field (a single character).
        --namespace <SDImport/JSONData>
            Select SDImport or JSONData

EOF;
    }

    function csv_to_array($filename, $delimiter, $separator)
    {
        if(!file_exists($filename) || !is_readable($filename))
          return FALSE;
          $header = NULL;
          $data = array();
          if (($handle = fopen($filename, 'r')) !== FALSE)
          {
              while (($row = fgetcsv($handle, 1000, $delimiter, $separator)) !== FALSE)
              {
                  $data[] = $row;
              }
              fclose($handle);
          }
          return $data;
    }
