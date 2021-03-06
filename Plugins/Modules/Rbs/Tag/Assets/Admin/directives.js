(function ($) {

	"use strict";

	var app = angular.module('RbsChange'),
		TAG_COLORS = ['red', 'orange', 'yellow', 'green', 'blue', 'purple', 'gray'];


	/**
	 * <rbs-tag-color-selector/>
	 */
	app.directive('rbsTagColorSelector', function () {

		return {
			restrict : 'E',
			require  : 'ngModel',
			scope    : true,
			template :
				'<a ng-repeat="color in colors" class="tag tag-color-setter (=color=)" href ng-click="setColor($event, color)">' +
				'<i ng-class="{true:\'icon-circle\', false:\'icon-circle-blank\'}[selectedColor == color]" class="(= iconSize =)"></i>' +
				'</a>',

			link : function (scope, elm, attrs, ngModel) {
				scope.colors = TAG_COLORS;

				ngModel.$render = function () {
					scope.selectedColor = ngModel.$viewValue;
				};

				scope.iconSize = attrs.iconSize || 'icon-large';

				scope.setColor = function ($event, color) {
					ngModel.$setViewValue(color);
					ngModel.$render();
				};
			}
		};

	});


	/**
	 * <rbs-tag/>
	 */
	app.directive('rbsTag', ['RbsChange.i18n', function (i18n) {
		return {
			restrict : 'A',
			replace  : true,
			template :
				'<span draggable="true" class="tag (= rbsTag.color =)">' +
					'<i class="icon-user" ng-if="rbsTag.userTag" title="' + i18n.trans('m.rbs.tag.admin.js.usertag-text | ucf') + '" style="border-right:1px dotted white; padding-right:4px"></i> ' +
					'<i class="icon-exclamation-sign" ng-if="rbsTag.unsaved" title="' + i18n.trans('m.rbs.tag.admin.js.tag-not-saved | ucf') + '" style="border-right:1px dotted white; padding-right:4px"></i> ' +
					'(= rbsTag.label =)' +
					'<a ng-if="canBeRemoved" href tabindex="-1" class="tag-delete" ng-click="remove()"><i class="icon-remove"></i></a>' +
				'</span>',

			scope : {
				'rbsTag'   : '=',
				'onRemove' : '&'
			},

			link : function (scope, elm) {
				elm.addClass('tag');
				scope.canBeRemoved = elm.is('[on-remove]');
				scope.remove = function () {
					scope.onRemove();
				};
				scope.$watch('rbsTag', function (value) {
					if (value) {
						elm.addClass(value.color);
						if (value.unsaved) {
							elm.addClass('new');
						} else {
							elm.removeClass('new');
						}
						if (value.used) {
							elm.addClass('opacity-half');
						} else {
							elm.removeClass('opacity-half');
						}
					}
				}, true);

				function buildDnDRepresentation (tag) {
					return JSON.stringify({
						"id" : tag.id,
						"label" : tag.label,
						"color" : tag.color,
						"userTag" : tag.userTag
					});
				}

				elm.on({
					'dragstart.rbs.tag': function (e) {
						e.dataTransfer.setData('Rbs/Tag', buildDnDRepresentation(scope.rbsTag));
						e.dataTransfer.effectAllowed = "copy";
					}
				});

			}
		};
	}]);

	/**
	 * <rbs-tag-filter/>
	 */
	app.directive('rbsTagFilter', ['$q', '$location', '$filter', 'RbsChange.TagService', 'RbsChange.ArrayUtils', rbsTagFilter]);

	function rbsTagFilter ($q, $location, $filter, TagService, ArrayUtils) {

		return {
			restrict : 'E',
			templateUrl : 'Rbs/Tag/tag-filter.twig',
			replace: 'true',
			scope: false,
			// Create isolated scope

			link : function (scope) {

				var tagsLoadedDefered = $q.defer();
				scope.tags = TagService.getList(tagsLoadedDefered);
				scope.selectedTags = [];

				function updateFilter () {
					scope.filteredTags = $filter('filter')(scope.tags, {'label': scope.filterTags});
					//scope.filteredTags = $filter('orderBy')($filter('filter')(scope.tags, {'label': scope.filterTags}), 'label');
				}

				scope.$watch('filterTags', updateFilter, true);
				scope.$watch('tags', updateFilter, true);

				tagsLoadedDefered.promise.then(function () {
					var filter = $location.search()['filter'];
					if (filter && filter.indexOf('hasTag:') === 0) {
						angular.forEach(filter.substring(7).split(/,/), function (tagId) {
							var tag = getTagById(parseInt(tagId, 10));
							if (tag) {
								scope.selectTag(tag);
							}
						});
					}
				});

				function getTagById (id) {
					var i;
					for (i=0 ; i<scope.tags.length ; i++) {
						if (scope.tags[i].id === id) {
							return scope.tags[i];
						}
					}
					return null;
				}

				function updateUrl () {
					var ids = [];
					angular.forEach(scope.selectedTags, function (tag) {
						ids.push(tag.id);
					});
					if (ids.length) {
						$location.url($location.path()+'?filter=hasTag:' + ids.join(','));
					} else {
						$location.url($location.path());
					}
				}

				scope.selectTag = function (tag) {
					if (ArrayUtils.inArray(tag, scope.selectedTags) === -1) {
						scope.selectedTags.push(tag);
					}
					updateUrl();
				};

				scope.unselectTag = function (tag) {
					if (ArrayUtils.removeValue(scope.selectedTags, tag) !== -1) {
						updateUrl();
					}
				};

				scope.removeLastSelectedTag = function () {
					if (scope.selectedTags.length) {
						scope.selectedTags.pop();
						updateUrl();
					}
				};

				scope.unselectAll = function () {
					ArrayUtils.clear(scope.selectedTags);
					updateUrl();
				};


			}
		};

	}

	/**
	 * <rbs-tag-filter-panel/>
	 */
	app.directive('rbsTagFilterPanel', rbsTagFilterPanel);

	function rbsTagFilterPanel () {

		return {
			restrict : 'E',
			templateUrl : 'Rbs/Tag/tag-filter-panel.twig',
			replace: 'true',
			scope: false,
			// Create isolated scope

			link : function (scope) {

			}
		};

	}

	/**
	 * <input class="rbs-auto-size-input" ... />
	 *
	 * Used in <rbs-tag-selector/>
	 */
	app.directive('rbsAutoSizeInput', function () {

		var options = {
			maxWidth: 1000,
			comfortZone: 18
		};

		return {
			restrict : 'AC',

			link : function (scope, elm) {

				var val = '',
					input = $(elm),
					testSubject = $('<tester/>').css({
						position: 'absolute',
						top: -9999,
						left: -9999,
						width: 'auto',
						fontSize: input.css('fontSize'),
						fontFamily: input.css('fontFamily'),
						fontWeight: input.css('fontWeight'),
						letterSpacing: input.css('letterSpacing'),
						whiteSpace: 'nowrap'
					}),
					check = function() {
						if (val === (val = input.val())) {return;}
						var escaped = val.replace(/&/g, '&amp;').replace(/\s/g,' ').replace(/</g, '&lt;').replace(/>/g, '&gt;');
						testSubject.html(escaped);
						input.width(Math.min(testSubject.width() + options.comfortZone, options.maxWidth));
					};

				testSubject.insertAfter(input);

				$(elm).bind('keydown keyup blur update', check);

			}
		};

	});


	/**
	 * <rbs-tag-selector ng-model="document.tags"></rbs-tag-selector>
	 */
	app.directive(
		'rbsTagSelector',
		[
			'$timeout', '$compile', 'RbsChange.ArrayUtils', 'RbsChange.TagService', 'RbsChange.i18n',
			rbsTagSelectorFn
		]
	);

	function rbsTagSelectorFn ($timeout, $compile, ArrayUtils, TagService, i18n) {

		var	autocompleteEl;

		function loadAvailTags () {
			return TagService.getList();
		}

		// Popover element that shows suggestions.
		$('<div id="rbsTagSelectorAutocompleteList"></div>').css({
			'position' : 'absolute',
			'display'  : 'none'
		}).appendTo('body');
		autocompleteEl = $('#rbsTagSelectorAutocompleteList');

		return {
			restrict : 'E',
			replace  : true,
			require  : 'ngModel',
			scope    : true,
			template :
				'<div class="tag-selector" ng-mousedown="focus($event)" ng-swipe-left="moveLeft()" ng-swipe-right="moveRight()">' +
					'<span class="btn-toolbar pull-right">' +
					'<a target="_blank" class="btn btn-xs btn-default" title="' + i18n.trans('m.rbs.tag.admin.js.manage-tags | ucf') + '" type="button" href="Rbs/Tag">' +
					'<i class="icon-cog"></i>' +
					'</a>' +
					'<button class="btn btn-xs btn-default" title="' + i18n.trans('m.rbs.tag.admin.js.show-hide-all-tags | ucf') + '" type="button" ng-click="toggleShowAll($event)">' +
					'<i ng-class="{true:\'icon-chevron-up\',false:\'icon-chevron-down\'}[showAll]"></i>' +
					'</button>' +
					'</span>' +
					'<span ng-repeat="tag in tags">' +
					'<span ng-if="! tag.input" rbs-tag="tag" on-remove="removeTag($index)"></span>' +
					'<input autocapitalize="off" autocomplete="off" autocorrect="off" type="text" rbs-auto-size-input="" ng-if="tag.input" ng-keyup="autocomplete()" ng-keydown="keydown($event, $index)"></span>' +
					'</span>' +
					'<div class="all-tags clearfix" ng-show="showAll">' +
					'<h6 ng-pluralize count="availTags.length" when="{0: \'' + i18n.trans('m.rbs.tag.adminjs.no_available_tag') + '\', ' +
						'one: \'' + i18n.trans('m.rbs.tag.adminjs.available_tag') + '\', ' +
						'other: \'' + i18n.trans('m.rbs.tag.adminjs.available_tags') + '\'}">' +
					'</h6>' +
					'<a href ng-repeat="tag in availTags" ng-click="appendTag(tag)"><span rbs-tag="tag"></span></a>' +
					'</div>' +
				'</div>',


			link : function (scope, elm, attrs, ngModel) {

				var inputIndex = -1, tempTagCounter = 0;

				ngModel.$render = function ngModelRenderFn () {
					if (angular.isArray(ngModel.$viewValue)) {
						scope.tags = angular.copy(ngModel.$viewValue);
					} else {
						scope.tags = [];
					}
					if (inputIndex === -1) {
						scope.tags.push({'input': true});
					} else {
						scope.tags.splice(inputIndex, 0, {'input': true});
					}
				};


				scope.availTags = loadAvailTags();
				scope.showAll = false;

				scope.toggleShowAll = function ($event) {
					scope.showAll = ! scope.showAll;
					if (scope.showAll && $event.shiftKey) {
						scope.availTags = loadAvailTags();
					}
				};

				function getInput() {
					return elm.find('input[type=text]');
				}

				function update () {
					var value = [];
					angular.forEach(scope.tags, function (tag, i) {
						if (tag.input) {
							inputIndex = i;
						} else {
							value.push(tag);
						}
					});
					ngModel.$setViewValue(value.length === 0 ? undefined : value);
				}

				function backspace () {
					if (scope.tags.length > 1) {
						scope.tags[inputIndex-1].used = false;
						scope.tags.splice(inputIndex-1, 1);
						update();
					}
				}

				function findTag (label) {
					var i;
					for (i=0 ; i<scope.availTags.length ; i++) {
						if (angular.lowercase(scope.availTags[i].label) === angular.lowercase(label)) {
							return scope.availTags[i];
						}
					}
					return null;
				}

				function add (value) {
					var tag = findTag(value);
					if (!tag) {
						tag = createTemporaryTag(value);
					}
					appendTag(tag);
				}

				function createTemporaryTag (value) {
					return {
						'id'      : --tempTagCounter,
						'label'   : value,
						'unsaved' : true,
						'userTag' : true
					};
				}

				function appendTag (tag) {
					if (!scope.isUsed(tag)) {
						scope.tags.splice(inputIndex, 0, tag);
						tag.used = true;
						update();
					}
				}
				scope.appendTag = appendTag;

				function moveInput (value, offset) {
					if (value.length === 0 && (inputIndex+offset) < (scope.tags.length)) {
						var r = ArrayUtils.move(scope.tags, inputIndex, inputIndex + offset);
						update();
						scope.focus();
						return r;
					}
					return false;
				}

				scope.isUsed = function (tag) {
					var i;
					for (i=0 ; i<scope.tags.length ; i++) {
						if (! scope.tags[i].input && scope.tags[i].id === tag.id) {
							return true;
						}
					}
					return false;
				};

				scope.removeTag = function (index) {
					scope.tags[index].used = false;
					scope.tags.splice(index, 1);
					update();
				};

				scope.focus = function () {
					$timeout(function () {
						getInput().focus();
					});
				};

				scope.moveLeft = function () {
					moveInput(getInput().val().trim(), -1);
				};
				scope.moveRight = function () {
					moveInput(getInput().val().trim(), +1);
				};

				scope.keydown = function ($event, index) {
					var	input = getInput(),
						value = input.val().trim();
					inputIndex = index;

					switch ($event.keyCode) {

						// Backspace
						case 8 :
							if (value.length === 0 && !$event.shiftKey && !$event.metaKey) {
								backspace();
								$event.preventDefault();
								$event.stopPropagation();
							}
							break;

						// Tab
						case 9 :
						// Coma
						case 188 :
							if (value.length > 0) {
								add(value);
								input.val('');
								$event.preventDefault();
								$event.stopPropagation();
							}
							break;

						// Enter
						case 13 :
							if (scope.suggestions.length) {
								$event.preventDefault();
								$event.stopPropagation();
								scope.autocompleteAdd(scope.suggestions[0]);
							} else if (value.length > 0) {
								add(value);
								input.val('');
								$event.preventDefault();
								$event.stopPropagation();
							}
							break;

						// Left arrow
						case 37 :
							if (moveInput(value, -1)) {
								$event.preventDefault();
								$event.stopPropagation();
							}
							break;

						// Right arrow
						case 39 :
							if (moveInput(value, 1)) {
								$event.preventDefault();
								$event.stopPropagation();
							}
							break;

						// Down arrow
						case 40 :
							if ($event.ctrlKey) {
								$event.preventDefault();
								$event.stopPropagation();
								scope.showAll = true;
							}
							break;

						// Up arrow
						case 38 :
							if ($event.ctrlKey) {
								$event.preventDefault();
								$event.stopPropagation();
								scope.showAll = false;
							}
							break;

						// Ctrl+R
						case 82 :
							if ($event.ctrlKey) {
								scope.availTags = loadAvailTags();
								$event.preventDefault();
								$event.stopPropagation();
							}
							break;
					}

				};


				scope.autocompleteAdd = function (tag) {
					autocompleteEl.hide();
					getInput().val('');
					appendTag(tag);
					focus();
				};

				scope.autocomplete = function () {
					var	input = getInput(),
						value = input.val().trim(),
						suggestions = [];

					if (value.length) {
						value = angular.lowercase(value);
						angular.forEach(scope.availTags, function (tag) {
							var label = angular.lowercase(tag.label), p;
							if (ArrayUtils.inArray(tag, scope.tags) === -1) {
								p = label.indexOf(value);
								if (p === 0) {
									// Tag begins with the entered value?
									// Add it at the beginning of the autocomplete list.
									suggestions.unshift(tag);
								} else if (p > 0) {
									// Tag contains the entered value?
									// Add it at the end of the autocomplete list.
									suggestions.push(tag);
								}
							}
						});
					}
					scope.suggestions = suggestions;

					function buildSuggestionsList () {
						return '<a href ng-repeat="tag in suggestions" ng-click="autocompleteAdd(tag)"><span rbs-tag="tag"></span></a><br/><small><em>' + i18n.trans('m.rbs.tag.admin.js.enter-selects-first-tag | ucf') + '</em></small>';
					}

					if (suggestions.length) {
						$compile(buildSuggestionsList())(scope, function (clone) {
							autocompleteEl.empty();
							autocompleteEl.append(clone);
							autocompleteEl.css({
								'left' : input.offset().left + 'px',
								'top'  : (input.outerHeight() + input.offset().top + 3)  + 'px'
							});
							autocompleteEl.show();
						});
					} else {
						autocompleteEl.hide();
					}

				};

			}
		};
	}


})(window.jQuery);