(function () {

	"use strict";

	var app = angular.module('RbsChange');


	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModel('Rbs_Theme_Theme');


	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{

			$delegate.model('Rbs_Theme')
				.route('home', 'Rbs/Theme', { 'redirectTo': 'Rbs/Theme/Theme/'});

			$delegate.model('Rbs_Theme_Theme')
				.route('tree', 'Rbs/Theme/Theme/:id/Templates/', 'Document/Rbs/Theme/PageTemplate/list.twig');

			$delegate.routesForModels([
				'Rbs_Theme_Theme',
				'Rbs_Theme_PageTemplate'
			]);

			return $delegate;
		}]);

	}]);

})();