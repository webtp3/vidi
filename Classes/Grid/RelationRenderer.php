<?php
namespace Fab\Vidi\Grid;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Fab\Vidi\Domain\Repository\ContentRepositoryFactory;
use TYPO3\CMS\Backend\Utility\IconUtility;
use Fab\Vidi\Domain\Model\Content;
use Fab\Vidi\Tca\Tca;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class rendering relation
 */
class RelationRenderer extends ColumnRendererAbstract {

	/**
	 * Render a representation of the relation on the GUI.
	 *
	 * @return string
	 */
	public function render() {

		$result = '';

		// Get TCA table service.
		$table = Tca::table($this->object);

		// Get label of the foreign table.
		$foreignLabelField = $this->getForeignTableLabelField($this->fieldName);

		if ($table->field($this->fieldName)->hasOne()) {

			$foreignObject = $this->object[$this->fieldName];

			if ($foreignObject) {
				$template = '<a href="%s" data-uid="%s" class="btn-edit invisible">%s</a><span>%s</span>';
				$result = sprintf($template,
					$this->getEditUri($foreignObject),
					$this->object->getUid(),
					IconUtility::getSpriteIcon('actions-document-open'),
					$foreignObject[$foreignLabelField]
				);
			}
		} elseif ($table->field($this->fieldName)->hasMany()) {

			if (!empty($this->object[$this->fieldName])) {

				$dataType = $this->object->getDataType();

				if (Tca::table($dataType)->field($this->fieldName)->isTree()) {

					$relatedDataType = Tca::table($this->object->getDataType())->field($this->fieldName)->getForeignTable();

					// Initialize the matcher object.
					/** @var \Fab\Vidi\Persistence\Matcher $matcher */
					$matcher = GeneralUtility::makeInstance('Fab\Vidi\Persistence\Matcher', array(), $relatedDataType);

					// Default ordering for related data type.
					$defaultOrderings = Tca::table($relatedDataType)->getDefaultOrderings();
					/** @var \Fab\Vidi\Persistence\Order $order */
					$defaultOrder = GeneralUtility::makeInstance('Fab\Vidi\Persistence\Order', $defaultOrderings);

					// Fetch related contents
					$relatedContents = ContentRepositoryFactory::getInstance($relatedDataType)->findBy($matcher, $defaultOrder);


					$fieldConfiguration = Tca::table($dataType)->field($this->fieldName)->getConfiguration();
					$parentField = $fieldConfiguration['treeConfig']['parentField'];

					$flatTree = array();
					foreach ($relatedContents as $node) {
						$flatTree[$node->getUid()] = array(
							'item' => $node,
							'parent' => $node[$parentField] ? $node[$parentField]['uid'] : NULL,

						);
					}

					$tree = array();

					// If leaves are selected without its parents selected, those are shown as parent
					foreach ($flatTree as $id => &$flatNode) {
						if (!isset($flatTree[$flatNode['parent']])) {
							$flatNode['parent'] = NULL;
						}
					}

					foreach ($flatTree as $id => &$node) {
						if ($node['parent'] === NULL) {
							$tree[$id] = &$node;
						} else {
							$flatTree[$node['parent']]['children'][$id] = &$node;
						}
					}

					$relatedContents = $tree;

					$result = 'todo: format content';

				} else {
					$template = '<li><a href="%s" data-uid="%s" class="btn-edit invisible">%s</a><span>%s</span></li>';

					/** @var $foreignObject \Fab\Vidi\Domain\Model\Content */
					foreach ($this->object[$this->fieldName] as $foreignObject) {
						$result .= sprintf($template,
							$this->getEditUri($foreignObject),
							$this->object->getUid(),
							IconUtility::getSpriteIcon('actions-document-open'),
							$foreignObject[$foreignLabelField]);
					}

					$result = sprintf('<ul class="unstyled">%s</ul>', $result);
				}

			}
		}
		return $result;
	}

	/**
	 * Render an edit URI given an object.
	 *
	 * @param Content $object
	 * @return string
	 */
	protected function getEditUri(Content $object) {
		return sprintf('alt_doc.php?returnUrl=%s&edit[%s][%s]=edit',
			rawurlencode($this->getModuleLoader()->getModuleUrl()),
			rawurlencode($object->getDataType()),
			$object->getUid()
		);
	}

	/**
	 * Return the label field of the foreign table.
	 *
	 * @param string $fieldName
	 * @return string
	 */
	protected function getForeignTableLabelField($fieldName) {

		// Get TCA table service.
		$table = Tca::table($this->object);

		// Compute the label of the foreign table.
		$relationDataType = $table->field($fieldName)->relationDataType();
		return Tca::table($relationDataType)->getLabelField();
	}

}
