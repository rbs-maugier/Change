(function () {

	var app = angular.module('RbsChange');

	app.provider('RbsChange.ErrorFormatter', function RbsChangeErrorFormatterProvider() {


		this.$get = ['$location', 'RbsChange.Utils', function ($location, Utils) {

			var errorHandlers = {

				'VALIDATION-ERROR' :
					function (data, context) {
						var message = 'Les propriétés suivantes ne sont pas valides :<dl>';
						angular.forEach(data.data['properties-errors'], function (messages, property) {
							if (context && context.$propertyInfoProvider && context.$propertyInfoProvider[property]) {
								var section = context.$propertyInfoProvider[property].section.id || null;
								var url = Utils.makeUrl($location.absUrl(), { 'section': section });
								message += '<dt><a href="' + url + '">' + context.$propertyInfoProvider[property].label + '</a></dt><dd>' + messages.join('. ') + '</dd>';
							} else {
								message += '<dt>' + property + '</dt><dd>' + messages.join('. ') + '</dd>';
							}
						});
						return message + '</dl>';
					},

				/**
				 * {
				 *   "name": "creationDate",
				 *   "value": "abc",
				 *   "type": "DateTime"
				 * }
				 */
				'INVALID-VALUE-TYPE' :
					function (data, context) {
						var property = data.data.name;
						if (context && context.$propertyInfoProvider && context.$propertyInfoProvider[property]) {
							property = context.$propertyInfoProvider[property];
						}
						return "La propriété '" + property + "' n'a pas le type attendu ('" + data.data.type + "').";
					},

				'INVALID-LCID' :
					function (data) {
						return data.message;
					},

				'UPDATE-ERROR' :
					function (data) {
						return data.message;
					},

				'EXCEPTION-72002' :
				  function (data) {
					  return "Vous devez vous reconnecter avec votre nom d'utilisateur et votre mot de passe. (Code de l'erreur : 72002)";
				  },

				"default" :
					function (data) {
						//console.warn("No error handler defined for error '" + data.code + "': using default message.");
						return data.code + ': ' + data.message;
					}

			};

			return {

				format : function (errorResponse, context) {
					var handler = angular.isFunction(errorHandlers[errorResponse.code]) ? errorResponse.code : 'default';
					return errorHandlers[handler](errorResponse, context);
				}

			};

		}];

	});

})();