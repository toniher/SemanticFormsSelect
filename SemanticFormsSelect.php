<?php

use SFS\HookRegistry;

/**
 * @see https://github.com/SemanticMediaWiki/SemanticFormsSelect/
 *
 * @defgroup SemanticFormsSelect Semantic Forms Select
 * @codeCoverageIgnore
 */
class SemanticFormsSelect {

	/**
	 * @since 1.0
	 */
	public static function initExtension( $credits = array() ) {

		// See https://phabricator.wikimedia.org/T151136 (extension.json)
		define( 'SFS_VERSION', isset( $credits['version'] ) ? $credits['version'] : '2.2.0-alpha' );

		// Api modules
		$GLOBALS['wgAPIModules']['sformsselect'] = 'SFS\ApiSemanticFormsSelect';

		$GLOBALS['wgScriptSelectCount'] = 0;
		$GLOBALS['wgSF_Select_debug'] = 0;

		// Register resource files
		$GLOBALS['wgResourceModules']['ext.sf_select.scriptselect'] = array(
			'localBasePath' => __DIR__ ,
			'remoteExtPath' => 'SemanticFormsSelect',
			'position' => 'bottom',
			'scripts' => array(
				'res/scriptSelect.js'
			),
			'dependencies' => array(
				'ext.pageforms.main'
			)
		);
	}

	/**
	 * @since 1.0
	 */
	public static function onExtensionFunction() {

		if ( !defined( 'PF_VERSION' ) ) {
			die( '<b>Error:</b><a href="https://github.com/SemanticMediaWiki/SemanticFormsSelect/">Semantic Forms Select</a> requires the <a href="https://www.mediawiki.org/wiki/Extension:PageForms">Page Forms</a> extension. Please install and activate this extension first.' );
		}

		if ( isset( $GLOBALS['wgPageFormsFormPrinter'] )) {
			$GLOBALS['wgPageFormsFormPrinter']->setInputTypeHook( 'SF_Select', '\SFS\SemanticFormsSelect::init', array() );
		}
	}

	/**
	 * @since 1.0
	 *
	 * @param string $dependency
	 *
	 * @return string|null
	 */
	public static function getVersion( $dependency = null ) {

		if ( $dependency === null && defined( 'SFS_VERSION' ) ) {
			return SFS_VERSION;
		}

		if ( $dependency === 'PageForms' && defined( 'PF_VERSION' ) ) {
			return PF_VERSION;
		}

		return null;
	}

}
