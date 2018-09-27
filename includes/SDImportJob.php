<?php

/**
 * Background job to import a page into the wiki, adapted from Data Transfer
 *
 * @author Yaron Koren
 * @author Toni Hermoso
 */
class SDImportJob extends Job {

   function __construct( $title, $params = '', $id = 0 ) {
       parent::__construct( 'sdImport', $title, $params, $id );
   }

   /**
    * Run a dtImport job
    * @return boolean success
    */
   function run() {
       wfProfileIn( __METHOD__ );

       if ( is_null( $this->title ) ) {
           $this->error = "sdImport: Invalid title";
           wfProfileOut( __METHOD__ );
           return false;
       }

       if ( method_exists( 'WikiPage', 'getContent' ) ) {
           $wikiPage = new WikiPage( $this->title );
           if ( !$wikiPage ) {
               $this->error = 'sdImport: Wiki page not found "' . $this->title->getPrefixedDBkey() . '"';
               wfProfileOut( __METHOD__ );
               return false;
           }
       } else {
         // Remove old support
         return false;
       }
       $for_pages_that_exist = $this->params['for_pages_that_exist'];
       if ( $for_pages_that_exist == 'skip' && $this->title->exists() ) {
           return true;
       }

       // Change global $wgUser variable to the one specified by
       // the job only for the extent of this import.
       global $wgUser;
       $actual_user = $wgUser;
       $wgUser = User::newFromId( $this->params['user_id'] );
       $text = $this->params['text'];
       $edit_summary = $this->params['edit_summary'];

       // Act according to storage here
       if ( $this->params['storage'] == "json" ) {
          // TODO: to evaluate if check destination page here or before

          // Handle JSON new content
    			$contentModel = $wikiPage->getContentModel();
    			if ( $contentModel === "json" || ! $wikiPage->exists() ) {
    				$new_content = new JSONContent( $text );
    			}

       } else {

         if ( $this->title->exists() ) {
             if ( $for_pages_that_exist == 'append' ) {
                     // MW >= 1.21
               $existingText = $wikiPage->getContent()->getNativeData();
               $text = $existingText . "\n" . $text;

             }
         }

         $new_content = new WikitextContent( $text );

       }

       $wikiPage->doEditContent( $new_content, $edit_summary );

       $wgUser = $actual_user;
       wfProfileOut( __METHOD__ );
       return true;
   }
}
