<?php
	/**
	 * Helper to display a Facets Block
	 */

	/**
	 * Return list of objects associated to collection.
	 *
	 * @param subsetSQL
	 * @return objects
	 */
	function get_objects_for_tags($subsetSQL, $sortOrder) {
		// Create Where clause
		$whereSubset = createWhereSubsetClause('item', $subsetSQL);

		// Define Order By clause
		if ($sortOrder == 'count_alpha') {
			$orderBy = array('tagCount DESC', 'name ASC');
		} else {
			$orderBy = array('name ASC');
		}
		
		// Get the database.
		$db = get_db();
		// Get the table.
		$table = $db->getTable('Tag');
		// Build the select query.
		$select = $table->getSelectForFindBy();
		$table->filterByTagType($select, 'Item');
		$select->where($whereSubset);
		$select->order($orderBy);
		
		return $table->fetchObjects($select);
	}

	/**
	 * Return HTML Select associated with Array of facets tag values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html.
	 */
	function get_tags_facet_select($subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false) {
		if ($tags = get_objects_for_tags($subsetSQL, $sortOrder)) {
			// Build array
			$facetTags = buildTagsArray($tags);
			
			// Stores data for selected tag, if any
			$selectedTagName = (isset($_GET['tags']) ? $_GET['tags'] : '');

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetTags, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetTags = array_filter($facetTags, "isNotSingleExtra");
			}			

			$addOptions = false;
			// Build first part of the select tag
			if ($selectedTagName != '') {
				$html  = "<div class=\"select-cross\"><select class=\"facet-selected\" name=\"tag\">";
				$html .= "<option value=\"\" data-url=\"" . getFieldUrl('tags', null) . "\"> " . html_escape(__('Remove filter')) . "...</option>";
				$html .= "<option selected value=\"\">" . $selectedTagName . "</option>";
			} elseif (count($facetTags) > 0) {
				$html  = "<div class=\"select-arrow\"><select class=\"facet\" name=\"tag\">";
				$html .= "<option value=\"\">" . html_escape(__('Select')) . "...</option>";
				$addOptions = true;
			}

			// Build additional part of the select tag (if needed)
			if ($addOptions) {
				foreach ($facetTags as $tag) {
					$html .= "<option value=\"" . $tag['id'] . "\" data-url=\"" . getFieldUrl('tags', $tag['name']) . "\">" . $tag['name'] . ($showPopularity ? " (" . $tag['count'] . ")" : "") . "</option>";
				}
			}
			$html .= "</select></div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return HTML Checkboxes associated with Array of facets tag values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html.
	 */
	function get_tags_facet_checkboxes($subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false, $limitCheckboxes = 0) {
		if ($tags = get_objects_for_tags($subsetSQL, $sortOrder)) {
			// Build array
			$facetTags = buildTagsArray($tags);
			
			// Stores data for selected tags, if any
			$selectedTags = (isset($_GET['tags']) ? $_GET['tags'] : '');
			$selectedTagNames = explode(option('tag_delimiter'), $selectedTags);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetTags, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetTags = array_filter($facetTags, "isNotSingleExtra");
			}			

			$countCheckboxes = 0;
			$html = '<div>';
			// Build first part of the checkboxes tag
			if ($selectedTags != '') {
				$selectedTagNames = explode(option('tag_delimiter'), $selectedTags);
				foreach ($selectedTagNames as $selectedTagName) {
					$url = getTagUrl($selectedTagName);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"\" data-url=\"" . $url . "\" checked><b>" . html_escape($selectedTagName) . "</b></div>";
					$countCheckboxes++;
				}
			}

			$hidingSeparator = false;
			// Build additional part of the select tag (if needed)
			foreach ($facetTags as $tag) {
				if (!in_array($tag['name'], $selectedTagNames)) {
					if ($limitCheckboxes != 0 && $countCheckboxes >= $limitCheckboxes && !$hidingSeparator) {
						// Add link to show other values
						$html .= "<div class=\"hidden\" id=\"facet-extra-values-tags\">";
						$hidingSeparator = true;
					}
					
					$url = getTagUrl($tag['name']);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"" . $tag['name'] . "\" data-url=\"" . $url . "\">" . html_escape($tag['name']) . ($showPopularity ? "<span class=\"facet-checkbox-count\"> (" . $tag['count'] . ")</span>" : "") . "</div>";
					$countCheckboxes++;
				}
			}
			
			if ($hidingSeparator) {
				$html .= "</div>";
				$html .= "<a id=\"facet-extra-link-tags\" class=\"facet-visibility-toggle\" data-element-id=\"tags\">" . FACETS_SHOW_MORE . "</a>";
			}

			$html .= "</div>";
		} else {
			$html = false;
		}

		return $html;
	}
	
	/**
	 * Return list of objects associated to collection.
	 *
	 * @param subsetSQL
	 * @return objects
	 */
	function get_objects_for_collection($subsetSQL) {
		// Create Where clause
		$whereSubset = createWhereSubsetClause('item', $subsetSQL);
		
		// Get the database.
		$db = get_db();
		// Get the table.
		$table = $db->getTable('Collection');
		// Build the select query.
		$select = $table->getSelect()
			->columns('COUNT(collections.id) AS count')
			->joinInner(array('items' => $db->Items), 'collections.id = items.collection_id', array())
			->where($whereSubset)
			->group('collections.id');

		return $table->fetchObjects($select);
	}

	/**
	 * Return HTML Select associated with Array of facets collection values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html.
	 */
	function get_collections_facet_select($subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false) {
		if ($collections = get_objects_for_collection($subsetSQL, $sortOrder)) {
			// Build array
			$facetCollections = buildCollectionsArray($collections);
			
			// Store data for selected collection, if any
			$selectedCollection = getSelectedCollection($facetCollections);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetCollections, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetCollections = array_filter($facetCollections, "isNotSingleExtra");
			}			

			// Sort array (have to do it now instead than in select because of the way we get the Collection name)
			$facetCollections = sortCollections($facetCollections, $sortOrder);

			$addOptions = false;
			// Build first part of the select tag
			if (isset($selectedCollection)) {
				$html  = "<div class=\"select-cross\"><select class=\"facet-selected\" name=\"collection\">";
				$html .= "<option value=\"\" data-url=\"" . getFieldUrl('collection', null) . "\"> " . html_escape(__('Remove filter')) . "...</option>";
				$html .= "<option selected value=\"\">" . $selectedCollection['name'] . "</option>";
			} elseif (count($facetCollections) > 0) {
				$html  = "<div class=\"select-arrow\"><select class=\"facet\" name=\"collection\">";
				$html .= "<option value=\"\">" . html_escape(__('Select')) . "...</option>";
				$addOptions = true;
			}

			// Build additional part of the select tag (if needed)
			if ($addOptions) {
				foreach ($facetCollections as $collection) {
					$html .= "<option value=\"" . $collection['id'] . "\" data-url=\"" . getFieldUrl('collection', $collection['id']) . "\">" . $collection['name'] . ($showPopularity ? " (" . $collection['count'] . ")" : "") . "</option>";
				}
			}
			$html .= "</select></div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return HTML Checkboxes associated with Array of facets values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @param limitCheckboxes
	 * @return html
	 */
	function get_collections_facet_checkboxes($subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false, $limitCheckboxes = 0) {
		if ($collections = get_objects_for_collection($subsetSQL, $sortOrder)) {
			// Build array
			$facetCollections = buildCollectionsArray($collections);
			
			// Store data for selected collection, if any
			$selectedCollection = getSelectedCollection($facetCollections);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetCollections, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetCollections = array_filter($facetCollections, "isNotSingleExtra");
			}			

			// Sort array (have to do it now instead than in select because of the way we get the Collection's name)
			$facetCollections = sortCollections($facetCollections, $sortOrder);

			$countCheckboxes = 0;
			$html = '<div>';
			// Build first part of the checkboxes tag
			if (isset($selectedCollection)) {
				$url = getFieldUrl('collection', null, $selectedCollection);
				$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"\" data-url=\"" . $url . "\" checked><b>" . html_escape($selectedCollection['name']) . "</b></div>";
				$countCheckboxes++;
			}

			$hidingSeparator = false;
			// Build additional part of the select tag (if needed)
			foreach ($facetCollections as $collection) {
				if ($collection != $selectedCollection) {
					if ($limitCheckboxes != 0 && $countCheckboxes >= $limitCheckboxes && !$hidingSeparator) {
						// Add link to show other values
						$html .= "<div class=\"hidden\" id=\"facet-extra-values-collections\">";
						$hidingSeparator = true;
					}
					
					$url = getFieldUrl('collection', $collection['id']);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"" . $collection['name'] . "\" data-url=\"" . $url . "\">" . html_escape($collection['name']) . ($showPopularity ? "<span class=\"facet-checkbox-count\"> (" . $collection['count'] . ")</span>" : "") . "</div>";
					$countCheckboxes++;
				}
			}
			
			if ($hidingSeparator) {
				$html .= "</div>";
				$html .= "<a id=\"facet-extra-link-collections\" class=\"facet-visibility-toggle\" data-element-id=\"collections\">" . FACETS_SHOW_MORE . "</a>";
			}

			$html .= "</div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return list of objects associated to collection.
	 *
	 * @param subsetSQL
	 * @param sortOrder
	 * @return objects
	 */
	function get_objects_for_item_type($subsetSQL, $sortOrder) {
		// Create Where Subset clause
		$whereSubset = createWhereSubsetClause('item', $subsetSQL);

		// Define Order by clause
		if ($sortOrder == 'count_alpha') {
			$orderBy = array('count DESC', 'name ASC');
		} else {
			$orderBy = array('name ASC');
		}
		
		// Get the database.
		$db = get_db();
		// Get the table.
		$table = $db->getTable('ItemType');
		// Build the select query.
		$select = $table->getSelect()
			->columns('COUNT(item_types.id) AS count')
			->joinInner(array('items' => $db->Items), 'item_types.id = items.item_type_id', array())
			->where($whereSubset)
			->group('item_types.id')
			->order($orderBy);
			
		return $table->fetchObjects($select);
	}

	/**
	 * Return HTML Select associated with Array of facets item type values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html.
	 */
	function get_item_types_facet_select($subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false) {
		if ($itemTypes = get_objects_for_item_type($subsetSQL, $sortOrder)) {
			// Build array
			$facetItemTypes = buildItemTypesArray($itemTypes);
					
			// Store data for selected item type, if any
			$selectedItemType = getSelectedItemType($facetItemTypes);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetItemTypes, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetItemTypes = array_filter($facetItemTypes, "isNotSingleExtra");
			}			

			$addOptions = false;
			// Build first part of the select tag
			if (!empty($selectedItemType)) {
				$html  = "<div class=\"select-cross\"><select class=\"facet-selected\" name=\"type\">";
				$html .= "<option value=\"\" data-url=\"" . getFieldUrl('type', null) . "\"> " . html_escape(__('Remove filter')) . "...</option>";
				$html .= "<option selected value=\"\">" . $selectedItemType['name'] . "</option>";
			} elseif (count($facetItemTypes) > 0) {
				$html  = "<div class=\"select-arrow\"><select class=\"facet\" name=\"type\">";
				$html .= "<option value=\"\">" . html_escape(__('Select')) . "...</option>";
				$addOptions = true;
			}

			// Build additional part of the select tag (if needed)
			if ($addOptions) {
				foreach ($facetItemTypes as $itemType) {
					$html .= "<option value=\"" . $itemType['id'] . "\" data-url=\"" . getFieldUrl('type', $itemType['id']) . "\">" . $itemType['name'] . ($showPopularity ? " (" . $itemType['count'] . ")" : "") . "</option>";
				}
			}
			$html .= "</select></div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Return HTML Checkboxes associated with Array of facets item type values.
	 *
	 * @param subsetSQL
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html.
	 */
	function get_item_types_facet_checkboxes($subsetSQL, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false, $limitCheckboxes = 0) {
		if ($itemTypes = get_objects_for_item_type($subsetSQL, $sortOrder)) {
			// Build array
			$facetItemTypes = buildItemTypesArray($itemTypes);
					
			// Store data for selected item type, if any
			$selectedItemType = getSelectedItemType($facetItemTypes);

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetItemTypes, 'isNotSingleExtra')) > FACETS_MINIMUM_AMOUNT) {
				$facetItemTypes = array_filter($facetItemTypes, "isNotSingleExtra");
			}			

			$countCheckboxes = 0;
			$html = '<div>';
			// Build first part of the checkboxes tag
			if (isset($selectedItemType)) {
				$url = getFieldUrl('type', null, $selectedItemType);
				$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"\" data-url=\"" . $url . "\" checked><b>" . html_escape($selectedItemType['name']) . "</b></div>";
				$countCheckboxes++;
			}

			$hidingSeparator = false;
			// Build additional part of the select tag (if needed)
			foreach ($facetItemTypes as $itemType) {
				if ($itemType != $selectedItemType) {
					if ($limitCheckboxes != 0 && $countCheckboxes >= $limitCheckboxes && !$hidingSeparator) {
						// Add link to show other values
						$html .= "<div class=\"hidden\" id=\"facet-extra-values-item-types\">";
						$hidingSeparator = true;
					}
					
					$url = getFieldUrl('type', $itemType['id']);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"" . $itemType['name'] . "\" data-url=\"" . $url . "\">" . html_escape($itemType['name']) . ($showPopularity ? "<span class=\"facet-checkbox-count\"> (" . $itemType['count'] . ")</span>" : "") . "</div>";
					$countCheckboxes++;
				}
			}
			
			if ($hidingSeparator) {
				$html .= "</div>";
				$html .= "<a id=\"facet-extra-link-item-types\" class=\"facet-visibility-toggle\" data-element-id=\"item-types\">" . FACETS_SHOW_MORE . "</a>";
			}

			$html .= "</div>";
		} else {
			$html = false;
		}

		return $html;
	}
	
	/**
	 * Return list of objects associated to element.
	 *
	 * @param recordType
	 * @param subsetSQL
	 * @param elementId
	 * @param isDate
	 * @param sortOrder
	 * @return objects
	 */
	function get_objects_for_element($recordType, $subsetSQL, $elementId, $isDate, $sortOrder) {
		// Create Where clauses
		$whereRecordType = createWhereRecordTypeClause($recordType);
		$whereSubset = createWhereSubsetClause($recordType, $subsetSQL);
		
		// Create the columns, groupBy and orderBy clauses
		if ($isDate) {
			$columns1 = array('SUBSTR(element_texts.text, 1, 4) AS year');
			$columns2 = 'COUNT(year) AS count';
			$groupBy = array('year', 'record_id');
			if ($sortOrder == 'count_alpha') {
				$orderBy = 'count DESC, year DESC';
			} else {
				$orderBy = 'year DESC';
			}
		} else {
			$columns1 = '';
			$columns2 = 'COUNT(text) AS count';
			$groupBy = array('text', 'record_id');
			if ($sortOrder == 'count_alpha') {
				$orderBy = 'count DESC, text ASC';
			} else {
				$orderBy = 'text ASC';
			}
		}	

		// Get the database.
		$db = get_db();
		// Get the table.
		$table = $db->getTable('ElementText');
		// Build the select query.
		$subQuery = $table->getSelect();
		$subQuery->columns($columns1);
		$subQuery->joinInner(array('elements' => $db->Elements), 'element_texts.element_id = elements.id', array());
		$subQuery->joinInner(array('element_sets' => $db->ElementSet), 'element_sets.id = elements.element_set_id', array());
		if ($recordType == 'item') {
			$subQuery->joinInner(array('items' => $db->Item), 'items.id = element_texts.record_id', array());
		} else {
			$subQuery->joinInner(array('collections' => $db->Collection), 'collections.id = element_texts.record_id', array());
		}
		$subQuery->where('elements.id = '. $elementId);
		$subQuery->where($whereRecordType);
		$subQuery->where($whereSubset);
		$subQuery->group($groupBy);
		
		$select = "SELECT *, " . $columns2 . " FROM (" . $subQuery . ") foo GROUP BY `text` ORDER BY " . $orderBy;

		return $table->fetchObjects($select);
	}

	/**
	 * Return HTML Select associated with Array of facets values.
	 *
	 * @param recordType
	 * @param subsetSQL
	 * @param elementId
	 * @param isDate
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @return html
	 */
	function get_element_facet_select($recordType, $subsetSQL, $elementId = 50, $isDate = false, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false) {
		// Build array
		if ($elements = get_objects_for_element($recordType, $subsetSQL, $elementId, $isDate, $sortOrder)) {
			$facetElement = array();
			foreach ($elements as $element) {
				if ($isDate) {
					$facetElement[$element->year] = $element->count;
				} else {
					$facetElement[$element->text] = $element->count;
				}
			}
			$element_id = $element->element_id;

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetElement, 'isNotSingleElement')) > FACETS_MINIMUM_AMOUNT) {
				$facetElement = array_filter($facetElement, "isNotSingleElement");
			}			

			// Get current parameters to check if one is selected
			if (!empty($_GET['advanced'])) {
				$search = $_GET['advanced'];
				foreach ($search as $searchIndex => $searchArray){
					if (isset($searchArray['element_id']) && $searchArray['element_id'] == $element_id) {
						$term = $searchArray['terms'];
						break;
					}
				}
			}

			$addOptions = false;
			// Build first part of the select tag
			if (isset($term)){
				$html =	"<div class=\"select-cross\"><select id=\"" . $element_id . "\" class=\"facet-selected\" name=\"" . $elementId . "\">";
				$url = getElementFieldUrl($element_id, null, $isDate);
				$html .= "<option value=\"\" data-url=\"" . $url . "\"> " . html_escape(__('Remove filter')) . "...</option>";
				$html .= "<option selected value=\"\">$term</option>";
			} elseif (count($facetElement) > 0) {
				$html =	"<div class=\"select-arrow\"><select id=\"" . $element_id . "\" class=\"facet\" name=\"" . $elementId . "\">";
				$html .= "<option value=\"\">" . html_escape(__('Select')) . "...</option>";
				$addOptions = true;
			}

			// Build additional part of the select tag (if needed)
			if ($addOptions) {
				foreach ($facetElement as $name => $count) {
					$url = getElementFieldUrl($element_id, $name, $isDate);
					$html .= "<option value=\"" . $name . "\" data-url=\"" . $url . "\">" . $name . ($showPopularity ? " (" . $count . ")" : "") . "</option>";
				}
			}
			$html .= "</select></div>";
		} else {
			$html = false;
		}

		return $html;
	}
	
	/**
	 * Return HTML Checkboxes associated with Array of facets values.
	 *
	 * @param recordType
	 * @param subsetSQL
	 * @param elementId
	 * @param isDate
	 * @param hideSingleEntries
	 * @param sortOrder
	 * @param showPopularity
	 * @param limitCheckboxes
	 * @return html
	 */
	function get_element_facet_checkboxes($recordType, $subsetSQL, $elementId = 50, $isDate = false, $hideSingleEntries = false, $sortOrder = 'count_alpha', $showPopularity = false, $limitCheckboxes = 0) {
		// Build array
		if ($elements = get_objects_for_element($recordType, $subsetSQL, $elementId, $isDate, $sortOrder)) {
			$facetElement = array();
			foreach ($elements as $element) {
				if ($isDate) {
					$facetElement[$element->year] = $element->count;
				} else {
					$facetElement[$element->text] = $element->count;
				}
			}
			$element_id = $element->element_id;

			// Remove single entries if required
			if ($hideSingleEntries && count(array_filter($facetElement, 'isNotSingleElement')) > FACETS_MINIMUM_AMOUNT) {
				$facetElement = array_filter($facetElement, "isNotSingleElement");
			}			

			$selectedTerms = array();
			// Get current parameters to check if one or more are selected
			if (!empty($_GET['advanced'])) {
				$search = $_GET['advanced'];
				foreach ($search as $searchIndex => $searchArray){
					if (isset($searchArray['element_id']) && $searchArray['element_id'] == $element_id) {
						$selectedTerms[] = $searchArray['terms'];
					}
				}
			}

			$countCheckboxes = 0;
			$html = '<div>';
			// Build first part of the checkboxes tag
			if (!empty($selectedTerms)) {
				foreach ($selectedTerms as $term){
					$url = getElementFieldUrl($element_id, null, $isDate, $term);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"\" data-url=\"" . $url . "\" checked><b>" . html_escape($term) . "</b></div>";
					$countCheckboxes++;
				}
			}

			$hidingSeparator = false;
			// Build additional part of the select tag (if needed)
			foreach ($facetElement as $name => $count) {
				if (!in_array($name, $selectedTerms)) {
					if ($limitCheckboxes != 0 && $countCheckboxes >= $limitCheckboxes && !$hidingSeparator) {
						// Add link to show other values
						$html .= "<div class=\"hidden\" id=\"facet-extra-values-" . $elementId . "\">";
						$hidingSeparator = true;
					}
					
					$url = getElementFieldUrl($element_id, $name, $isDate);
					$html .= "<div class=\"facet-checkbox\"><input type=\"checkbox\" value=\"" . $name . "\" data-url=\"" . $url . "\">" . html_escape($name) . ($showPopularity ? "<span class=\"facet-checkbox-count\"> (" . $count . ")</span>" : "") . "</div>";
					$countCheckboxes++;
				}
			}
			
			if ($hidingSeparator) {
				$html .= "</div>";
				$html .= "<a id=\"facet-extra-link-" . $elementId . "\" class=\"facet-visibility-toggle\" data-element-id=\"" . $elementId . "\">" . FACETS_SHOW_MORE . "</a>";
			}

			$html .= "</div>";
		} else {
			$html = false;
		}

		return $html;
	}

	/**
	 * Add an Element Field to Search to the current URL.
	 *
	 * @param string $field_id The Element id.
	 * @param string $value The Element value.
	 * @param string $isDate 
	 * @return string The new URL.
	 */
	function getElementFieldUrl($field_id, $value = null, $isDate = false, $oldValue = null)
	{
		// Get the current facets.
		if (!empty($_GET['advanced'])) {
			$search = $_GET['advanced'];
			if ($value == '') {
				if ($oldValue == '') {
					// unset current element filter(s) if already set
					foreach ($search as $searchIndex => $searchArray){
						if (isset($searchArray['element_id']) && $searchArray['element_id'] == $field_id){
							unset ($search[$searchIndex]);
						}
					}
				} else {
					// unset current element filter with specific value if already set
					foreach ($search as $searchIndex => $searchArray){
						if (isset($searchArray['element_id']) && $searchArray['element_id'] == $field_id && $searchArray['terms'] == $oldValue){
							unset ($search[$searchIndex]);
							break;
						}
					}
				}
			}
		} else {
			$search = array();
		}
		if (!is_null($value)) {
			if ($isDate) {
				$search[] = array('element_id'=>$field_id, 'type'=>'starts with', 'terms'=>$value);
			} else {
				$search[] = array('element_id'=>$field_id, 'type'=>'is exactly', 'terms'=>$value);
			}
		}
		$params['advanced'] = $search;
		if (isset($_GET['origin'])) $params['origin'] = $_GET['origin'];
		if (isset($_GET['origin-title'])) $params['origin-title'] = $_GET['origin-title'];
		if (isset($_GET['type'])) $params['type'] = $_GET['type'];
		if (isset($_GET['collection'])) $params['collection'] = $_GET['collection'];
		if (isset($_GET['tag_id'])) $params['tag_id'] = $_GET['tag_id'];
		if (isset($_GET['tag'])) $params['tag'] = $_GET['tag'];
		if (isset($_GET['tags'])) $params['tags'] = $_GET['tags'];
		if (isset($_GET['search'])) $params['search'] = $_GET['search'];

		// Rebuild the route.
		return 'browse?' . http_build_query($params);
	}

	/**
	 * Add an Element Field to Search to the current URL.
	 *
	 * @param string $filter The filter field name (tags|tag_id|type|collection).
	 * @param string $value The Element value.
	 * @return string The new URL.
	 */
	function getFieldUrl($filter, $value = null, $oldValue = null)
	{
		// Get the current facets.
		if (!empty($_GET['advanced'])) {
			$search = $_GET['advanced'];
		} else {
			$search = array();
		}
		// set previous parameters
		$params['advanced'] = $search;
		if (isset($_GET['origin'])) $params['origin'] = $_GET['origin'];
		if (isset($_GET['origin-title'])) $params['origin-title'] = $_GET['origin-title'];
		if (isset($_GET['type'])) $params['type'] = $_GET['type'];
		if (isset($_GET['collection'])) $params['collection'] = $_GET['collection'];
		if (isset($_GET['tag_id'])) $params['tag_id'] = $_GET['tag_id'];
		if (isset($_GET['tag'])) $params['tag'] = $_GET['tag'];
		if (isset($_GET['tags'])) $params['tags'] = $_GET['tags'];
		if (isset($_GET['search'])) $params['search'] = $_GET['search'];

		// set(unset) current
		if (!is_null($value)){
			$params[$filter] = $value;
		} else {
			unset($params[$filter]);
		}

		// Rebuild the route.
		return 'browse?' . http_build_query($params);
	}
	
	/**
	 * Add a Tag Field to Search to the current URL.
	 *
	 * @param string $filter The filter field name (tags|tag_id|type|collection).
	 * @param string $value The Element value.
	 * @return string The new URL.
	 */
	function getTagUrl($value = null)
	{
		// Get the current facets.
		if (!empty($_GET['advanced'])) {
			$search = $_GET['advanced'];
		} else {
			$search = array();
		}
		// set previous parameters
		$params['advanced'] = $search;
		if (isset($_GET['origin'])) $params['origin'] = $_GET['origin'];
		if (isset($_GET['origin-title'])) $params['origin-title'] = $_GET['origin-title'];
		if (isset($_GET['type'])) $params['type'] = $_GET['type'];
		if (isset($_GET['collection'])) $params['collection'] = $_GET['collection'];
		if (isset($_GET['tag_id'])) $params['tag_id'] = $_GET['tag_id'];
		if (isset($_GET['tag'])) $params['tag'] = $_GET['tag'];
		if (isset($_GET['tags'])) $params['tags'] = $_GET['tags'];
		if (isset($_GET['search'])) $params['search'] = $_GET['search'];

		// set(unset) current
		if (!is_null($value)) {
			if (isset($params['tags'])) {
				$tags = explode(option('tag_delimiter'), $params['tags']);
				if (in_array($value, $tags)) {
					$tagToRemove = array($value);
					$tags = array_diff($tags, $tagToRemove);
				} else {
					$tags[] = $value;
				}
				$params['tags'] = implode(option('tag_delimiter'), $tags);
		
				if (empty($params['tags'])) unset($params['tags']);
			} else {
				$params['tags'] = $value;
			}
		} else {
			unset($params['tags']);
		}

		// Rebuild the route.
		return 'browse?' . http_build_query($params);
	}

	function isFacetActive($recordType, $element_name, $settings) {
		if (isset($settings['elements'][$element_name][$recordType])) {
			return ((bool)$settings['elements'][$element_name][$recordType]);
		}
		return false;
	}
	
	function isNotSingleElement($count) {
		if ($count > 1) return $count;
	}

	function isNotSingleExtra($element) {
		if ($element['count'] > 1) return $element;
	}
	
	function createWhereRecordTypeClause($recordType) {
		return 'element_texts.record_type = \'' . ucfirst($recordType) . '\'';
	}
	
	function createWhereSubsetClause($recordType, $sql) {
		if ($sql != '') {
			return $recordType . 's.id IN (' . $sql . ')';
		} else {
			return '1=1';
		}
	}
	
	function recordTypeActive($recordType, $elements) {
		foreach ($elements as $element) {
			if (array_key_exists($recordType, $element)) return true;
		}
		return false;
	}
	
	function buildCollectionsArray($collections) {
		$facetCollections = array();
		foreach ($collections as $collection) {
			$facetCollections[$collection->id]['id'] = $collection->id;
			$facetCollections[$collection->id]['name'] = $collection->getDisplayTitle();
			$facetCollections[$collection->id]['count'] = $collection->count;
		}
		return $facetCollections;
	}
	
	function getSelectedCollection($collections) {
		if (isset($_GET['collection'])) {
			$collection_id = $_GET['collection'];
			if (array_key_exists($collection_id, $collections)) {
				return $collections[$collection_id];
			}
		}
	}
	
	function sortCollections($collections, $sortOrder = 'count_alpha') {
		if ($sortOrder == 'count_alpha') {
			array_multisort(array_column($collections, 'count'), SORT_DESC, array_column($collections, 'name'), SORT_ASC, $collections);
		} else {
			array_multisort(array_column($collections, 'name'), SORT_ASC, $collections);
		}
		return $collections;
	}

	function buildItemTypesArray($itemTypes) {
		$facetItemTypes = array();
		foreach ($itemTypes as $itemType) {
			$facetItemTypes[$itemType->id]['id'] = $itemType->id;
			$facetItemTypes[$itemType->id]['name'] = $itemType->name;
			$facetItemTypes[$itemType->id]['count'] = $itemType->count;
		}
		return $facetItemTypes;
	}

	function getSelectedItemType($itemTypes) {
		if (isset($_GET['type'])) {
			$itemType_id = $_GET['type'];
			if (array_key_exists($itemType_id, $itemTypes)) {
				return $itemTypes[$itemType_id];
			}
		}
	}
	
	function buildTagsArray($tags) {
		$facetTags = array();
		foreach ($tags as $tag) {
			$facetTags[$tag->id]['id'] = $tag->id;
			$facetTags[$tag->id]['name'] = $tag->name;
			$facetTags[$tag->id]['count'] = $tag->tagCount;
		}
		return $facetTags;
	}
?>
